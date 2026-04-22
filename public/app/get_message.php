<?php

header("Access-Control-Allow-Origin: https://localhost.akuprof.ru");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'functions.php';

$client = generatClient('es');

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    $input = file_get_contents('php://input');

    $data = json_decode($input, true);

    header("Content-Type: application/json");

    $response = getMessage($client, $data['userData']['email'], $data['assistant']);

    echo json_encode([
        'response' => $response
    ]);
}

function getMessage($client, $email, $assistant){
    if($assistant == "ИИ ассистент"){
        $index = "message_history";
    }else if($assistant == "Менеджер"){
        $index = "message_history_manager";
    }
    $params = [
        "index" => $index,
        "body" => [
            "query" => [
                "match" =>[
                    "email" =>[
                        "query" => $email
                    ]
                ]
            ],
            "sort" =>[
                ['created_at' => ['order' => 'desc']]
            ]
        ]
    ];

    $response = $client->search($params);

    $hits = $response['hits']['hits'];

    $hits = array_reverse($hits);

    $messages = [];

    if($assistant == "Менеджер"){
        foreach($hits as $hit){
            $messages[] = [
                "email" => $email,
                "user_name" => $hit['_source']['name'],
                "messageUser" => $hit['_source']['messageUser'],
                "managerResponse" => $hit['_source']['managerResponse'],
                "date" => $hit['_source']['created_at']
            ];
        }
    }else{
        foreach($hits as $hit){
            $messages[] = [
                "email" => $email,
                "user_name" => $hit['_source']['name'],
                "messageUser" => $hit['_source']['messageUser'],
                "messageAi" => $hit['_source']['messageAi'],
                "date" => $hit['_source']['created_at']
            ];
        }
    }

    return $messages;
}
// echo "TEst";