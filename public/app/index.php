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
$es = generatClient("es");

$hits = '';


if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $input = file_get_contents('php://input');

    $data = json_decode($input, true);

    $question = trim($data['question']);
    $question = strip_tags($question);

    $docs = searchSimilar($question, $es, $client);

    $context = implode(
        "\n",
        array_map(fn($d) => $d['_source']['content'], $docs)
    );

    $store_messages = $data['story'];

    // $prompt = "
    //     Ты помощник интернет-магазина akuprof.ru.

    //     Используй только этот контекст:
    //     $context


    //     Вопрос пользователя:
    //     $question

    // ";
    $systemPrompt = "
        Ты — квалифицированный ИИ-консультант интернет-магазина акустических и звукоизоляционных материалов akuprof.ru. 
        Твоя задача — помогать пользователям подбирать материалы, отвечать на вопросы о характеристиках, размерах и монтаже, строго опираясь на предоставленный контекст из базы данных.

        ### 1. ПРАВИЛА АНАЛИЗА КОНТЕКСТА (ВАЖНО):
        - Внимательно сопоставляй названия товаров. Пользователь может написать сокращенное название (например, «Membrane 3.0»), а в базе товар может называться развернуто («SoundGuard Membranе 2500x1200x3 мм»). Если бренд и базовая суть совпадают (3.0 и 3 мм) — считай этот контекст РЕЛЕВАНТНЫМ.
        - Ищи информацию во всех блоках: НАЗВАНИЕ, ОПИСАНИЕ, ХАРАКТЕРИСТИКИ. Размеры, вес, состав и индексы звукоизоляции (дБ) — это важнейшие данные, всегда извлекай их для ответа.
        - Если в контексте действительно нет запрашиваемой информации или речь идет о совершенно другом материале: вежливо ответь, что не можешь найти точный ответ на данный вопрос в каталоге, и предложи обратиться к менеджеру по тел.: +7 (495) 970-82-03.
        - ЗАПРЕЩЕНО использовать фразы: «в предоставленном контексте нет информации», «исходя из текста» или «согласно базе данных». Отвечай естественным языком, как живой эксперт компании, который знает этот ассортимент на память.

        ### 2. ПРАВИЛА ОФОРМЛЕНИЯ ССЫЛОК И ТОВАРОВ:
        - Если товар найден и соответствует запросу, ты ОБЯЗАН:
        1. Четко ответить на вопрос пользователя (например, назвать точные размеры, толщину или состав).
        2. Оформить ссылку на товар в формате Markdown: Название товара - (URL), взяв URL строго из поля ИСТОЧНИК (ССЫЛКА) в контексте.
        - Никогда не придумывай и не дорисовывай URL-адреса! Если ссылки в контексте нет — пиши просто название товара текстом.
        - Если в контексте есть ссылка на статью или инструкцию, обязательно упомяни её: «Рекомендуем также ознакомиться с инструкцией: Название - (URL)».

        ### 3. СЦЕНАРИИ ОТВЕТОВ:
        - Сценарий «Приветствие» (Пользователь просто поздоровался): Игнорируй контекст товаров. Ответь дружелюбно и профессионально, спроси, какой материал или задачу звукоизоляции нужно решить.
        - Сценарий «Вопрос о наличии / заказе объема»: Дай ответ по свойствам товара, но в конце добавь обязательную фразу: «Актуальное наличие товара на складе и возможность заказа нужного объема, пожалуйста, уточняйте у менеджера по телефону: +7 (495) 970-82-03».

        ### 4. ТОН И СТИЛЬ:
        - Пиши четко, емко, структурировано. Используй списки для перечисления характеристик (размеры, вес, дБ) — это облегчает чтение.
        - Избегай вводных фраз вроде «Итак», «Таким образом», «Я рад помочь».
        - Не задавай встречных вопросов и не пиши дежурные фразы вроде «Могу ли я еще чем-то помочь?» в конце ответа (исключение — приветствие).
    ";
    // $userPrompt = "
    //     Контекст:
    //     $context

    //     Вопрос пользователя:
    //     $question
    // ";  

    $question = ['role' => 'user', 'content' => $question];
    $systemPrompt = ['role' => 'system', 'content' => $systemPrompt];

    $prompt = $store_messages;

    array_push($prompt, $question);
    array_unshift($prompt, $systemPrompt);

    $responseAi = askGPT($prompt,$client);

    // $responseElastic = saveMessage($es, $question, $responseAi, $userData);

    // $messagesHistory = getMessage($es, $userData['email'], 5);

    header('Content-Type: application/json');

    echo json_encode([
        'status' => 'success',
        'responseAi' => $responseAi,
        "docs" => $docs
    ]);
}else{
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

// $client = generatClient("client");
// $es = generatClient("es");

// $hits = '';

function processBatch($batch, $client, $es){
    foreach($batch as $file){
        // echo($file);
        $content = file_get_contents(DATA_DIR . '/' . $file);
        $chunks = splitText($content);

        foreach ($chunks as $chunk) {
            $es->index([
                'index' => ELASTIC_INDEX,
                'body' => [
                    'content'   => $chunk,
                    'embedding' => getEmbedding($chunk, $client),
                    'source'    => $file,
                ],
            ]);
        }
    }
}

function searchSimilar(string $question, $es, Client $client): array
{
    $embeddingResponse = getEmbedding($question, $client);

    $queryVector = $embeddingResponse[0]['embedding'];

    $response = $es->search([
        'index' => ELASTIC_INDEX,
        'body' => [
            'size' => TOP_K,
            'knn' => [
                'field' => 'embedding',
                'query_vector' => $queryVector,
                'k' => TOP_K,
                'num_candidates' => 100
            ]
        ]
    ]);

    // $hits = $response['hits']['hits'];

    // foreach ($hits as $hit) {
    //     echo "ID: " . $hit['_id'] . "\n";
    //     echo "Text:\n";
    //     echo $hit['_source']['content'] . "\n";
    //     echo "-------------------------<br><br>";
        
    // }

    return $response['hits']['hits'];
}

// searchSimilar - преобразование запроса клиента в embedding и поиск в elastic наиболее подходящих 3 чанков 

function askGPT(array $prompt, Client $client)
{

$response = $client->post(
    'https://api.openai.com/v1/chat/completions', // Правильный эндпоинт OpenAI
    [
        'headers' => [
            'Authorization' => 'Bearer ' . OPENAI_API_KEY,
            'Content-Type'  => 'application/json',
        ],
        'json' => [
            'model' => 'gpt-5-nano', // Или 'gpt-4o' в зависимости от твоих задач
            'messages' => $prompt
        ]
    ]
);

    // $response = $client->post(
    //     'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY,
    //     [
    //         'json' => [
    //             'systemInstruction' => [
    //                 'parts' => [
    //                     [
    //                         'text' => $systemPrompt
    //                     ]
    //                 ]
    //             ],
    //             'contents' => [
    //                 [
    //                     'parts' => [
    //                         [
    //                             'text' => $userPrompt
    //                         ]
    //                     ]
    //                 ]
    //             ]
    //         ]
    //     ]
    // );

    $responseBody = $response->getBody()->getContents();

    // 2. Декодируем JSON-строку в ассоциативный массив PHP
    $data = json_decode($responseBody, true);

    // echo "<pre>";
    // echo($data['output'][1]['content'][0]['text']);
    // echo "</pre>";

    return $data['choices'][0]['message']['content'];
}
