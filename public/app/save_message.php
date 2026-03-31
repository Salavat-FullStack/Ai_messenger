<?php

require_once 'functions.php';

header('Content-Type: application/json');

$client = generatClient('es');

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    try{
        $input = file_get_contents('php://input');

        $data = json_decode($input, true);

        if (!$data) {
            throw new Exception("Невалидный JSON");
        }

        $response = saveMessage(
            $client,
            $data['messageUser'] ?? '',
            $data['messageAi'] ?? '',
            $data['messageReview'] ?? '',
            $data['userData'] ?? [],
            $data['date'] ?? ''
        );

        echo json_encode([
            "response" => $response
        ]);
    } catch(Throwable $e){
        echo json_encode([
            "error" => $e->getMessage()
        ]);
    }
}

function saveMessage($client, $messageUser, $messageAi, $messageReview, $userData, $date){

    $params = [
        'index' => 'message_history',
        'body' => [
            "name" => $userData['name'],
            "surname" => $userData['surname'],
            "email" => $userData['email'],
            "messageUser" => trim($messageUser),
            "messageReview" => trim($messageReview),
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