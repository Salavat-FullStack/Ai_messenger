<?php

require_once 'functions.php';

$client = generatClient('es');

$headers = getallheaders();
$secret = 'salavat20212509';

$token = "f9LHodD0cOLwjuOGWlt5_5r_fk-nUKWe-e3hL5-f_Mg3JcT_L9d7gt1dqg8lbDBJJxPKQx6UEHnpoqND01Iy";
// $user_id = "216673677";

function sendMessage($message, $userId){
    global $token;

    $url = "https://platform-api.max.ru/messages?user_id=" . $userId;

    $data = [
        "text" => $message,
        "format" => "html"
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $token",
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    return $result;
}

if (!isset($headers['X-Max-Bot-Api-Secret']) || $headers['X-Max-Bot-Api-Secret'] !== $secret) {
    http_response_code(403);
    exit;
}

$input = file_get_contents("php://input");
$json = json_decode($input, true);

$pretty = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// добавляем дату сверху
$line = date("Y-m-d H:i:s") . " " . $pretty . "\n";

// записываем в файл
file_put_contents('log.txt', $line, FILE_APPEND);

// file_put_contents(__DIR__ . "/webhook_log.txt", date('Y-m-d H:i:s') . " " . $input . PHP_EOL, FILE_APPEND);

// если пришло сообщение, вызываем функцию из bot_max.php
if (!empty($json['message']) && !empty($json['message']['link'])) {
    $userIdArr = [137759013, 230853692, 159563753, 160092633, 140001164, 153979238, 175971694];
    $userId = $json['message']['sender']['user_id'];

    $text = $json['message']['link']['message']['text'];

    $menagerResponse = $json['message']['body']['text'];

    preg_match('/Id документа\s*:\s*(\S+)/u', $text, $matches);

    $id = $matches[1] ?? null;

    preg_match('/Id пользователя\s*:\s*(\S+)/u', $text, $array);

    preg_match('/Вопрос\s+(?:для\s+)?(ИИ|менеджеру)!/u', $text, $typeMatch);

    $type = $typeMatch[1] ?? null;

    $userId = $array[1] ?? null;

    $UserName = ", ответил(а) пользователю с id = " . $userId;

    preg_match('/Имя\s*:\s*(.+)/u', $text, $nameMatch);

    $name = trim($nameMatch[1] ?? '');

    if(!empty($name)){
        $UserName = ", ответил(а) пользователю = " . $name;
    }

    if(!empty($id)){
        if($type === 'ИИ'){
            $params = [
                'index' => 'message_history',
                'id'    => $id,
                'body'  => [
                    'doc' => [
                        'managerResponse' => $menagerResponse
                    ]
                ]
            ];
        }else if($type === 'менеджеру'){
            $params = [
                'index' => 'message_history_manager',
                'id'    => $id,
                'body'  => [
                    'doc' => [
                        'managerResponse' => $menagerResponse
                    ]
                ]
            ];
        }else{
            sendMessage('не удалось получить тип сообщения', $userId);
        }

        $response = $client->update($params);
        // отправляем ответ
        foreach($userIdArr as $elem){
            sendMessage("менеджер - " . $json['message']['sender']['name'] . $UserName, $elem);
        }
    }else if(empty($id)){
        sendMessage('сообщение не имеет id документа', $userId);
    }
}else{
    sendMessage($json['message']['body']['text'], $json['message']['sender']['user_id']);
}

// обязательно возвращаем 200 OK
http_response_code(200);