<?php

$headers = getallheaders();
$secret = 'salavat20212509';

if (!isset($headers['X-Max-Bot-Api-Secret']) || $headers['X-Max-Bot-Api-Secret'] !== $secret) {
    http_response_code(403);
    exit;
}

$input = file_get_contents("php://input");
$json = json_decode($input, true);

// логируем (для отладки)
file_put_contents(__DIR__ . "/webhook_log.txt", date('Y-m-d H:i:s') . " " . $input . PHP_EOL, FILE_APPEND);

// если пришло сообщение, вызываем функцию из bot_max.php
if (!empty($json['message'])) {
    $userId = $json['message']['user_id'];
    $text = "Привет, это авто-ответ!"; // можно парсить текст менеджера и формировать ответ

    // подключаем bot_max.php
    include __DIR__ . '/bot_max.php';

    // отправляем ответ
    sendMessage($text);
}

// обязательно возвращаем 200 OK
http_response_code(200);