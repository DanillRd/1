<?php
// Этот файл предназначен для обработки запросов на вывод средств пользователей из системы.
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include_once $_SERVER['DOCUMENT_ROOT']."/engine/cfg.php";
include_once $_SERVER['DOCUMENT_ROOT']."/engine/ini.php";

    if(isset($_REQUEST['return_id'])){
    $output_id = $_REQUEST['return_id'];
    $sql = "select * from output where status = 0 and id = '".$output_id."'";
    $order_row = $db->get_row($sql);

    if($db->run("UPDATE `users` SET `balance` = `balance` + ".$order_row['sum']." where `login` = '".$order_row['login']."' limit 1")){
        $db->run("UPDATE `output` SET `status` = 2  where `id` = '".$order_row['id']."' limit 1");
        echo "Заявка отменена №".$_REQUEST['return_id'];
    }
    die();

}   elseif(isset($_REQUEST['out_id'])){
    $output_id = $_REQUEST['out_id'];
    $sql = "select * from output where status = 0 and id = $output_id";
    $order_row = $db->get_row($sql);
}
    else{
    $sql = "select * from output where status = 0 and sum_out < 10000 and user_outs < (user_pays * 1.1) ORDER BY RAND() LIMIT 1";
    //select * from output where status = 0 and sum_out < 10500 ORDER BY RAND() LIMIT 1
    $order_row = $db->get_row($sql);
    $output_id = $order_row['id'];
}
// Варианты для вывода средств
$currency='rub'; //рубль
    if($order_row) {
    if (strpos($order_row['ps'], 'QDT')) {
        $psystem = '101';
        $schet_nomer = str_ireplace("QDT", "", $order_row['ps']);
    } else if (strpos($order_row['ps'], 'QSTN')) {
        $psystem = '102';
        $schet_nomer = str_ireplace("QSTN", "", $order_row['ps']);
    } else {
        echo "Сеть платежей не определена, выберите сеть " . $order_row['ps'];
        die();
    }
}
    function getConversionRateFromAPI($symbol) {        // Функция для получения актуального курса
    global $coingeckoApiUrl;

    $url = $coingeckoApiUrl . "?ids=$symbol&vs_currencies=usd";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    return isset($data[$symbol]['usd']) ? $data[$symbol]['usd'] : null;
}

    $amount = $_REQUEST['AMOUNT'];
    $convertedAmountUSDT = $amount / $usdToRubExchangeRate;     // Пересчет рублей в доллары
if ($convertedAmountUSDT == null) {
    echo "Ошибка пересчета рублей в USDT";
    die();
}

    $qdtConversionRate = getConversionRateFromAPI('qchain-qdt');    // Делаем пересчет в токены
if ($qdtConversionRate == null) {
    echo "Ошибка получения курса QDT монеты";
    die();
}

// Рассчитываем количество QDT монет
    $convertedAmountQDT = $convertedAmountUSDT / $qdtConversionRate;
if ($convertedAmountQDT == null) {
    echo "Ошибка расчета количества QDT монет";
    die();
}

if ($psystem === '101') {
    $conversionSymbol = 'qchain-qdt';
    $convertedAmountCrypto = convertToUSDT($convertedAmountUSDT, $conversionSymbol);
} elseif ($psystem === '102') {
    $convertedAmountCrypto = $convertedAmountUSDT;  // Конвертация USDT в QSTN (если 1 QSTN = 1 USD) // Указано на проекте!(Не биржевой токен)
}

$data = array(
    'wallet_id'=>$merchant_id, // Не обязательный пункт
    'purse'=>$schet_nomer, // В данном случае будет адрес кошелька
    'amount'=>$order_row['summ_out'],
    'desc'=>$order_row['inv_code'],
    'currency'=>$psystem,
    'action'=>'cashout',
);

    $amount = $order_row['summ_out'];
    $desc = $order_row['inv_code'];
    $email = $order_row['login'] . "@mail.ru";

$answer = "ok"; // Заглушка для создания заявки

if ($answer == 'ok') {
    echo "Заявка создана ";
    echo $data['desc'];

    // Обновление статуса и реферальные действия

    $sql = "update output SET status = 3 WHERE id = $output_id";
    $db->run($sql);

    $user_row = $db->get_row("select * from `users` where `login` = '".$order_row['login']."'");
    if($user_row['http_referer'] == "gamblingpro"){
        $rev_sum = round($order_row['sum_out'] * 0.2);
        $otvet = file_get_contents("https://pb.gambling.pro/rev?hit_id=".$user_row['ref_id']."&act=revshare&module=vulkan14&secret_code=PK0p0QjVqITzsTQi&rev_sum=-".$rev_sum."&currency=rub");
    } elseif($user_row['http_referer'] == "advertiserunet") {
        $rev_sum = round($order_row['sum'] * 0.2);
        $otvet = file_get_contents("http://advertiseru.net/postback/072b13f72d1a426b/?token=c0e30676206818c00541788877a86d49&uid=".$user_row['ref_id']."&order_id=".$order_row['inv_code']."&payout=-".$rev_sum);
    }

    $log_message = "Заявка создана: " . $data['desc'] . "\n";
    $log_message .= "Статус обновлен в базе данных.\n";
    $log_message .= "Реферальные действия обработаны.\n";
    $log_message .= "Ответ от gamblingpro или advertiserunet: " . $otvet . "\n";
    file_put_contents('cashout_log.txt', $log_message, FILE_APPEND);

} else {
    print_r($request);
    echo $json["message"]."<br>";
    echo $json["result"]."<br><br><br><br>";
    print_r($json);

    $log_message = "Ошибка при создании заявки: " . $data['desc'] . "\n";
    file_put_contents('cashout_log.txt', $log_message, FILE_APPEND);
}
?>