<?php

$allowed_origins = [
    'https://localhost.akuprof.ru',
    'https://akuprof.ru',
    "https://zvukoizolyatsiya.com"
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

    try{
$input = file_get_contents('php://input');

        // 1. Получаем токен из заголовка
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode(["error" => "Unauthorized: Missing token"]);
            exit;
        }

        $cleanToken = substr($authHeader, 7); // Извлекаем "id:signature"

        if (strpos($cleanToken, ':') === false) {
            http_response_code(400);
            echo json_encode(["error" => "Bad Request: Invalid token format"]);
            exit;
        }

        // 2. Ваша привычная деструктуризация
        list($userId, $token) = explode(":", $cleanToken);

        // 3. Ваша криптографическая проверка подписи
        $expected = hash_hmac('sha256', $userId, COOKIE_GENERATE_KEY);

        if (!hash_equals($expected, $token)) {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden: Invalid token signature"]);
            exit;
        }

        $fileUrl = 'не передан!';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0 && $_POST['selectedAssistant'] == 'Менеджер') {
            $file = $_FILES['image'];

            if ($file['error'] !== 0) {
                echo json_encode(['error' => 'Ошибка при загрузке картинки!']);
                exit;
            }

            $allowedExtensions = [

                // text
                'txt',

                // images
                'png',
                'jpeg',
                'jpg',
                'gif',
                'webp',

                // pdf
                'pdf',

                // word
                'doc',
                'docx',

                // excel
                'xls',
                'xlsx'
            ];

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions)) {
                echo json_encode(['error_file' => 'Неверный формат файла']);
                exit;
            }

             $allowedMimeTypes = [

                // txt
                'text/plain',

                // images
                'image/png',
                'image/jpeg',
                'image/gif',
                'image/webp',

                // pdf
                'application/pdf',

                // word
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',

                // excel
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);

            if (!in_array($mimeType, $allowedMimeTypes)) {
                echo json_encode(['error_file' => 'Неверный MIME-тип']);
                exit;
            }

            $uploadDir = __DIR__ . '/uploads/' . $userId . '/';

            // === 9. Создаём папку если нет
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // === 10. Генерация имени файла
            $fileName = uniqid() . '.' . $extension;

            // === 11. Полный путь
            $fullPath = $uploadDir . $fileName;

            // === 12. Сохранение
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                echo json_encode(['error' => 'Не удалось сохранить файл']);
                exit;
            }

            // === 13. URL
            $fileUrl = '/uploads/' . $userId . '/' . $fileName;

        }
        // else{
        //     echo json_encode([
        //         "response" => "файл не найден"
        //     ]);
        // }


        $messageUser = trim(strip_tags($_POST['messageUser'] ?? ''));
        $messageAi = trim(strip_tags($_POST['messageAi'] ?? ''));
        $managerResponse = strip_tags($_POST['managerResponse'] ?? '');

        $name = '';
        $email = '';
        $phone = '';

        $userData = json_decode($_POST['USER_DATA'], true);

        if(!empty($userData)){

            $errors = [];

            $email = trim($userData['email']);
            $name = trim($userData['name']);
            $phone = trim($userData['phone']);    

           if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Некорректный email";
            }

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
        }

        if($_POST['selectedAssistant'] == 'ИИ ассистент'){
            $response = saveMessage(
                $client,
                $messageUser,
                $messageAi,
                $_POST['messageReview'],
                '',
                $userId,
                str_replace(' ', 'T', $_POST['date']),
                $name,
                $phone,
                $email
            );
        }
        if($_POST['selectedAssistant'] == 'Менеджер'){
            $response = saveMessageManager(
                $client,
                $messageUser,
                $managerResponse,
                $userId,
                $fileUrl,
                str_replace(' ', 'T', $_POST['date']),
                $name,
                $phone,
                $email
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

function saveMessage($client, $messageUser, $messageAi, $messageReview, $managerResponse, $userData, $date, $name, $phone, $email){

    $params = [
        'index' => 'message_history',
        'body' => [
            "user_token" => $userData,
            "messageUser" => $messageUser,
            "messageReview" => $messageReview,
            "messageAi" => $messageAi,
            "managerResponse" => $managerResponse,
            "user_name" => $name,
            "phone" => $phone,
            "email" => $email,
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


function saveMessageManager($client, $messageUser, $managerResponse, $userData, $file, $date, $name, $phone, $email){

    $params = [
        'index' => 'message_history_manager',
        'body' => [
            "user_token" => $userData,
            "messageUser" => $messageUser,
            "managerResponse" => $managerResponse,
            "user_name" => $name,
            "phone" => $phone,
            "email" => $email,
            "file" => $file,
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