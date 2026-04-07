<?php
// читаем входящий запрос
$input = file_get_contents("php://input");

// логируем (чтобы видеть что приходит)
file_put_contents("log.txt", $input . PHP_EOL, FILE_APPEND);

// ОБЯЗАТЕЛЬНО ответить 200
http_response_code(200);