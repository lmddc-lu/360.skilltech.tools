<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;

@session_start();
//echo var_dump($_SESSION);

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
if (!$user) exit();

$tours = $user->getTours();
echo "<br><br>".$user->getEmail()." - ".$user->getDirectory();
echo "<br><br>";

foreach ($tours as $tour){
    echo "<br>".$tour->getTitle();
}
