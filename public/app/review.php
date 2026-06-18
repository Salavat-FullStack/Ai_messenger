<?php

header("Access-Control-Allow-Origin: https://localhost.akuprof.ru");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require __DIR__ . '/../../vendor/autoload.php';
require_once 'functions.php';

use GuzzleHttp\Client;

$client = generatClient("client");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Получаем массив истории и текущий запрос
    $storyArray = $data['story'] ?? [];
    $request = $data['request'] ?? '';

    // ТРАНСФОРМАЦИЯ МАССИВА В СТРОКУ КОНТЕКСТА
    $storyString = "";
    if (is_array($storyArray)) {
        foreach ($storyArray as $message) {
            $role = ($message['role'] === 'user') ? 'Пользователь' : 'Ассистент';
            $content = $message['content'] ?? '';
            $storyString .= "{$role}: {$content}\n";
        }
    }

    $prompt = 'Ты — технический модуль RAG-системы для строительного магазина.
Твоя задача — проанализировать историю диалога и текущий короткий вопрос пользователя. 
Если текущий вопрос зависит от контекста (содержит местоимения "этот", "он", "они", "под них", скрытый смысл "сколько стоит?", "какая толщина?"), перепиши его так, чтобы он стал самостоятельным и полным поисковым запросом для Elasticsearch.

ПРАВИЛА И ИСКЛЮЧЕНИЯ:
1. Если вопрос самостоятельный, начинается с приветствия или касается общих тем (доставка, оплата, контакты) БЕЗ привязки к товару — верни его БЕЗ ИЗМЕНЕНИЙ.
2. Вместо местоимений подставляй точные названия товаров/материалов из истории диалога.
3. Не придумывай новые бренды и свойства, которых не было в диалоге.
4. Ответ должен содержать ТОЛЬКО финальный текст вопроса. Никаких пояснений и вводных слов.';

    // Передаем уже преобразованную строку $storyString
    $response = reviewGpt($client, $prompt, $storyString, $request);

    echo json_encode([
        "response" => trim($response)
    ], JSON_UNESCAPED_UNICODE);
}

function reviewGpt(Client $client, $prompt, $story, $request) {
    try {
        $response = $client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
            ],
            'json' => [
                'model' => 'gpt-5-nano',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $prompt
                    ],
                    [
                        'role' => 'user',
                        'content' => "История диалога:\n$story\n\nТекущий вопрос пользователя:\n$request"
                    ]
                ]
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['choices'][0]['message']['content'] ?? '';

    } catch (\GuzzleHttp\Exception\ClientException $e) {
        echo '<pre>';
        echo $e->getResponse()->getBody()->getContents();
        echo '</pre>';
        die();
    }
}