<?php
return"
    <body>
        <div class=\"login-page\">
            <form action='/defen' method='post'>
                <div class='put' style=\"display:block\"> 
                    <span>帳號</span>
                    <input type='text' name='user' id='user'>
                    <span>密碼</span>
                    <input type='password' name='pass' id='pass'>
                    <input type='submit' name='login' value='登入'>
                    <input type='button' name='register' value='註冊' id='register'>
               </div>
            </form>
            <form action='/defen' method='post'>
                <div class='put1' style=\"display:none\" id='register'>
                    <span>名稱</span>
                    <input type='text' name='name1' id='name1'>
                    <span>帳號</span>
                    <input type='text' name='user1' id='user1'>
                    <span>密碼</span>
                    <input type='password' name='pass1' id='pass1'>
                    <span>確認</span>
                    <input type='password' id='repass1'>
                    <input type='submit' name='register' value='註冊'> 
                    <input type='button' name='return' value='返回' id='return'>
                </div>
            </form>
        </div>
    </body>
";
?>