<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// define('ELASTIC_INDEX', 'documents');
define('CHUNK_SIZE', 1000);
define('CHUNK_OVERLAP', 200);
define('TOP_K', 8);

define('ELASTIC_HOST', $_ENV['ELASTIC_HOST']);
define('GIGACHAT_AUTH_KEY', $_ENV['GIGACHAT_AUTH_KEY']);

use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;

function getElasticIndex(): string 
{
    // Значение по умолчанию на случай, если Referer не подошел
    $defaultIndex = 'documents'; 

    if (!isset($_SERVER['HTTP_REFERER'])) {
        return $defaultIndex;
    }

    $referer = $_SERVER['HTTP_REFERER'];
    $domain = parse_url($referer, PHP_URL_HOST);

    if ($domain === 'zvukoizolyatsiya.com') {
        return 'documents_zvukoizol';
    }

    if ($domain === 'akuprof.ru' || $domain === 'localhost.akuprof.ru') {
        return 'documents';
    }

    if($domain == 'izomaxx.ru'){
        return 'documents_izomaxx';
    }

    if($domain == 'stroytovary.com'){
        return 'documents_stroytovary';
    }

    return $defaultIndex;
}

function generatClient($data){
    $client = new Client();

    $es = ClientBuilder::create()
        ->setHosts([ELASTIC_HOST])
        ->build();

    if($data == "client"){
        return $client;
    }else if($data == "es"){
        return $es;
    }
}

function getEmbedding($text, Client $client): array
{
    $response = $client->post($_ENV['AI_API_URL'] . '/embeddings', [
        'headers' => [
            'Authorization' => 'Bearer ' . $_ENV['AI_API_KEY'],
            'Content-Type'  => 'application/json',
        ],
        'json' => [
            'model' => 'openai/text-embedding-3-small',
            'input' => is_array($text) ? $text : [$text],
        ],
    ]);

    $data = json_decode($response->getBody(), true);
    return $data['data'];
}

function splitText(string $text): array
{
    $chunks = [];
    $length = mb_strlen($text);
    $start = 0;

    while ($start < $length) {
        $chunks[] = mb_substr($text, $start, CHUNK_SIZE);
        $start += (CHUNK_SIZE - CHUNK_OVERLAP);
    }

    return $chunks;
}


function getGigaToken(Client $client): string
{
    $cacheFile = sys_get_temp_dir() . '/gigachat_token.json';

    if (file_exists($cacheFile)) {
        $cache = json_decode(file_get_contents($cacheFile), true);
        // expires_at в миллисекундах, берём запас в 60 секунд
        if (!empty($cache['access_token']) && $cache['expires_at'] / 1000 > time() + 60) {
            return $cache['access_token'];
        }
    }

    $response = $client->post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
        'verify' => $_ENV['GIGACHAT_CERT'],
        'headers' => [
            'Authorization' => 'Basic ' . $_ENV['GIGACHAT_AUTH_KEY'],
            'RqUID'         => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)),
            'Content-Type'  => 'application/x-www-form-urlencoded',
            'Accept'        => 'application/json',
        ],
        'form_params' => ['scope' => 'GIGACHAT_API_PERS'],
    ]);

    $data = json_decode($response->getBody(), true);
    file_put_contents($cacheFile, json_encode($data));

    return $data['access_token'];
}