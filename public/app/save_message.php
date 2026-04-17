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
        if($data['selectedAssistant'] == 'ИИ ассистент'){
            $response = saveMessage(
                $client,
                $data['messageUser'],
                $data['messageAi'],
                $data['messageReview'],
                $data['userData'],
                str_replace(' ', 'T', $data['date'])
            );
        }
        if($data['selectedAssistant'] == 'Менеджер'){
            $response = saveMessageManager(
                $client,
                $data['messageUser'],
                $data['managerResponse'],
                $data['userData'],
                str_replace(' ', 'T', $data['date'])
            );
        }

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
        return $response['_id'];
    }else{
        return "Что-то пошло не так!";
    }
}


function saveMessageManager($client, $messageUser, $managerResponse, $userData, $date){

    $params = [
        'index' => 'message_history_manager',
        'body' => [
            "name" => $userData['name'],
            "surname" => $userData['surname'],
            "email" => $userData['email'],
            "messageUser" => trim($messageUser),
            "managerResponse" => $managerResponse,
            "created_at" => $date
        ]
    ];

    $response = $client->index($params);

    if($response['_shards']['failed'] === 0){
        return $response['_id'];
    }else{
        return "Что-то пошло не так!";
    }
}