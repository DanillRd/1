<?php
// Функцию для конвертации монет в USDT
--rjyabu
function getConversionRateFromAPI($symbol) {
    global $coingeckoApiUrl;

    $url = $coingeckoApiUrl . "?ids=$symbol&vs_currencies=usd";
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    return isset($data[$symbol]['usd']) ? $data[$symbol]['usd'] : null;
}
?>