<?php

$headers = getallheaders();
$secret = 'salavat20212509';

$token = "f9LHodD0cOLwjuOGWlt5_5r_fk-nUKWe-e3hL5-f_Mg3JcT_L9d7gt1dqg8lbDBJJxPKQx6UEHnpoqND01Iy";
// $user_id = "216673677";

function sendMessage($message, $userId){
    global $token;

    $url = "https://platform-api.max.ru/messages?user_id=" . $userId;

    $data = [
        "text" => $message,
        "format" => "html"
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $token",
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    return $result;
}

if (!isset($headers['X-Max-Bot-Api-Secret']) || $headers['X-Max-Bot-Api-Secret'] !== $secret) {
    http_response_code(403);
    exit;
}

$input = file_get_contents("php://input");
$json = json_decode($input, true);

$pretty = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// добавляем дату сверху
$line = date("Y-m-d H:i:s") . " " . $pretty . "\n";

// записываем в файл
file_put_contents('log.txt', $line, FILE_APPEND);

// file_put_contents(__DIR__ . "/webhook_log.txt", date('Y-m-d H:i:s') . " " . $input . PHP_EOL, FILE_APPEND);

// если пришло сообщение, вызываем функцию из bot_max.php
if (!empty($json['message'])) {
    $userId = $json['message']['user_id'];
    $text = "Привет, это авто-ответ!"; // можно парсить текст менеджера и формировать ответ

    // подключаем bot_max.php
    // include __DIR__ . '/bot_max.php';

    // отправляем ответ
    sendMessage($text, $userId);
}

// обязательно возвращаем 200 OK
http_response_code(200);