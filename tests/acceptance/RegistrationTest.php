<?php
require_once 'vendor/autoload.php';

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverWait;
use Facebook\WebDriver\WebDriverExpectedCondition;
use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Exception\NoSuchElementException;
use MyProject\TelegramBot;

//additional lib for driver process
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class RegistrationTest extends \Codeception\Test\Unit {
    private $config;
    private $driver;
    private $telegramBot;

    protected function setUp(): void {
        // Загрузка конфигурации
        $this->config = require __DIR__ . '/../../config/config.php';

        // Путь к ChromeDriver из конфигурационного файла
        $chromeDriverPath = $this->config['chromeDriverPath'];

        //TEST
        // Запуск ChromeDriver
        $this->process = new Process([$chromeDriverPath]);
        $this->process->start();

        // Подождите несколько секунд, чтобы ChromeDriver полностью запустился
        sleep(2);

        // Проверка, что процесс запущен
        if (!$this->process->isRunning()) {
            throw new ProcessFailedException($this->process);
        }

        // Проверка вывода процесса на наличие ошибок
        $output = $this->process->getOutput();
        $errorOutput = $this->process->getErrorOutput();

        if (!empty($errorOutput)) {
            throw new \RuntimeException('ChromeDriver failed to start: ' . $errorOutput);
        }

        //TEST END

        // Настройка опций Chrome
        $options = new ChromeOptions();
        $options->setBinary('/usr/bin/google-chrome');
        //$options->addArguments(['--start-fullscreen']);
        $options->addArguments(['--headless',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--remote-debugging-port=9222',
            '--window-size=1920,1080']);



        // Инициализация WebDriver
        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);

        // Использование URL ChromeDriver из конфигурационного файла
        $this->driver = RemoteWebDriver::create($this->config['chromeDriverUrl'], $capabilities);

        // Создание объекта TelegramBot
        $this->telegramBot = new TelegramBot($this->config);
    }

    public function testWebPage() {
        // Переход на страницу https://hrscanner.ru/
        $this->driver->get('https://hrscanner.ru/');

        // Проверка кнопки «Протестировать HRSCANNER»
        try {
            $button = $this->driver->findElement(WebDriverBy::xpath("//body[@class='body']//button[@class='btn__test popup-link']"));
            $button->click();
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки регистрации НЕ прошёл. Не найдена кнопка «Протестировать HRSCANNER».";
            $this->telegramBot->sendMessage($txt);
        }

        // Проверка поля «Ваш email»
        try {
            $emailInput = $this->driver->findElement(WebDriverBy::xpath("//fieldset[@class='form__group']//input[@id='regEmail']"));
            $date = new DateTime();
            $emailInput->sendKeys($date->getTimestamp() . '@hrscanner.ru');
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки регистрации НЕ прошёл. Не найдено поле ввода email.";
            $this->telegramBot->sendMessage($txt);
        }

        // Проверка поля «Номер телефона»
        try {
            $phoneInput = $this->driver->findElement(WebDriverBy::xpath("//fieldset[@class='form__group']//input[@id='regPhone']"));
            $date = new DateTime();
            $phoneInput->sendKeys("4" . $date->getTimestamp());
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки регистрации НЕ прошёл. Не найдено поле ввода телефона.";
            $this->telegramBot->sendMessage($txt);
        }

        // Проверка кнопки «Зарегистрироваться»
        try {
            $button = $this->driver->findElement(WebDriverBy::xpath("//div[@class='popup__form']//button[@class='btn btnForm__popup btnForm__popup_register']"));
            $button->click();
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки регистрации НЕ прошёл. Не найдена кнопка регистрации.";
            $this->telegramBot->sendMessage($txt);
        }

        // 20 секунд на загрузку страницы
        sleep(20);

        // Проверка конечного url
        $currentUrl = $this->driver->getCurrentURL();

        if ($currentUrl === 'https://hrscanner.ru/ru/user/home') {
            $txt = "{$this->config['emojiSuccess']} Тест проверки регистрации прошёл успешно";
            $this->telegramBot->sendMessage($txt);
        } else {
            $txt = "{$this->config['emojiFailure']} Тест проверки регистрации НЕ прошёл. Получен URL $currentUrl \n вместо ожидаемого.";
            $this->telegramBot->sendMessage($txt);
            $this->fail("Проверка URL не пройдена.");
        }

        // Закрытие браузера
        $this->driver->quit();
    }

    protected function tearDown(): void {
        // Остановка ChromeDriver после завершения тестов
        if ($this->process->isRunning()) {
            $this->process->stop();
        }

        // Закрытие браузера
        if ($this->driver) {
            $this->driver->quit();
        }
    }
}
?>
