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
$userIdArr = [137759013, 230853692];

if($_SERVER['REQUEST_METHOD'] === "POST"){
    // $input = file_get_contents("php://input");

    list($userId, $token) = explode(":", $_COOKIE["ai_chat_cookie"]);

    $expected = hash_hmac('sha256', $userId, COOKIE_GENERATE_KEY);

    if (!hash_equals($expected, $token)) {
        http_response_code(403);
        exit("Invalid token");
    }

    // $data = json_decode($input, true);

    // $userData = $data['userData'];

    if($_POST['selectedAssistant'] == 'ИИ ассистент'){
        $title = "<b>- Вопрос для ИИ! 🟢 </b> \n \n";

        // $userDataText = "<b>- Данные пользователя 🟢 </b> \n" . 
        //             "<b>Имя : </b>" . $userData['name'] . "\n" .
        //             "<b>Фамилия : </b>" . $userData['surname'] . "\n" .
        //             "<b>Email : </b>" . $userData['email'] . "\n \n" ;

        $userQuestion = "<b>- Вопрос пользователя : </b> \n" . $_POST['messageUser'] . "\n \n";
        $AiResponse = "<b>- Ответ ИИ : \n </b>" . $_POST['messageAi'] . "\n \n";

        $documentId = "<b>- Id документа : </b>" . $_POST['UserId'] . "\n \n";

        $userIdText = "<b>- Id пользователя : </b>" . $userId . "\n \n";

        $date = "<b>- Дата : </b>" . $_POST['date'];

        $message = $title . $userQuestion .  $AiResponse . $documentId . $userIdText . $date;
        // $message = $title . $userDataText . $userQuestion . $userAiQuestion . $AiResponse . $date;
    }else if($_POST['selectedAssistant'] == 'Менеджер'){

        $title = "<b>- Вопрос менеджеру! 🔴 </b> \n \n";

        // $userDataText = "<b>- Данные пользователя </b> \n" . 
        //             "<b>Имя : </b>" . $userData['name'] . "\n" .
        //             "<b>Фамилия : </b>" . $userData['surname'] . "\n" .
        //             "<b>Email : </b>" . $userData['email'] . "\n \n" ;

        $userQuestion = "";

        if(!empty($_POST['messageUser'])){
            $userQuestion = "<b>- Вопрос пользователя : </b> \n" . $_POST['messageUser'] . "\n \n";
        }

        $date = "<b>- Дата : </b>" . $_POST['date'] . "\n \n";

        $documentId = "<b>- Id документа : </b>" . $_POST['UserId'] . "\n \n";

        $userIdText = "<b>- Id пользователя : </b>" . $userId . "\n \n";


        $message = $title . $userQuestion . $date . $documentId . $userIdText;
    }

    $filePath = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $filePath = $_FILES['image']['tmp_name'];
    }else{
        if($_POST['selectedAssistant'] == 'Менеджер' && isset($_FILES['image'])){
            $message .= "Не удалось загрузить путь фото!"; 
        }
    }

    foreach($userIdArr as $elem){
        sendMessage($message, $elem, $filePath);
    }

    echo json_encode([
        'status' => 'success',
        'message' => "сообщение отправлено в max"
    ]);
}

function sendMessage($message, $user_id, $filePath = null){
    global $tokenMax;

    $url = "https://platform-api.max.ru/messages?user_id=" . $user_id;

    // 👉 если есть файл
    if ($filePath && file_exists($filePath)) {

        // 1. получаем upload URL
        $uploadData = getUploadUrl($tokenMax);
        $uploadUrl = $uploadData['url'];

        // echo "<pre>";
        // print_r($uploadData);
        // echo "</pre>";

        // 2. загружаем файл
        $uploadResult = uploadFileToMax($uploadUrl, $filePath, $tokenMax);


        // echo "----------------------------вывод uploadResult----------------------";
        // echo "<pre>";
        // print_r($uploadResult);
        // echo "</pre>";

        $fileToken = array_values($uploadResult['photos'])[0]['token'];

        // echo "<pre>";
        // print_r($fileToken);
        // echo "</pre>";

        // если вдруг что-то пошло не так
        if (!$fileToken) {
            return false;
        }

        // ❗ пауза (важно)
        sleep(1);

        $data = [
            "text" => $message,
            "format" => "html",
            "attachments" => [
                [
                    "type" => "image",
                    "payload" => [
                        "token" => $fileToken
                    ]
                ]
            ]
        ];

    } else {
        // 👉 обычное сообщение
        $data = [
            "text" => $message,
            "format" => "html"
        ];
    }

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

function getUploadUrl($token) {

    $ch = curl_init("https://platform-api.max.ru/uploads?type=image");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $token"
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        die('Curl error: ' . curl_error($ch));
    }
    $data = json_decode($response, true);

    // 👇 защита от ошибки
    if (!isset($data['url'])) {
        die('Ошибка получения upload URL: ' . $response);
    }

    return $data;
}


function uploadFileToMax($uploadUrl, $filePath, $token) {

    $ch = curl_init($uploadUrl);

    $postData = [
        'data' => new CURLFile($filePath)
    ];

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $token"
    ]);

    $response = curl_exec($ch);

    return json_decode($response, true);
}