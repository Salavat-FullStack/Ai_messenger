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
// $userIdArr = [137759013, 230853692, 159563753];
$userIdArr = [137759013, 230853692];

if($_SERVER['REQUEST_METHOD'] === "POST"){
    // $input = file_get_contents("php://input");

    list($userId, $token) = explode(":", $_COOKIE["ai_chat_cookie"]);

    $expected = hash_hmac('sha256', $userId, COOKIE_GENERATE_KEY);

    if (!hash_equals($expected, $token)) {
        http_response_code(403);
        exit("Invalid token");
    }

    $name = '';
    $email = '';
    $phone = '';

    $userData = json_decode($_POST['USER_DATA'], true);
    
    if(!empty($userData)){

        $errors = [];

        $email = trim($userData['email']);
        $name = trim($userData['name']);
        $phone = trim($userData['phone']);    

        // if (empty($email)) {
        //     $errors[] = "Введите email";
        // }
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Некорректный email";
        }
        // if (empty($name)) {
        //     $errors[] = "Введите имя";
        // }

        if(!empty($name)){
            if (mb_strlen($name) < 2 || mb_strlen($name) > 30) {
                $errors[] = "Имя должно быть от 2 до 30 символов";
            }
            // Разрешаем только буквы
            if (!preg_match("/^[a-zA-Zа-яА-ЯёЁ]+$/u", $name)) {
                $errors[] = "Имя может содержать только буквы";
            }
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // if (empty($phone)) {
        //     $errors[] = "Введите номер телефона";
        // }

        if (!empty($phone) && mb_strlen($phone) < 10) {
            $errors[] = "Номер телефона слишком короткий (минимум 10 символов)";
        }
        
        if (!empty($errors)) {

            echo json_encode([
                "success" => false,
                "errors" => $errors
            ]);

            exit();
        }

        if(!empty($name)){
            $name = "<b>- Имя : </b>" . $name . "\n \n";
        }
        if(!empty($phone)){
            $phone = "<b>- Телефон : </b>" . $phone . "\n \n";
        }
        if(!empty($email)){
            $email = "<b>- Email : </b>" . $email . "\n \n";
        }
    }

    // $data = json_decode($input, true);

    // $userData = $data['userData'];

    $ip = $_SERVER['REMOTE_ADDR'];

    $response = file_get_contents("http://ip-api.com/json/$ip");

    $location = json_decode($response, true);

    $location = "<b>- Локация: </b>" . $location['country'] . ", " . $location['regionName'] . ", " . $location['city'] . "\n \n";

    $ip = "<b>- IP: </b>" . $ip . "\n \n";

    $url = "<b>- Страница: </b>" . $_POST['url'] . "\n \n";

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

        $message = $title . $userQuestion . $AiResponse . $name . $email. $phone . $location . $ip . $url . $documentId . $userIdText . $date;
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


        $message = $title . $userQuestion . $name . $email. $phone . $location . $ip . $url . $date . $documentId . $userIdText;
    }

    $filePath = null;
    $mimeType = null;


    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {

        $filePath = $_FILES['image']['tmp_name'];
        $mimeType = $_FILES['image']['type'];

    } else {

        if ($_POST['selectedAssistant'] == 'Менеджер' && isset($_FILES['image'])) {
            $message .= "Не удалось загрузить файл!";
        }
    }

    foreach ($userIdArr as $elem) {
        sendMessage($message, $elem, $filePath, $mimeType);
    }

    echo json_encode([
        'status' => 'success',
        'message' => "сообщение отправлено в max"
    ]);
}

function sendMessage($message, $user_id, $filePath = null, $mimeType = null) {

    global $tokenMax;

    $url = "https://platform-api.max.ru/messages?user_id=" . $user_id;

    // =========================
    // ЕСЛИ ЕСТЬ ФАЙЛ
    // =========================
    if ($filePath && file_exists($filePath)) {

        // определяем тип загрузки
        $uploadType = 'file';

        if ($mimeType && strpos($mimeType, 'image/') === 0) {
            $uploadType = 'image';
        }

        // получаем upload URL
        $uploadData = getUploadUrl($tokenMax, $uploadType);

        if (!isset($uploadData['url'])) {

            echo 'Ошибка получения upload URL';

            echo '<pre>';
            print_r($uploadData);
            echo '</pre>';

            exit;
        }

        $uploadUrl = $uploadData['url'];

        // загружаем файл
        $uploadResult = uploadFileToMax($uploadUrl, $filePath, $tokenMax);

        // echo '<pre>';
        // print_r($uploadResult);
        // echo '</pre>';
        $fileToken = null;

        // фото
        if (isset($uploadResult['photos'])) {

            $photo = array_values($uploadResult['photos'])[0] ?? null;

            $fileToken = $photo['token'] ?? null;
        }

        // обычный файл
        elseif (isset($uploadResult['token'])) {

            $fileToken = $uploadResult['token'];
        }

        // если ошибка
        if (!$fileToken) {

            echo 'Ошибка загрузки файла';

            echo '<pre>';
            print_r($uploadResult);
            echo '</pre>';

            exit;
        }

        // тип attachment
        $attachmentType = 'file';

        if ($uploadType === 'image') {
            $attachmentType = 'image';
        }

        // небольшая пауза
        sleep(1);

        // сообщение с файлом
        $data = [
            "text" => $message,
            "format" => "html",
            "attachments" => [
                [
                    "type" => $attachmentType,
                    "payload" => [
                        "token" => $fileToken
                    ]
                ]
            ]
        ];

    } else {

        // обычное сообщение
        $data = [
            "text" => $message,
            "format" => "html"
        ];
    }

    // отправка сообщения
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


function getUploadUrl($token, $type = 'file') {

    $url = "https://platform-api.max.ru/uploads?type=" . $type;

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: $token"
    ]);

    $response = curl_exec($ch);

    return json_decode($response, true);
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