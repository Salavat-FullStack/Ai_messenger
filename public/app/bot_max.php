<?php

// $token = "f9LHodD0cOLwjuOGWlt5_5r_fk-nUKWe-e3hL5-f_Mg3JcT_L9d7gt1dqg8lbDBJJxPKQx6UEHnpoqND01Iy";

// $url = "https://platform-api.max.ru/subscriptions";

// $data = [
//     "url" => "https://chat-progress.ru/app/webhook.php",
//     "update_types" => ["message_created", "bot_started"],
//     "secret" => "salavat20212509"
// ];

// $ch = curl_init($url);

// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_POST, true);

// curl_setopt($ch, CURLOPT_HTTPHEADER, [
//     "Authorization: $token",
//     "Content-Type: application/json"
// ]);

// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// $result = curl_exec($ch);
    
$token = "f9LHodD0cOLwjuOGWlt5_5r_fk-nUKWe-e3hL5-f_Mg3JcT_L9d7gt1dqg8lbDBJJxPKQx6UEHnpoqND01Iy";
$user_id = "230853692";

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $input = file_get_contents("php://input");

    $data = json_decode($input, true);

    $userData = $data['userData'];

    if($data['selectedAssistant'] == 'ИИ ассистент'){
        $title = "<b>- Данные пользователя 🟢 </b> \n \n";

        $userDataText = "<b>- Данные пользователя 🟢 </b> \n" . 
                    "<b>Имя : </b>" . $userData['name'] . "\n" .
                    "<b>Фамилия : </b>" . $userData['surname'] . "\n" .
                    "<b>Email : </b>" . $userData['email'] . "\n \n" ;

        $userQuestion = "<b>- Вопрос пользователя : </b> \n" . $data['messageUser'] . "\n \n";
        $AiResponse = "<b>- Ответ ИИ : \n </b>" . $data['messageAi'] . "\n \n";

        $userAiQuestion = "<b>- Переделанный вопрос (ИИ) : </b> \n" . $data['messageReview'] . "\n \n";

        $date = "<b>- Дата : </b>" . $data['date'];

        $message = $title . $userDataText . $userQuestion . $userAiQuestion . $AiResponse . $date;
    }else if($data['selectedAssistant'] == 'Менеджер'){

        $title = "<b>- Вопрос менеджеру! 🔴 </b> \n \n";

        $userDataText = "<b>- Данные пользователя </b> \n" . 
                    "<b>Имя : </b>" . $userData['name'] . "\n" .
                    "<b>Фамилия : </b>" . $userData['surname'] . "\n" .
                    "<b>Email : </b>" . $userData['email'] . "\n \n" ;

        $userQuestion = "<b>- Вопрос пользователя : </b> \n" . $data['messageUser'] . "\n \n";

        $date = "<b>- Дата : </b>" . $data['date'] . "\n \n";

        $userId = "<b>- Id документа : </b>" . $data['UserId'];

        $message = $title . $userDataText . $userQuestion . $date . $userId;
    }

    $result = sendMessage($message);

    echo json_encode([
        'status' => 'success',
        'message' => "сообщение отправлено в max"
    ]);
}


function sendMessage($message){
    global $token;
    global $user_id;

    $url = "https://platform-api.max.ru/messages?user_id=" . $user_id;

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