<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// define('ELASTIC_INDEX', 'documents');
define('CHUNK_SIZE', 1000);
define('CHUNK_OVERLAP', 200);
define('TOP_K', 8);

define('ELASTIC_HOST', $_ENV['ELASTIC_HOST']);
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY']);

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

function getEmbedding($text, Client $client) : array
{

    $response = $client->post(
        'https://api.openai.com/v1/embeddings',
        [
            'headers' => [
                'Authorization' => 'Bearer ' . OPENAI_API_KEY,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ],
        ]
    );

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