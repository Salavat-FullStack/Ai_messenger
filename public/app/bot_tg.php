<?php

define("TG_TOKEN", "8477216590:AAEsjTTHuO76KvPi9mDe27dPntIg3nQn-IY");
define("TG_USER_ID", -1003660883702);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $input = file_get_contents('php://input');

    $data = json_decode($input, true);

    // <b></b>

    $userData = "<b>Данные пользователя </b> \n" . 
                "<b>Имя : </b>" . $data[$userData['name']] . "\n" .
                "<b>Фамилия : </b>" . $data[$userData['surname']] . "\n" .
                "<b>Email : </b>" . $data[$userData['email']] . "\n" ;

    $userQuestion = "<b>Вопрос пользователя : </b>" . $data['messageUser'] . '\n';
    $AiResponse = "<b>Ответ ИИ : </b>" . $data['messageAi'];

    $message = $userData . $userQuestion . $AiResponse;

    $Query = array(
        "chad_id" => TG_USER_ID,
        "text" => $message,
        "parse_mode" => "html"
    );

    $ch = curl_init("https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage?" . http_build_query($Query));

    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayQuery);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $resultQuery = curl_exec($ch);
    curl_close($ch);

    header('Content-Type: application/json');

    echo json_encode([
        'status' => 'success',
        'message' => "сообщение отправлено в telegram",
        "resultQuery" => $resultQuery
    ]);
}