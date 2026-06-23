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

    $context = "";
    
    foreach ($docs as $d) {
        $source = $d['_source'];
        
        // Собираем полную информацию о товаре в одну карточку
        $context .= "--- ТОВАР ИЗ КАТАЛОГА ---\n";
        $context .= "Название: " . $source['title'] . "\n";
        $sourceBrand = isset($source['brand']) ? $source['brand'] : 'Другие';
        $context .= "Бренд: " . $sourceBrand . "\n";
        $context .= "Ссылка: " . $source['url'] . "\n";
        $context .= "Описание и характеристики:\n" . $source['content'] . "\n";
        $context .= "-------------------------\n\n";
    }

// Всё, теперь переменная $context готова и в ней лежат полные данные!

    $store_messages = $data['story'];

    $systemPrompt = "
        Ты — квалифицированный ИИ-консультант интернет-магазина звукоизоляционных материалов akuprof.ru. Твоя задача — подбирать материалы и отвечать на вопросы, строго опираясь на предоставленный контекст (10 товаров из базы данных).

        ### 1. КРИТИЧЕСКИЙ ФИЛЬТР КОНТЕНТА (ВАЖНО):
        - Поисковая система может ошибаться и выдавать товары не по теме (например, звукоизоляцию для ТРУБ, когда пользователь спрашивает про ПОТОЛОК).
        - Ты ОБЯЗАН критически оценить каждый товар. Если товар НЕ ПОДХОДИТ по смыслу под конкретную задачу пользователя — ПОЛНОСТЬЮ ИГНОРИРУЙ его. Не упоминай его в ответе, как будто его нет в контексте. Рекомендуй только то, что реально решает проблему.
        - Внимательно сопоставляй названия. Если пользователь пишет сокращенно («Membrane 3.0»), а в базе товар называется «SoundGuard Membranе 2500x1200x3 мм» — считай его РЕЛЕВАНТНЫМ.
        - ЗАПРЕЩЕНО использовать фразы: «в предоставленном контексте нет информации», «согласно базе данных», «исходя из текста». Отвечай естественным языком, как живой эксперт, который знает ассортимент наизусть.
        - Если из 10 товаров ни один не подходит по смыслу: вежливо ответь, что не можешь найти точное решение в каталоге, и предложи обратиться к менеджеру по тел.: +7 (495) 970-82-03.

        ### 2. СЦЕНАРИИ ОТВЕТОВ:
        - Сценарий «Приветствие» (пользователь просто поздоровался): Категорически ЗАПРЕЩЕНО выводить товары, ссылки, дБ и телефоны. Ответь максимально коротко и просто (1-2 предложения). Пример: «Здравствуйте! Рад приветствовать вас в интернет-магазине AKUPROF.RU. Какую задачу по звукоизоляции вам необходимо решить? С удовольствием помогу!»
        - Сценарий «Вопрос о наличии / объеме»: Ответь по свойствам материалов, но в конце добавь фразу: «Актуальное наличие товара на складе и возможность заказа нужного объема, пожалуйста, уточняйте у менеджера по телефону: +7 (495) 970-82-03».

        ### 3. ПРАВИЛА ОФОРМЛЕНИЯ ТОВАРОВ И ССЫЛОК:
        - Для каждого подходящего по смыслу товара извлеки из контекста характеристики (размеры, вес, дБ) и кратко объясни, ПОЧЕМУ он подходит.
        - Оформляй ссылки строго в формате Markdown: [Название товара](URL), взяв URL в точности из поля url (или ССЫЛКА).
        - Никогда не выдумывай URL. Если ссылки в контексте нет — пиши просто название товара текстом.
        - Если есть ссылка на инструкцию, добавь: «Рекомендуем также ознакомиться с инструкцией: [Название инструкции](URL)».

        ### 4. ТОН И СТИЛЬ:
        - Пиши четко, емко, структурировано. Используй списки для характеристик.
        - Избегай вводных фраз вроде «Итак», «Таким образом», «Я рад помочь».
        - Не задавай встречных вопросов и не пиши «Могу ли я еще чем-то помочь?» в конце ответа (исключение — сценарий «Приветствие»).
    ";

    // ### 4. ТОН И СТИЛЬ И ФОКУС:
    //     - Отвечай строго на поставленный вопрос пользователя. Если пользователь просит (посоветовать/выбрать) или спрашивает размеры, давай краткий обзор и ключевые параметры. Не вываливай всю сопутствующую техническую информацию (инструкции по монтажу, нюансы адгезии штукатурки и т.д.), если о ней прямо не спросили.
    //     - Используй списки только тогда, когда нужно перечислить сами карточки товаров или явные числовые характеристики. Внутри описания товара пиши лаконичный связный текст.

    // ### 5. КРИТИЧЕСКИЕ ОГРАНИЧЕНИЯ:
    // - Запрещено перегружать ответ лишней технической информацией, которая напрямую не относится к вопросу пользователя. 


    $userContent = "КОНТЕКСТ ИЗ БАЗЫ ДАННЫХ:\n" . $context . "\n\nВЫШЕ ПРЕДОСТАВЛЕН КОНТЕКСТ. ИСПОЛЬЗУЯ ЕГО, ОТВЕТЬ НА ВОПРОС ПОЛЬЗОВАТЕЛЯ: " . $question;

    $question = ['role' => 'user', 'content' => $userContent];
    $systemPrompt = ['role' => 'system', 'content' => $systemPrompt];

    $prompt = $store_messages;

    array_push($prompt, $question);
    array_unshift($prompt, $systemPrompt);

    $responseAi = askGPT($prompt,$client);

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
    // 1. Получаем ИИ-вектор от OpenAI
    $embeddingResponse = getEmbedding($question, $client);
    $queryVector = $embeddingResponse[0]['embedding'];

    // 2. Выполняем гибридный поиск в Elasticsearch
    $response = $es->search([
        'index' => ELASTIC_INDEX,
        'body'  => [
            'size' => 8, 
            
            // Традиционный полнотекстовый поиск (BM25)
            'query' => [
                'multi_match' => [ 
                    'query'  => $question,
                    // Ищем по новым полям. Заголовку даем самый высокий приоритет (^4), описанию чуть меньше (^1.5)
                    'fields' => ['title^4', 'description^1.5', 'specification'], 
                    'type'   => 'best_fields', 
                    'operator' => 'or',
                    'boost'  => 0.5 // Оставляем пониженный коэффициент для текста, чтобы ИИ-смысл доминировал
                ]
            ],
            
            // Векторный поиск (Поиск по смыслу нейросети)
            'knn' => [
                'field'          => 'embedding',
                'query_vector'   => $queryVector,
                'k'              => 10,
                'num_candidates' => 100,
                'boost'          => 2.0 // Приоритет отдаем вектору
            ]
        ]
    ]);

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

    $responseBody = $response->getBody()->getContents();

    // 2. Декодируем JSON-строку в ассоциативный массив PHP
    $data = json_decode($responseBody, true);

    // echo "<pre>";
    // echo($data['output'][1]['content'][0]['text']);
    // echo "</pre>";

    return $data['choices'][0]['message']['content'];
}
