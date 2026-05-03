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
require_once 'functions.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

define('COOKIE_GENERATE_KEY', $_ENV['COOKIE_GENERATE_KEY']);

$client = generatClient('es');

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    try{
        $input = file_get_contents('php://input');

        // $data = json_decode($input, true);

        // if (!$data) {
        //     throw new Exception("Невалидный JSON");
        // }

        list($userId, $token) = explode(":", $_COOKIE["ai_chat_cookie"]);

        $expected = hash_hmac('sha256', $userId, COOKIE_GENERATE_KEY);

        if (!hash_equals($expected, $token)) {
            http_response_code(403);
            exit("Invalid token");
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $file = $_FILES['image'];

            if ($file['error'] !== 0) {
                echo json_encode(['error' => 'Ошибка при загрузке картинки!']);
                exit;
            }

            $allowedExtensions = [
                'txt',
                'png',
                'jpeg',
                'jpg',
                'gif',
                'webp',
                'doc',
                'pdf'
            ];

            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions)) {
                echo json_encode(['error' => 'Неверный формат файла']);
                exit;
            }

            $allowedMimeTypes = [
                'text/plain',
                'image/png',
                'image/jpeg',
                'image/gif',
                'image/webp',
                'application/pdf',
                'application/msword'
            ];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedMimeTypes)) {
                echo json_encode(['error' => 'Неверный MIME-тип']);
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

        if($_POST['selectedAssistant'] == 'ИИ ассистент'){
            $response = saveMessage(
                $client,
                $_POST['messageUser'],
                $_POST['messageAi'],
                $_POST['messageReview'],
                $userId,
                str_replace(' ', 'T', $_POST['date'])
            );
        }
        if($_POST['selectedAssistant'] == 'Менеджер'){
            $response = saveMessageManager(
                $client,
                $_POST['messageUser'],
                $_POST['managerResponse'],
                $userId,
                $fileUrl,
                str_replace(' ', 'T', $_POST['date'])
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

function saveMessage($client, $messageUser, $messageAi, $messageReview, $userData, $date){

    $params = [
        'index' => 'message_history',
        'body' => [
            "user_token" => $userData,
            "messageUser" => trim($messageUser),
            "messageReview" => trim($messageReview),
            "messageAi" => $messageAi,
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


function saveMessageManager($client, $messageUser, $managerResponse, $userData, $file, $date){

    $params = [
        'index' => 'message_history_manager',
        'body' => [
            "user_token" => $userData,
            "messageUser" => trim($messageUser),
            "managerResponse" => $managerResponse,
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