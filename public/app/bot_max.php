<?php

header("Access-Control-Allow-Origin: https://localhost.akuprof.ru");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

define('COOKIE_GENERATE_KEY', $_ENV['COOKIE_GENERATE_KEY']);

$tokenMax = "f9LHodD0cOLwjuOGWlt5_5r_fk-nUKWe-e3hL5-f_Mg3JcT_L9d7gt1dqg8lbDBJJxPKQx6UEHnpoqND01Iy";
$user_id = "230853692";

if($_SERVER['REQUEST_METHOD'] === "POST"){
    $input = file_get_contents("php://input");

    list($userId, $token) = explode(":", $_COOKIE["ai_chat_cookie"]);

    $expected = hash_hmac('sha256', $userId, COOKIE_GENERATE_KEY);

    if (!hash_equals($expected, $token)) {
        http_response_code(403);
        exit("Invalid token");
    }

    $data = json_decode($input, true);

    // $userData = $data['userData'];

    if($data['selectedAssistant'] == 'ИИ ассистент'){
        $title = "<b>- Вопрос для ИИ! 🟢 </b> \n \n";

        // $userDataText = "<b>- Данные пользователя 🟢 </b> \n" . 
        //             "<b>Имя : </b>" . $userData['name'] . "\n" .
        //             "<b>Фамилия : </b>" . $userData['surname'] . "\n" .
        //             "<b>Email : </b>" . $userData['email'] . "\n \n" ;

        $userQuestion = "<b>- Вопрос пользователя : </b> \n" . $data['messageUser'] . "\n \n";
        $AiResponse = "<b>- Ответ ИИ : \n </b>" . $data['messageAi'] . "\n \n";

        $userAiQuestion = "<b>- Переделанный вопрос (ИИ) : </b> \n" . $data['messageReview'] . "\n \n";

        $date = "<b>- Дата : </b>" . $data['date'];

        $message = $title . $userQuestion . $userAiQuestion . $AiResponse . $date;
        // $message = $title . $userDataText . $userQuestion . $userAiQuestion . $AiResponse . $date;
    }else if($data['selectedAssistant'] == 'Менеджер'){

        $title = "<b>- Вопрос менеджеру! 🔴 </b> \n \n";

        // $userDataText = "<b>- Данные пользователя </b> \n" . 
        //             "<b>Имя : </b>" . $userData['name'] . "\n" .
        //             "<b>Фамилия : </b>" . $userData['surname'] . "\n" .
        //             "<b>Email : </b>" . $userData['email'] . "\n \n" ;

        $userQuestion = "<b>- Вопрос пользователя : </b> \n" . $data['messageUser'] . "\n \n";

        $date = "<b>- Дата : </b>" . $data['date'] . "\n \n";

        $documentId = "<b>- Id документа : </b>" . $data['UserId'] . "\n \n";

        $userIdText = "<b>- Id пользователя : </b>" . $userId . "\n \n";


        $message = $title . $userQuestion . $date . $documentId . $userIdText;
    }

    $result = sendMessage($message);

    echo json_encode([
        'status' => 'success',
        'message' => "сообщение отправлено в max"
    ]);
}


function sendMessage($message){
    global $tokenMax;
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
        "Authorization: $tokenMax",
        "Content-Type: application/json"
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    return $result;
}