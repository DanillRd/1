<?php

// Настройка адресов для депозитов магазина
$QDT_adress = '';
$QSTN_adress = '';

$symbol = "qchain-qdt";
$coingeckoApiUrl = "https://api.coingecko.com/api/v3/simple/price?ids=$symbol&vs_currencies=usd"; // Необходимо для получения акуально курса для конвертации QDT/USDT
$usdToRubExchangeRate = 95;                                                                         // Устанавливаем курс обмена для реферальных начислений после конвертации монет в USDT

?>