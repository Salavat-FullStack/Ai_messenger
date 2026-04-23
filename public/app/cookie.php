<?php

header("Access-Control-Allow-Origin: https://localhost.akuprof.ru");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

define('COOKIE_GENERATE_KEY', $_ENV['COOKIE_GENERATE_KEY']);

$cookie_name = "ai_chat_cookie";

function createUserCookie($secret, $cookie_name) {
    // 1. создаём уникальный id
    $id = bin2hex(random_bytes(16));

    // 2. делаем подпись
    $signature = hash_hmac('sha256', $id, $secret);

    // 3. соединяем в одну строку
    $value = $id . ':' . $signature;

    // 4. сохраняем куку
    setcookie($cookie_name, $value, [
        'expires' => time() + (86400 * 30), // 30 дней
        'path' => '/',
        'httponly' => true,
        'secure' => true, // true если HTTPS
        'samesite' => 'None'
    ]);

    echo json_encode([
        "status" => "authorized",
        $_COOKIE[$cookie_name]
    ]);
}

if (!isset($_COOKIE[$cookie_name])) {
    createUserCookie(COOKIE_GENERATE_KEY, $cookie_name);
}

// list($userId, $token) = explode(":", $_COOKIE[$cookie_name]);

// $expected = hash_hmac('sha256', $userId, COOKIE_GENERATE_KEY);

// if (!hash_equals($expected, $token)) {
//     http_response_code(403);
//     exit("Invalid token");
// }

echo json_encode([
    "status" => "authorized",
    "user_id" => $userId,
    "token" => $token
]);