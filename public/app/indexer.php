<?php

require_once "functions.php";

define('DATA_DIR', __DIR__ . '/../dataText/parsed_products');

$client = generatClient("client"); 
$es = generatClient("es");
$files = array_slice(scandir(DATA_DIR), 2);

$batchSize = 7;
$batch = [];

foreach ($files as $file) {
    $fullPath = DATA_DIR . '/' . $file;
    if (!is_file($fullPath)) {
        continue;
    }

    $content = file_get_contents($fullPath);

    $batch[] = [
        'text' => $content,
        'file' => $file
    ];

    if (count($batch) === $batchSize) {
        // Передаем ELASTIC_INDEX четвертым аргументом для надежности
        processAndSendWholeProducts($batch, $client, $es, ELASTIC_INDEX);
        $batch = []; 
        
        // Пауза 0.5 секунды между батчами, чтобы не словить Rate Limit от OpenAI
        usleep(500000); 
    }
}

if (count($batch) > 0) {
    processAndSendWholeProducts($batch, $client, $es, ELASTIC_INDEX);
}

echo "\nИндексация всех товаров успешно завершена!\n";

/**
 * Функция отправки целых товаров в OpenAI и Elasticsearch
 */
function processAndSendWholeProducts($batch, $client, $es, $indexName) {
    $textsOnly = array_column($batch, 'text');
    
    // Получаем эмбеддинги
    $embeddingResponse = getEmbedding($textsOnly, $client);

    $bulk = [];
    foreach ($batch as $index => $item) {
        // Используем переданное имя индекса из аргумента $indexName
        $bulk[] = ['index' => ['_index' => $indexName]];
        $bulk[] = [
            'content'   => $item['text'], 
            'embedding' => $embeddingResponse[$index]['embedding'],
            'source'    => $item['file'],
        ];
    }

    // Загрузка в Elasticsearch
    $es->bulk(['body' => $bulk]);
    
    echo "Загружена пачка из " . count($batch) . " товаров в Elasticsearch.\n";
}