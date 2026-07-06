<?php
$allowed_origins = [
    'https://localhost.akuprof.ru',
    'https://akuprof.ru'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization"); // Важно: Authorization разрешен
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

define('COOKIE_GENERATE_KEY', $_ENV['COOKIE_GENERATE_KEY']);

// Функция ТЕПЕРЬ НЕ СТАВИТ КУКУ, а возвращает готовую строку токена
function generateUserToken($secret) {
    // 1. Создаём уникальный id
    $id = bin2hex(random_bytes(16));

    // 2. Делаем подпись
    $signature = hash_hmac('sha256', $id, $secret);

    // 3. Соединяем в одну строку и возвращаем
    return $id . ':' . $signature;
}

// Пытаемся получить токен из заголовков (если фронтенд его прислал)
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

// Если заголовка нет — генерируем НОВЫЙ токен и отдаем фронтенду
if (empty($authHeader) || strpos($authHeader, 'Bearer ') !== 0) {
    $newToken = generateUserToken(COOKIE_GENERATE_KEY);
    
    echo json_encode([
        "status" => "authorized",
        "comment" => "Токен создан!",
        "token" => $newToken // Передаем токен фронтенду для сохранения
    ]);
    exit;
}

// Если заголовок пришел, валидируем его, как и раньше
$cleanToken = substr($authHeader, 7); // Отрезаем "Bearer "

if (strpos($cleanToken, ':') === false) {
    http_response_code(400);
    exit(json_encode(["error" => "Malformed token"]));
}

list($userId, $token) = explode(":", $cleanToken);
$expected = hash_hmac('sha256', $userId, COOKIE_GENERATE_KEY);

if (!hash_equals($expected, $token)) {
    http_response_code(403);
    exit(json_encode(["error" => "Invalid token"]));
}

// Если токен валидный, просто подтверждаем статус
echo json_encode([
    "status" => "authorized",
    "comment" => "Токен ранее был создан и он валиден!"
]);