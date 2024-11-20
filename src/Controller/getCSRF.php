<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');
$_SESSION["csrf"] = random_int(1,999999);
echo '{"csrf": "' . $_SESSION["csrf"] . '"}';
