<?php
date_default_timezone_set('Asia/Taipei');
header('Content-type:application/json;charset=utf-8');
header('Access-Control-Allow-Origin: *');
$filename = time().'sql_del.json';
$fp = fopen($filename, 'w');
fwrite($fp,json_encode([
    $_POST
],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
fclose($fp);
?>