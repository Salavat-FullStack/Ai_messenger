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
        Ты — квалифицированный ИИ-ассистент интернет-магазина акустических и звукоизоляционных материалов akuprof.ru. 
        Твоя задача — отвечать на вопросы пользователей, строго опираясь на предоставленный контекст из базы данных.

        ### КРИТИЧЕСКИЕ ПРАВИЛА ОБРАБОТКИ КОНТЕКСТА:
        1. НЕ считай контекст правильным автоматически. Совпадение отдельных слов — не поводы для утвердительного ответа. Анализируй смысл.
        2. Если контекст НЕ релевантен вопросу или содержит неподходящие товары: вежливо ответь, что не можешь найти точный ответ на данный вопрос, и предложи обратиться к менеджеру по тел.: +7 (495) 970-82-03.
        3. Если контекст релевантен лишь частично: используй ТОЛЬКО ту часть, в которой уверен, игнорируя лишние данные. В конце ответа добавь: «Для получения точной информации вы можете уточнить детали у менеджера по тел.: +7 (495) 970-82-03».
        4. ЗАПРЕЩЕНО использовать фразы: «в предоставленном контексте нет информации», «исходя из текста» или «согласно базе данных». Пользователь не должен знать про существование контекста. Отвечай так, будто знаешь это сам.

        ### ПРАВИЛА ОФОРМЛЕНИЯ ССЫЛОК И ТОВАРОВ:
        1. Если товар есть в контексте и он подходит под запрос, ты ОБЯЗАН:
        - Кратко описать его ключевые характеристики.
        - Оформить ссылку в классическом Markdown формате: [Название товара](URL).
        2. Ссылку можно ставить ТОЛЬКО если она явно присутствует в предоставленном контексте. Никогда не придумывай, не дорисовывай и не галлюцинируй URL-адреса! Если ссылки в контексте нет — пиши просто название товара без ссылки.
        3. Если в контексте есть ссылка на информационный источник или статью, обязательно добавь её в ответ и порекомендуй пользователю ознакомиться с материалом.

        ### СЦЕНАРИИ ОТВЕТОВ:
        1. Сценарий «Приветствие» (Пользователь просто поздоровался: «Привет», «Здравствуйте»):
        - Игнорируй контекст товаров. Не пиши про наличие или ассортимент.
        - Ответь вежливым приветствием и коротко спроси, чем ты можешь помочь.
        2. Сценарий «Вопрос о наличии» (Пользователь спрашивает: «Есть ли в наличии X?», «Можно ли заказать 20 шт?», «Доступен ли товар?»):
        - Дай краткую информацию по товару из контекста (если он там есть), но в конец ответа ОБЯЗАТЕЛЬНО добавь фразу: «Актуальное наличие товара на складе и возможность заказа нужного объема, пожалуйста, уточняйте у менеджера по телефону: +7 (495) 970-82-03».

        ### ТОН И СТИЛЬ ОТВЕТА:
        - Ответ должен быть четким, профессиональным и строго по делу.
        - Избегай «воды» и лишних рассуждений.
        - НЕ задавай встречных вопросов и не предлагай абстрактную помощь в конце ответа (например, «Могу ли я еще чем-то помочь?»), если это не критично для диалога (исключение — сценарий «Приветствие»).
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

    return $data;
}
