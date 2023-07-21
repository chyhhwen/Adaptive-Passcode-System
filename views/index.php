<?php
return"
    <body>
        <form action='../defense/xss.php' method='post'>
            <div class=\"login-page\">
                <div class='put'>
                    <span>帳號</span>
                    <input type='text' name='user'>
                    <span>密碼</span>
                    <input type='password' name='pass'>
                    <input type='submit' name='login'>
                </div>
            </div>
        </form>
    </body>
";
?>