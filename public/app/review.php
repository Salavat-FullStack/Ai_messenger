<?php

require __DIR__ . '/../vendor/autoload.php';
require_once 'functions.php';

use GuzzleHttp\Client;

$client = generatClient("client");

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $input = file_get_contents('php://input');

    $data = json_decode($input, true);

    $story = $data['story'];
    $request = $data['request'];

    $prompt = "
        Ты анализируешь диалог между пользователем и ассистентом.

        Твоя задача — переписать последний вопрос пользователя так,
        чтобы он был полностью понятен без истории диалога.

        Правила:
        1. Если вопрос уже самостоятельный — верни его без изменений.
        2. Если вопрос содержит ссылки на предыдущие сообщения
        (например: 'первый', 'второй', 'этот', 'тот', 'предыдущий',
        'который ты сказал', 'тот товар', 'из списка', местоимения),
        — замени их на конкретные названия или объекты из истории.
        3. Ничего не объясняй.
        4. Верни только переписанный вопрос.
        5. Не добавляй лишний текст.

        Ответ должен содержать только итоговый вопрос.
    ";

    $response = reviewGpt($client, $prompt, $story, $request);

    echo json_encode([
        "response" => $response
    ]);
}

function reviewGpt(Client $client, $prompt, $story, $request){
    $response = $client->post(
        'https://api.openai.com/v1/responses',
        [
            "headers" => [
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-5-nano',
                'input' => [
                    [
                        'role' => 'system',
                        'content' => $prompt
                    ],
                    [
                        'role' => 'user',
                        'content' => "История диалога:\n$story\n\nТекущий вопрос:\n$request"
                    ]
                ]
            ]
        ]
    );

    $data = json_decode($response->getBody(), true);

    return $data['output'][1]['content'][0]['text'];
}