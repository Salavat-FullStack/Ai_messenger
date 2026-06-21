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

    $prompt = 'Ты — строго технический модуль оптимизации запросов для Elasticsearch. 
    Твоя задача — проанализировать последний вопрос пользователя с учетом истории диалога и определить, является ли он самостоятельным.

    Правила оптимизации:
    1. Если последний вопрос самостоятельный и понятен без контекста — верни его БЕЗ ИЗМЕНЕНИЙ.
    2. Если вопрос несамостоятельный и ссылается на контекст прошлых сообщений (например: "сколько они стоят?", "дай ссылку на первый"), перепиши его так, чтобы он стал полностью самостоятельным, подставив конкретные названия товаров, брендов или деталей из истории (Пример: "Сколько стоят товары X и Y?").

    КРИТИЧЕСКИЕ ПРАВИЛА:
    - На выходе ты должен вернуть ТОЛЬКО текст финального вопроса для поисковика.
    - Категорически запрещено добавлять вводные слова, пояснения, кавычки или знаки пунктуации вокруг всего ответа. Никаких "Вот переделанный вопрос:". Только чистый текст запроса.

    - Если пользователь просто поздововался, (привет, добрый день, как дела и тд) то просто оставь вопрос как есть без изменений.

    - ЕСЛИ ВОПРОС САМОСТОЯТЕЛЬНЫЙ (например, пользователь назвал новый товар или спрашивает про доставку/оплату) — верни его БЕЗ ИЗМЕНЕНИЙ, убрав приветствие.

    Ответ должен содержать ТОЛЬКО финальный текст поискового запроса. Никаких вводных слов, пояснений и знаков препинания.';
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