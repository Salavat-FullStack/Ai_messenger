<?php

require_once "functions.php";

define('DATA_DIR', __DIR__ . '/../dataText/about');

$files = array_slice(scandir(DATA_DIR), 2);

$batchSize = 7;
$batch = [];
$bulk = [];

$client = generatClient("client");
$es = generatClient("es");


foreach ($files as $file) {

    $fullPath = DATA_DIR . '/' . $file;

    if (!is_file($fullPath)) {
        continue; 
    }

    $content = file_get_contents($fullPath);

    $chanks = splitText($content);

    // echo('------------------chanks--------------\n');
    // print_r($chanks);

    foreach($chanks as $chank){
        $batch[] = $chank;
    }

    $batchCount = count($batch);
    echo("count(batch) == $batchCount \n");

    $remainderBatch = 0;

    if(count($batch) <= $batchSize){
        echo("вызов, count(batch) <= batchSize  \n");

        $embeddingResponse  = getEmbedding($batch, $client);

        foreach ($batch as $index => $text) {

            $bulk[] = ['index' => ['_index' => ELASTIC_INDEX]];

            $bulk[] = [
                'content'   => $text,
                'embedding' => $embeddingResponse[$index]['embedding'],
                'source'    => $file,
            ];
        }

        $es->bulk(['body' => $bulk]);

        $batch = [];
        $bulk = [];
    }else if(count($batch) > $batchSize){
        while(count($batch) > 0){

            $newBatch = array_slice($batch, 0, $batchSize);

            $remainderBatch = count($batch) - count($newBatch);

            // вызов getEmbedding с $newBatch
            $embeddingResponse  = getEmbedding($newBatch, $client);

            foreach ($newBatch as $index => $text) {

                $bulk[] = ['index' => ['_index' => ELASTIC_INDEX]];

                $bulk[] = [
                    'content'   => $text,
                    'embedding' => $embeddingResponse[$index]['embedding'],
                    'source'    => $file,
                ];
            }

            $es->bulk(['body' => $bulk]);

            // уменьшение количества
            $batch = array_slice($batch, count($newBatch));
            $bulk = [];

            $l = count($newBatch);
            echo("newBatch == $l\n");
            echo("remainderBatch == $remainderBatch\n");

        }
    }
}