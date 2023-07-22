<?php
date_default_timezone_set('Asia/Taipei');

include "../lib/router.php";
include "../lib/http.php";
include "../lib/txt.php";

$router = new router();
$http = new http();
$txt = new txt();
$url = str_replace('/defense','',$_SERVER['REQUEST_URI']);
switch($url)
{
    case '/login':
        return require "login.php";
    default:
        http_response_code(404);
        return require "../views/error.php";
        die();
}
?>