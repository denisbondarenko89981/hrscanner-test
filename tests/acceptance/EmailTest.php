<?php
require_once 'vendor/autoload.php';

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Exception\NoSuchElementException;
use MyProject\TelegramBot;

//additional lib for driver process
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class EmailTest extends TestCase
{
    protected $driver; // Свойство для хранения объекта WebDriver
    protected $config; // Свойство для хранения конфигурации
    protected $telegramBot; // Свойство для хранения объекта TelegramBot

    public function setUp(): void
    {
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

    protected function sendMessage($txt)
    {
        $this->telegramBot->sendMessage($txt);
    }

    public function testWebPage()
    {
        // Переход на страницу https://hrscanner.ru/
        $this->driver->get('https://hrscanner.ru/');

        // Нажатие кнопки для открытия формы входа
        try {
            $button = $this->driver->findElement(WebDriverBy::xpath("//div[@class='header__row flex']//button[@class='btn_lk popup-link']"));
            $button->click();
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Не найден элемент.";
            $this->sendMessage($txt);
        }

        // Заполнение поля логина
        try {
            $emailInput = $this->driver->findElement(WebDriverBy::xpath("//fieldset[@class='form__group']//input[@name='login']"));
            $emailInput->sendKeys($this->config['hrEmail']);
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Не найдено поле ввода email (на стадии входа в лк).";
            $this->sendMessage($txt);
        }

        // Заполнение поля пароля
        try {
            $emailInput = $this->driver->findElement(WebDriverBy::xpath("//fieldset[@class='form__group']//input[@name='password']"));
            $emailInput->sendKeys($this->config['hrPassword']);
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Не найдено поле ввода password (на стадии входа в лк).";
            $this->sendMessage($txt);
        }

        // Клик на кнопку для входа
        try {
            $button = $this->driver->findElement(WebDriverBy::xpath("//form[@id='email-form-3']//button[@class='btn btnForm__popup btnForm__popup_signin']"));
            $button->click();
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Не найдена кнопка «Войти» (на стадии входа в ЛК).";
            $this->sendMessage($txt);
        }

        sleep(5);
        
        // Проверка перехода в ЛК
        $currentUrl = $this->driver->getCurrentURL();
        $expectedUrl = 'https://hrscanner.ru/ru/user/home';
        if ($currentUrl !== $expectedUrl) {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Получен URL $currentUrl \n вместо ожидаемого $expectedUrl (переход в ЛК не выполнен).";
            $this->sendMessage($txt);
        }

        sleep(5);
        
        // Заполнение поля с email
        try {
            $emailInput = $this->driver->findElement(WebDriverBy::xpath("//div[@class='input-group']//input[@id='email']"));
            $emailInput->sendKeys($this->config['emailLogin']);
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Не найдено поле ввода email (тестируемого).";
            $this->sendMessage($txt);
        }

        sleep(5);
        
        // Нажатие на кнопку "Резалт"
        try {
            $button = $this->driver->findElement(WebDriverBy::xpath("//div[@class='col-xs-2 cl']//button[@class='zenTooltip btn btn-default tst-btn disabled_']"));
            $button->click();
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Не найдена кнопка «Резалт» (вариант теста).";
            $this->sendMessage($txt);
        }

        sleep(5);
        
        // Нажатие на кнопку "Отправить новый тест"
        try {
            $button = $this->driver->findElement(WebDriverBy::xpath("//div[@class='exist_part send_row col-xs-2']//button[@class='btn btn-info send_test']"));
            $button->click();
        } catch (NoSuchElementException $exception) {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Не найдена кнопка «Отправить новый тест».";
            $this->sendMessage($txt);
        }

        // Данные для подключения к почтовому ящику
        $hostname = '{imap.yandex.ru:993/imap/ssl}INBOX';
        $username = $this->config['emailLogin']; // Используем данные из конфигурации
        $password = $this->config['emailPassword']; // Используем данные из конфигурации

        // Подключение к почтовому ящику
        $inbox = imap_open($hostname, $username, $password);
        if (!$inbox) {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Не удалось подключиться к почтовому сервису.";
            $this->sendMessage($txt);
            die('Не удается подключиться: ' . imap_last_error());
        }

        // Поиск сообщений от указанного отправителя
        $emails = imap_search($inbox, 'FROM "test@hrscanner.ru"');
        if ($emails) {
            rsort($emails);
            $latestEmailId = $emails[0];
            $overview = imap_fetch_overview($inbox, $latestEmailId, 0);
            $message = imap_fetchbody($inbox, $latestEmailId, 1);
            if (preg_match('/https:\/\/vacancy\.email[^\s]+/', $message, $matches)) {
                $url = $matches[0];
                // Переход по найденной ссылке
                $this->driver->get($url);

                // Удаление письма после успешного перехода по ссылке
                imap_delete($inbox, $latestEmailId);
                imap_expunge($inbox);
                $txt = "{$this->config['emojiSuccess']} Тест проверки email прошёл успешно.";
                $this->sendMessage($txt); // Используем метод sendMessage объекта TelegramBot
            } else {
                $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. В письме нет ссылки";
                $this->sendMessage($txt);
            }
        } else {
            $txt = "{$this->config['emojiFailure']} Тест проверки доставки Email НЕ прошёл. Письмо от test@hrscanner.ru не найдено.";
            $this->sendMessage($txt);
        }

        // Закрытие соединения почты
        imap_close($inbox);
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
