<?php

$allowed_origins = [
    'https://localhost.akuprof.ru',
    'https://akuprof.ru'
];

// Проверяем, откуда пришел запрос
if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Если это preflight-запрос (OPTIONS), сразу отдаем 200 и выходим
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'functions.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

define('COOKIE_GENERATE_KEY', $_ENV['COOKIE_GENERATE_KEY']);

$client = generatClient('es');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $input = file_get_contents('php://input');
    
    $data = json_decode($input, true);

    header("Content-Type: application/json");
        
    // 1. Получаем токен из заголовка вместо куки
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized: Missing token"]);
        exit;
    }

    echo $cleanToken;

    $cleanToken = substr($authHeader, 7); // Отрезаем "Bearer "

    if (strpos($cleanToken, ':') === false) {
        http_response_code(400);
        echo json_encode(["error" => "Bad Request: Invalid token format"]);
        exit;
    }

    // 2. Разделяем на ID и подпись
    list($userId, $token) = explode(":", $cleanToken);

    // 3. Ваша проверка подписи HMAC
    $expected = hash_hmac('sha256', $userId, COOKIE_GENERATE_KEY);

    if (!hash_equals($expected, $token)) {
        http_response_code(403);
        echo json_encode(["error" => "Invalid token"]);
        exit;
    }

    $response = getMessage($client, $userId, $data['assistant']);

    echo json_encode([
        'response' => $response
    ]);
}

function getMessage($client, $token, $assistant){
    if($assistant == "ИИ ассистент"){
        $index = "message_history";
    }else if($assistant == "Менеджер"){
        $index = "message_history_manager";
    }
    $params = [
        "index" => $index,
        "body" => [
            "size" => 50,
            "query" => [
                "match" =>[
                    "user_token" =>[
                        "query" => $token
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
                "user_token" => $token,
                "messageUser" => $hit['_source']['messageUser'],
                "managerResponse" => $hit['_source']['managerResponse'],
                "file" => $hit['_source']['file'],
                "date" => $hit['_source']['created_at']
            ];
        }
    }else{
        foreach($hits as $hit){
            $messages[] = [
                "user_token" => $token,
                "messageUser" => $hit['_source']['messageUser'],
                "messageAi" => $hit['_source']['messageAi'],
                "managerResponse" => $hit['_source']['managerResponse'],
                "date" => $hit['_source']['created_at']
            ];
        }
    }

    return $messages;
}
// echo "TEst";