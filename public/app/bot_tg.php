<?php

define("TG_TOKEN", "8477216590:AAEsjTTHuO76KvPi9mDe27dPntIg3nQn-IY");
define("TG_USER_ID", -1003660883702);

// if($_SERVER['REQUEST_METHOD'] === 'POST'){
//     $input = file_get_contents('php://input');

//     $data = json_decode($input, true);

//     $userData = $data['userData'];

//     $selectedAssistant = $data['selectedAssistant'];

//     $userDataText = "<b>- Данные пользователя </b> \n" . 
//                 "<b>Имя : </b>" . $userData['name'] . "\n" .
//                 "<b>Фамилия : </b>" . $userData['surname'] . "\n" .
//                 "<b>Email : </b>" . $userData['email'] . "\n \n" ;

//     $userQuestion = "<b>- Вопрос пользователя : </b> \n" . $data['messageUser'] . "\n \n";
//     $AiResponse = "<b>- Ответ ИИ : \n </b>" . $data['messageAi'] . "\n \n";

//     $userAiQuestion = "<b>- Переделанный вопрос (ИИ) : </b> \n" . $data['messageReview'] . "\n \n";

//     $date = "<b>- Дата : </b>" . $data['date'];

//     $message = $userDataText . $userQuestion . $userAiQuestion . $AiResponse . $date;

//     $Query = array(
//         "chat_id" => TG_USER_ID,
//         "text" => $message,
//         "parse_mode" => "html"
//     );

//     $ch = curl_init("https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage");

//     curl_setopt($ch, CURLOPT_POST, 1);
//     curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($Query));

//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
//     curl_setopt($ch, CURLOPT_HEADER, true);

//     $resultQuery = curl_exec($ch);

//     header('Content-Type: application/json');

//     echo json_encode([
//         'status' => 'success',
//         'message' => "сообщение отправлено в telegram"
//     ]);
// }



function writeLogFile($string, $clear = false){
    $log_file_name = __DIR__."/message.txt";
    $now = date("Y-m-d H:i:s");
    if($clear == false) {
        file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n", FILE_APPEND);
    } else {
        file_put_contents($log_file_name, $now." ".print_r($string, true)."\r\n");
    }
}

$data = file_get_contents('php://input');
$data = json_decode($data, true);

// writeLogFile($data, true);

if(!empty($data["message"]["photo"])){
    $last_photo = end($data["message"]["photo"]);

    $file_id = $last_photo['file_id'];

    $getQuery = array(
        "file_id" => $file_id,
    );

    $ch = curl_init("https://api.telegram.org/bot" . TG_TOKEN . "/getFile?" . http_build_query($getQuery));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $resultQuery = curl_exec($ch);

    /* записываем ответ в формате PHP массива */
    $arrDataResult = json_decode($resultQuery, true);

    if(empty($arrDataResult["result"]["file_path"])) {
        exit("Ошибка: нет file_path");
    }

    /* записываем URL необходимого изображения */
    $fileUrl = $arrDataResult["result"]["file_path"];

    /* формируем полный URL до файла */
    $photoPathTG = "https://api.telegram.org/file/bot". TG_TOKEN . "/" . $fileUrl;

    $dir = __DIR__ . "/img";
    if(!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    /* забираем название файла */
    $arrFilePath = explode("/", $fileUrl);
    $filename = end($arrFilePath);

    $newFilePath = $dir . "/" . $filename;

    $ch = curl_init($photoPathTG);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $imgData = curl_exec($ch);

    // 8. Сохраняем
    file_put_contents($newFilePath, $imgData);
}

    // ------------------------- сообщение с кнопкой -----------------------------//

// $Query = array(
//     "chat_id" => TG_USER_ID,
//     "text" => "Test сообщения с кнопкой!",
//     "reply_markup" => json_encode(
//         array(
//             "inline_keyboard" => array(
//                 array(
//                     array(
//                         "text" => "Button 1",
//                         "callback_data" => "test_1"
//                     ),
//                     array(
//                         "text" => "Buttom 2",
//                         "callback_data" => "test_2"
//                     ),
//                 )
//             )
//         )
//     )
// );

// $ch = curl_init("https://api.telegram.org/bot" . TG_TOKEN . "/sendMessage?" . http_build_query($Query));

// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
// curl_setopt($ch, CURLOPT_HEADER, true);

// $resultQuery = curl_exec($ch);