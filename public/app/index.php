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

    $question = $data['question'];

    // $storeMessages = $data['storeMessages'];

    $docs = searchSimilar($question, $es, $client);

    $context = implode(
        "\n",
        array_map(fn($d) => $d['_source']['content'], $docs)
    );

    $prompt = "
        Ты помощник интернет-магазина akuprof.ru.

        Используй только этот контекст:
        $context

        Вопрос пользователя:
        $question

        Ответ должен быть четким и по делу.

        Правила ответа:

        1. Если пользователь только здоровается (например: здравствуйте, добрый день и т.д.), 
        то в этом случаи не пользуйся контекстом, просто ответь приветствием и вежливо уточни, что он хотел бы узнать.
        НЕ добавляй информацию про наличие товара.

        2. Если пользователь прямо спрашивает о наличии товара 
        (например: есть ли товар X в наличии, можно ли заказать товар X, 
        можно ли заказать 10 штук, доступен ли товар и т.д.), 
        то к ответу нужно добавить:
        О наличии товара, пожалуйста, уточните у менеджера по тел.: +7 (495) 970-82-03 или другим доступным способом.
        
        3. Если в твоем ответе, есть какие либо товары, то рятом с названием товара вставляй и ссылку на него, пример (название товара (ссылка)). Ссылка на товар должна ставиться только в том случае, если она есть в контексте! (не создавай ее сам!)  
        
        4. Если в контексте есть информация, которая частично помогает ответить на вопрос,
            используй её и дай максимально полезный ответ.

        5. Отвечай [NO_ANSWER] ТОЛЬКО если:
            - в контексте полностью отсутствует информация по теме вопроса
            - и ты вообще не можешь дать даже частичную рекомендацию

        6. Если товар есть в контексте, ты ОБЯЗАН:
            - кратко описать его
            - обязательно вставить ссылку (если она есть в контексте)
            - не отправлять к менеджеру без необходимости
        7. Если в контексте, есть ссылка на источник, доболяй этй ссылку в ответ и порекомендуй прочитать содержимое пользователю
        8. НЕ задавай вопросы и не предлогай помощь, если это не критично для ответа 
    ";

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

function askGPT(string $prompt, Client $client): string
{

    $response = $client->post(
        'https://api.openai.com/v1/responses',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-5-nano',
                'input' => $prompt,
            ],
        ]
    );

    $data = json_decode($response->getBody(), true);

    // echo "<pre>";
    // echo($data['output'][1]['content'][0]['text']);
    // echo "</pre>";

    return $data['output'][1]['content'][0]['text'];
}
