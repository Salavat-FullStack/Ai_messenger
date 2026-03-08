<?php

define('BASE_ARTICLES_URL', 'https://akuprof.ru/articles');
define('OUTPUT_DIR', __DIR__ . '/data/texts/product');

if (!file_exists(OUTPUT_DIR)) {
    mkdir(OUTPUT_DIR, 0777, true);
}

$manualPages = [
    "https://akuprof.ru/about_us.html",
    "https://akuprof.ru/delivery.html",
    "https://akuprof.ru/contacts.html"
];

function getHtml($url) {
    echo($url);
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: PHP-scraper\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    return @file_get_contents($url, false, $context);
}

// getHtml - возврашает html страницу, указанного url (Скачивает страницу, Возвращает строку)

function getArticleCategories($baseUrl) {
    $html = getHtml($baseUrl);
    if (!$html) return [];

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $links = $dom->getElementsByTagName('a');

    // foreach ($links as $link) {
    //     echo "Текст: " . trim($link->textContent) . "\n";
    //     echo "Ссылка: " . $link->getAttribute('href') . "\n";
    //     echo "--------------------\n";
    // }

    $categories = [];
    foreach ($links as $a) {
        $href = trim($a->getAttribute('href'));
        if (strpos($href, '#') !== false) {
            continue; 
        }
        if (strpos($href, '/brand-') !== false) {
            if (strpos($href, 'http') === 0) {
                $categories[] = $href;
                // echo("{$href} \n");
            } else {
                $categories[] = "https://akuprof.ru" . $href;
                // echo("https://akuprof.ru" . "{$href} \n");
            }
        }
    }
    return array_unique($categories);
}

// getArticleCategories("https://akuprof.ru/articles");

function getArticlesFromCategory($categoryUrl) {
    $html = getHtml($categoryUrl);
    if (!$html) return [];

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $links = $dom->getElementsByTagName('a');

    $articles = [];
    $catSlug = basename(parse_url($categoryUrl, PHP_URL_PATH));

    foreach ($links as $a) {
        $href = trim($a->getAttribute('href'));
        if (strpos($href, '#') !== false) {
            continue; 
        }
        $href = strtok($href, '?');
        if (strpos($href, $catSlug) !== false || strpos($href, '/' . $catSlug) === 0) {
            if (strpos($href, 'http') === 0) {
                $href = $href . "?limit=1000";
                $articles[] = $href;
            } else {
                $href = $href . "?limit=1000";
                $articles[] = "https://akuprof.ru" . $href;
            }
        }
    }
    return array_unique($articles);
}

// $productArray = [];

function getArticleProduct($url){
    $html = getHtml($url);
    if (!$html) return [];

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    $xpath = new DOMXPath($dom);

    $productContainer = $xpath->query("//div[contains(@class, 'category-page')]")->item(0);
    if(!$productContainer){
        echo("Ошибка нет productContainer\n");
        return [];
    }
    $productLinks = $xpath->query(".//a/@href", $productContainer);

    if(!$productLinks){
        echo("Ошибка нет productLinks\n");
        return [];
    }

    foreach ($productLinks as $link) {
        $l = trim($link->nodeValue . "\n");

        $linksArray[] = explode('#', $l)[0];
    }
    $linksArray = array_unique($linksArray);

    $linksArray = array_values($linksArray);

    echo("---------ссылки на продукты-------------\n");
    var_dump($linksArray);

    return $linksArray;
}

// // loadHTML - метод загружающий html(Разбирает HTML,Создаёт структуру DOM)
// // parse_url - берет url, после базового, пример: (https://akuprof.ru/stati-o-akupunkture  вернет /stati-o-akupunkture)
// // basename - пример: (stati-o-akupunkture вернет stati-o-akupunkture)
// // strpos - вернет позицию, откуда начинаеться строка, пример: (strpos("Hello World", "World") вернет 6)
// // array_unique - убирает дубли в массиве 

function cleanText($text) {
    if (!$text) return '';
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $seen = [];
    $cleanedLines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line && !in_array($line, $seen)) {
            // Преобразуем "Ключ:" в "Ключ —"
            $line = preg_replace('/^([А-ЯЁ][^:]+):\s*$/u', '$1 —', $line);
            $cleanedLines[] = $line;
            $seen[] = $line;
        }
    }
    return implode("\n", $cleanedLines);
}

