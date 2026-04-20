<?php

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

header("Access-Control-Allow-Origin: https://site-a.com");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

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
        'secure' => false, // true если HTTPS
        'samesite' => 'Strict'
    ]);

    return $id;
}
