<?php
    
$token = "f9LHodD0cOLwjuOGWlt5_5r_fk-nUKWe-e3hL5-f_Mg3JcT_L9d7gt1dqg8lbDBJJxPKQx6UEHnpoqND01Iy";
$chat_id = "-73144948768060";

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $input = file_get_contents("php://input");

    $data = json_decode($input, true);

    $userData = $data['userData'];

    $selectedAssistant = $data['selectedAssistant'];

    $userDataText = "<b>- Данные пользователя </b> \n" . 
                "<b>Имя : </b>" . $userData['name'] . "\n" .
                "<b>Фамилия : </b>" . $userData['surname'] . "\n" .
                "<b>Email : </b>" . $userData['email'] . "\n \n" ;

    $userQuestion = "<b>- Вопрос пользователя : </b> \n" . $data['messageUser'] . "\n \n";
    $AiResponse = "<b>- Ответ ИИ : \n </b>" . $data['messageAi'] . "\n \n";

    $userAiQuestion = "<b>- Переделанный вопрос (ИИ) : </b> \n" . $data['messageReview'] . "\n \n";

    $date = "<b>- Дата : </b>" . $data['date'];

    $message = $userDataText . $userQuestion . $userAiQuestion . $AiResponse . $date;


    $url = "https://platform-api.max.ru/messages?chat_id=" . $chat_id;

    $data = [
        "text" => $message,
        "format" => "html"
    ];

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    // заголовки
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $token",
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);

    echo json_encode([
        'status' => 'success',
        'message' => "сообщение отправлено в max"
    ]);
}