// preg_split - разбивает предложение на массив
// preg_replace - заменяет значения из 1 аргумента на значение 2 аргумента в строке из 3 аргумента 
// implode - объединяет массив в строку (1 аргумент что должно быть между строками)
// trim - убирает пробелы

function extractText($html) {
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    
    $xpath = new DOMXPath($dom);

    // $description = $xpath->query("//div[contains(@class, 'category_description')]")->item(0);

    // if($description){
    //     // var_dump($description);
    //     echo('------------------------------------description---------------------\n');

    //     $text = $description->textContent;
        
    //     return cleanText($text);
    // }

    // $articleContent = $xpath->query("//div[contains(@class, 'article-content')]")->item(0);

    // if($articleContent){
    //     // var_dump($description);
    //     echo('------------------------------------articleContent---------------------\n');

    //     $text = $articleContent->textContent;
        
    //     return cleanText($text);
    // }

    // if(empty($articleContent) && empty($description)){
    //     return null;
    // }


        $productContainer = $xpath->query("//div[contains(@class, 'tabs-product')]")->item(0);
        $descriptionProduct = $xpath->query(".//div[@id='tab-description']", $productContainer)->item(0);

        $asanaBlocks = $xpath->query(".//div[contains(@class, 'asana-links')]", $descriptionProduct);

        foreach ($asanaBlocks as $asana) {
            $asana->parentNode->removeChild($asana);
        }

        $characteristics = $xpath->query("//div[@id='tab-specification']")->item(0);

        $characterText = $characteristics->textContent;

        $text = $descriptionProduct->textContent;

        $text = $text . $characterText;
        return cleanText($text);

    // $scriptTags = $dom->getElementsByTagName('script');
    // $remove = [];
    // foreach ($scriptTags as $tag) $remove[] = $tag;

    // $styleTags = $dom->getElementsByTagName('style');
    // foreach ($styleTags as $tag) $remove[] = $tag;
    
    // foreach ($remove as $tag) $tag->parentNode->removeChild($tag);

    // $text = $dom->textContent;
    // return cleanText($text);
}

// $test = getHtml("https://akuprof.ru/flexakustik-pir-50.html");
// $text = extractText($test);

// echo($text);

// // --- Основной блок ---
// $allUrls = [];

// // 1. Сбор категорий
echo "Собираем категории статей...\n";
$categories = getArticleCategories(BASE_ARTICLES_URL);
echo "Найдено категорий: " . count($categories) . "\n";

// 2. Сбор статей из категорий
foreach ($categories as $category) {
    echo "Сканируем категорию: $category\n";
    $articles = getArticlesFromCategory($category);
    echo "  Найдено статей: " . count($articles) . "\n";

    // $allUrls = array_merge($allUrls, $articles);
    print_r($articles);
    // usleep(500000); // 0.5 секунды

    echo "----------------------------------------product----------------------------------";

    foreach($articles as $article){
        $productArray[] = getArticleProduct($article);
    }
}

// echo("\n--------------конец-------------\n");
// // var_dump($productArray);

$products = [];

for($i = 0; $i <= count($productArray); $i++){
    foreach($productArray[$i] as $array){
        $products[] = $array;
    }
}


echo("\n--------------финальный массив-------------\n");
var_dump($products);

// // $products = array_slice($products, 0, 10);

// // return;
// // $total = 0;

// // foreach ($productArray as $products) {
// //     $total += count($products ?? []);
// // }

// // echo ("количество продуктов == $total");

// // 3. Добавляем ручные страницы
// // $allUrls = array_merge($allUrls, $manualPages);

// // // array_merge - объединяет два массива в один новый 

// // // 4. Убираем дубли
// // $allUrls = array_unique($allUrls);
// // echo "Всего URL для сканирования: " . count($allUrls) . "\n";

// // // 5. Сканирование и сохранение
$i = 1;

foreach ($products as $url) {
    echo "[$i/" . count($products) . "] Загружаем: $url\n";
    $html = getHtml($url);
    if ($html) {
        $text = extractText($html);
        $text = $text . "Ссылка на товар: $url";
        if ($text) {
            $slug = basename(parse_url($url, PHP_URL_PATH));
            $fileName = OUTPUT_DIR . "/{$slug}.txt";
            file_put_contents($fileName, $text);
            echo "  Сохранено: $fileName\n";
        } else {
            echo "  Текст не извлечён\n";
        }
    } else {
        echo "  Ошибка при загрузке\n";
    }
    $i++;
    usleep(500000); // пауза между запросами
}

echo "Готово!\n";