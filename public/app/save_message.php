<?php

require_once 'functions.php';

$client = generatClient('es');

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $input = file_get_contents('php://input');

    $data = json_decode($input, true);

    $response = saveMessage($client, $data['messageUser'], $data['messageAi'], $data['userData'], $data['date']);

    echo json_encode([
        "response" => $response
    ]);
}

function saveMessage($client, $messageUser, $messageAi, $userData, $date){

    $params = [
        'index' => 'message_history',
        'body' => [
            "name" => $userData['name'],
            "surname" => $userData['surname'],
            "email" => $userData['email'],
            "messageUser" => trim($messageUser),
            "messageAi" => $messageAi,
            "created_at" => $date
        ]
    ];

    $response = $client->index($params);

    if($response['_shards']['failed'] === 0){
        return "Документ сохранен"; 
    }else{
        return "Что-то пошло не так!";
    }
}