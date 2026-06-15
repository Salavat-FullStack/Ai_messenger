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

    // 1. Читаем сырой файл с диска
    $rawContent = file_get_contents($fullPath);

    // 2. Очищаем текст от табов и мусора "на лету" перед отправкой
    $cleanContent = cleanTextOnTheFly($rawContent);

    // Добавляем в батч уже чистый текст
    $batch[] = [
        'text' => $cleanContent,
        'file' => $file
    ];

    if (count($batch) === $batchSize) {
        processAndSendWholeProducts($batch, $client, $es, ELASTIC_INDEX);
        $batch = []; 
        usleep(500000); // Пауза 0.5 сек для лимитов OpenAI
    }
}

if (count($batch) > 0) {
    processAndSendWholeProducts($batch, $client, $es, ELASTIC_INDEX);
}

echo "\nИндексация очищенных товаров успешно завершена!\n";

/**
 * Функция умной очистки текста от табов и пустых пространств
 */
function cleanTextOnTheFly($text) {
    if (!$text) return '';
    
    // Заменяем все знаки табуляции на обычные пробелы
    $text = str_replace("\t", " ", $text);
    
    // Схлопываем множественные пробелы в один
    $text = preg_replace('/[ ]{2,}/', ' ', $text);
    
    // Разбиваем на строки
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $cleanedLines = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if ($line !== '') {
            $cleanedLines[] = $line;
        }
    }
    
    // Собираем обратно
    $resultText = implode("\n", $cleanedLines);

    // КРАСИВЫЙ ХАК ДЛЯ ХАРАКТЕРИСТИК:
    // Если параметр и цифра идут на разных строках, склеиваем их через двоеточие.
    // Это превратит: "Длина рулона, мм\n2500" в "Длина рулона, мм: 2500"
    $resultText = preg_replace('/(мм|м²|кг|дБ)\n(\d+)/ui', '$1: $2', $resultText);

    return $resultText;
}

/**
 * Функция отправки в OpenAI и Elasticsearch
 */
function processAndSendWholeProducts($batch, $client, $es, $indexName) {
    $textsOnly = array_column($batch, 'text');
    
    // Получаем эмбеддинги для чистого контента
    $embeddingResponse = getEmbedding($textsOnly, $client);

    $bulk = [];
    foreach ($batch as $index => $item) {
        $bulk[] = ['index' => ['_index' => $indexName]];
        $bulk[] = [
            'content'   => $item['text'], // Сюда идет очищенный текст
            'embedding' => $embeddingResponse[$index]['embedding'],
            'source'    => $item['file'],
        ];
    }

    $es->bulk(['body' => $bulk]);
    echo "Загружена чистая пачка из " . count($batch) . " товаров.\n";
}