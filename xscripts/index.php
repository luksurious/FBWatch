<?php 
require_once 'setup.php';
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
<?php
require_once 'login.php';
?>
            <form name="input" action="parseProfile.php" method="get">
                Enter FB Username:<input type="text" id="username" name="username"><br>
            </form> 
    </body>
</html>
