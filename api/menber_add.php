<?php
    date_default_timezone_set('Asia/Taipei');
    header('Content-type:application/json;charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    $data = json_decode(file_get_contents('php://input'), true);
    include "../lib/database.php";
    include "../lib/http.php";
    $http = new http();
    $time = $http -> time();
    $sql = new sql();
    $sql -> config("root","","temp","menber");
    $sql -> put_data(['',$data['name'],$data['user'],$data['pass'],$time]);
    $sql -> add("(?,?,?,?,?)");
?>