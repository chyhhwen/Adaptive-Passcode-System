<?php
include "../lib/database.php";
$sql = new sql();
@$u = $_POST['user'];
@$p = $_POST['pass'];
$sql -> config("root","","temp","menber");
$sql -> put_data(["id","name","user","pass","time"]);
echo $url = str_replace('/defense','',$_SERVER['REQUEST_URI']);
/*if(@$sql->login_check($u,$p))
{
    echo '登入成功';

}
else
{
    header('Location: http://localhost/');
}*/
?>