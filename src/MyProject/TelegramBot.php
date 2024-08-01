<?php
namespace MyProject;

class TelegramBot{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function sendMessage($txt)
    {
        $urlParameters = http_build_query([
            'chat_id' => $this->config['chatId'],
            'text' => $txt
        ]);

        $ch = curl_init($this->config['urlToken']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $urlParameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        if (empty($response)) {
            throw new Exception("Failed to send message");
        }

        return $response;
    }

    public function sendImage($txt, $filePath)
    {
        $url = "https://api.telegram.org/bot{$this->config['botToken']}/sendPhoto";

        $postFields = [
            'chat_id' => $this->config['chatId'],
            'caption' => $txt,
            'photo' => new CURLFile(realpath($filePath))
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:multipart/form-data"]);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $response = curl_exec($ch);
        curl_close($ch);

        if (empty($response)) {
            throw new Exception("Failed to send photo");
        }

        return $response;
    }
}
?>
