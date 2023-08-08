<?php

include_once($_SERVER['DOCUMENT_ROOT'] .'/engine/cfg.php');
include_once($_SERVER['DOCUMENT_ROOT'] .'/engine/ini.php');
include 'config.php';
include 'out.php';


function getConversionRateFromAPI($symbol) {
    global $coingeckoApiUrl;

    $url = $coingeckoApiUrl . "?ids=$symbol&vs_currencies=usd";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    return isset($data[$symbol]['usd']) ? $data[$symbol]['usd'] : null;
}

function convertToUSDT($amount, $symbol) {
    $conversionRate = getConversionRateFromAPI($symbol);

    if ($conversionRate !== null) {
        return $amount * $conversionRate;
    } else {
        return null;
    }
}

if(empty($_REQUEST['AMOUNT'])){
    die('empty AMOUNT!');
}
$inv = $_REQUEST['MERCHANT_ORDER_ID'];

$sql="select * from enter where status < 2 and inv_code='$inv'";
$order_row=$db->get_row($sql);
sleep(1200); // 20m in sec
if($order_row)
{
    if($order_row['sum'] != $_REQUEST['AMOUNT'])
        $msg= "Never sum";
    elseif (pay($order_row['id'], true, get_Valute($_REQUEST['CUR_ID']))){
        $msg = "ok";

        if (!empty($platelshik['ref_id']) && $user_row['ref_id'] != $user_row['id']) {
            $d_referalu = round(($order_row['sum'] * $usdToRubExchangeRate) * ($ref_percent / 100));  // Реферальное начисление

            $db->run("update `users` set `balance` = `balance` + '" . $d_referalu . "' where id = '" . $platelshik['ref_id'] . "'");
            $ref_user_row = $db->get_row("SELECT * FROM `users` where id = '" . $platelshik['ref_id'] . "'");

            $db->run("INSERT INTO `enter` (`inv_code`, `login`, `sum`, `status`, `paysys`, `returned`, `ip`)
            VALUES ('" . mt_rand(10000000, 99999999) . "','" . $ref_user_row['login'] . "', '" . $d_referalu . "', '2', 'dep ref " . $platelshik['login'] . "', '2', '" . $_SERVER["REMOTE_ADDR"] . "');");

            $ref_text = "Получил от Реферала id:" . $platelshik['id'] . " бонус " . $d_referalu . " руб";
            $db->run("INSERT INTO `balance_log` (`user_id`, `user_login`, `user_balance`, `log`)
            VALUES ('" . $ref_user_row['id'] . "','" . $ref_user_row['login'] . "', '" . $ref_user_row['balance'] . "', '" . $ref_text . "');");
        }

        // Конвертация и запись в лог
        if ($order_row['paysys'] === 'QDT') {
            $conversionSymbol = 'qchain-qdt';
            $convertedAmount = convertToUSDT($order_row['sum'], $conversionSymbol);
        } elseif ($order_row['paysys'] === 'QSTN') {
            $convertedAmount = $order_row['sum']; // 1 QSTN = 1 USD
        }

        if ($convertedAmount !== null) {
            $timestamp = time();
            $logEntry = "$timestamp | {$order_row['sum']} {$order_row['paysys']} ($inv) -> $convertedAmount USDT | {$order_row['email']}" . PHP_EOL;
            file_put_contents('conversion_log.txt', $logEntry, FILE_APPEND);
        }

        $db->run("update `enter` set `status` = '2' where id = '".$order_row['id']."'");
    }else
        $msg = "Order not paying, please wait 20 minutes";
}
else
    $msg = "Not found order";

echo $msg;
?>
