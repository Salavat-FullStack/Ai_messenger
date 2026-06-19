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
    Твоя задача — вернуть ключевые слова для поиска на основе последнего сообщения пользователя и истории.

    КРИТИЧЕСКИЕ ПРАВИЛА:
    1. ПРИВЕТСТВИЕ = НОВЫЙ ДИАЛОГ. Если последнее сообщение пользователя НАЧИНАЕТСЯ с приветствия ("привет", "здравствуйте", "добрый день" и т.д.), ты обязан ИГНОРИРОВАТЬ всю предыдущую историю. Очисти запрос от приветствия и верни только суть. 
    Пример: "привет посоветуй подрозетники" -> Результат: "посоветуй подрозетники" (или "подрозетники").

    2. ЕСЛИ ВОПРОС САМОСТОЯТЕЛЬНЫЙ (например, пользователь назвал новый товар или спрашивает про доставку/оплату) — верни его БЕЗ ИЗМЕНЕНИЙ, убрав приветствие.

    3. ЗАПРЕТ НА КОНСТРУКЦИИ И ОПЕРАТОРЫ. Запрещено выводить списки через "OR", "AND", запятые или слэши. Запрос должен выглядеть как обычная поисковая фраза человека. Никаких перечислений всех моделей из истории.

    4. КОНТЕКСТНАЯ ЗАВИСИМОСТЬ. Используй историю ТОЛЬКО если в последнем вопросе есть местоимения ("он", "они", "эти", "под них") или короткие уточнения ("сколько стоит?", "какая толщина?"). Тогда замени местоимение на конкретный товар из истории.

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