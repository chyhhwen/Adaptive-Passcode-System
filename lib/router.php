<?php
class router
{
    public function get($url)
    {
        if(@$this->check())
        {
            $temp = str_split($_SERVER['REQUEST_URI'],5);
            $use = "";
            for($i=1;$i<count($temp);$i++)
            {
                $use = $use.$temp[$i];
            }
            return $use;
        }
        else
        {
            switch($url)
            {
                case '/':
                    return require "./views/index.php";
                case '/defen':
                    include "./defense/login.php";
                    $login = new login();
                    if(@$_POST['user']!=NULL && @$_POST['pass']!=NULL)
                    {
                        if($login ->check(@$_POST['user'],@$_POST['pass']))
                        {
                            $_SESSION['index'] = true;
                        }
                        header('Location: http://localhost/');
                        exit();
                    }
                    else if(@$_POST['name1']!=NULL && @$_POST['user1']!=NULL && @$_POST['pass1']!=NULL)
                    {
                        echo 'register';
                    }
                    else
                    {
                        header('Location: http://localhost/');
                        exit();
                    }
                    break;
                case '/public':
                    http_response_code(404);
                    return require "./views/error.php";
                    die();
                default:
                    http_response_code(404);
                    return require "./views/error.php";
                    die();
            }
        }

    }
    public function check()
    {
        $temp = str_split($_SERVER['REQUEST_URI'],5);
        if($temp[0] == "/api/")
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    public function ref($a)
    {
        header('refresh:0;url="'.$a.'"');
    }
}
?>