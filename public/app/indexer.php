<?php

require_once "functions.php";

define('DATA_DIR', __DIR__ . '/../dataText/documents_zvukoizol/about');
define('MAX_CHAR_LIMIT', 4000); // Лимит на каждое текстовое поле по отдельности

$client = generatClient("client"); 
$es = generatClient("es");
$files = array_slice(scandir(DATA_DIR), 2);

$batchSize = 7;
$batch = [];

foreach ($files as $file) {
    $fullPath = DATA_DIR . '/' . $file;
    if (!is_file($fullPath) || pathinfo($file, PATHINFO_EXTENSION) !== 'json') {
        continue; // Пропускаем всё, что не является .json файлом
    }

    // 1. Читаем JSON файл с диска и декодируем его в массив
    $jsonRaw = file_get_contents($fullPath);
    $productData = json_decode($jsonRaw, true);

    // Защита: проверяем наличие новых раздельных полей вместо 'content'
    if (!$productData || !isset($productData['title'])) {
        continue; 
    }

    // 2. Очищаем и жестко ограничиваем длину для ОПИСАНИЯ и ХАРАКТЕРИСТИК отдельно
    $cleanDescription   = cleanTextOnTheFly($productData['description'] ?? '');
    $cleanSpecification = cleanTextOnTheFly($productData['specification'] ?? '');

    // 3. Формируем текстовую строку для генерации ИИ-вектора (embedding) без бренда
    $textForEmbedding = "НАЗВАНИЕ: " . $productData['title'] . "\n";
    if (!empty($cleanDescription)) {
        $textForEmbedding .= "ОПИСАНИЕ:\n" . $cleanDescription . "\n";
    }
    if (!empty($cleanSpecification)) {
        $textForEmbedding .= "ХАРАКТЕРИСТИКИ:\n" . $cleanSpecification;
    }

    // Добавляем в батч структурированные данные без поля brand
    $batch[] = [
        'title'          => $productData['title'],
        'url'            => $productData['url'],
        'description'    => $cleanDescription,   // Поля теперь разделены
        'specification'  => $cleanSpecification, // Поля теперь разделены
        'embedding_text' => $textForEmbedding,   // Текст для OpenAI
        'file'           => $file
    ];

    if (count($batch) === $batchSize) {
        processAndSendWholeProducts($batch, $client, $es, "documents_zvukoizol");
        $batch = []; 
        usleep(500000); // Пауза 0.5 сек для лимитов OpenAI
    }
}


if (count($batch) > 0) {
    processAndSendWholeProducts($batch, $client, $es, "documents_zvukoizol");
}

echo "\nИндексация разделенных JSON товаров (с лимитом полей в " . MAX_CHAR_LIMIT . " симв.) успешно завершена!\n";

/**
 * Функция умной очистки текста от табов, пустых пространств + ограничение длины
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
    $resultText = preg_replace('/(мм|м²|кг|дБ)\n(\d+)/ui', '$1: $2', $resultText);

    // МАГИЯ ОБРЕЗКИ: 
    if (mb_strlen($resultText, 'UTF-8') > MAX_CHAR_LIMIT) {
        $resultText = mb_substr($resultText, 0, MAX_CHAR_LIMIT, 'UTF-8');
    }

    return $resultText;
}

/**
 * Функция отправки в OpenAI и Elasticsearch по разделенным полям
 */
function processAndSendWholeProducts($batch, $client, $es, $indexName) {
    // Берем специальную склейку текстов для генерации точного вектора
    $textsOnly = array_column($batch, 'embedding_text');
    
    // Получаем эмбеддинги
    $embeddingResponse = getEmbedding($textsOnly, $client);

    $bulk = [];
    foreach ($batch as $index => $item) {
        $bulk[] = ['index' => ['_index' => $indexName]];
        
        // РАСКЛАДЫВАЕМ ВСЁ ПО НОВЫМ ПОЛЯМ ДЛЯ СХЕМЫ ИНДЕКСА (БЕЗ BRAND)
        $bulk[] = [
            'title'         => $item['title'],
            'url'           => $item['url'],
            'description'   => $item['description'],   // В базу уходит отдельно
            'specification' => $item['specification'], // В базу уходит отдельно
            'embedding'     => $embeddingResponse[$index]['embedding'],
            'source'        => $item['file']
        ];
    }

    $es->bulk(['body' => $bulk]);
    echo "Загружена структурированная пачка из " . count($batch) . " товаров в Elastic.\n";
}