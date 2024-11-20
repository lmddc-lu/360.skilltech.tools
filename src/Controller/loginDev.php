<?php
require_once __DIR__ . "/../Autoloader.php";
require_once __DIR__ . "/../config.php";

use \tour\Entity\User;
use \tour\Repository\UserRepository;
session_start();

if (DEV !== true){
  exit("Prod");
}

if (
  isset($_POST['email']) && strlen($_POST['email']) >= 4 &&
  isset($_POST['csrf']) && $_POST['csrf'] == $_SESSION["csrf"]
){
  echo "Connection<br>";
  $repo = new UserRepository();
  $user = $repo->findByEmail($_POST['email']);
  if ($user == null) {
    $userInfo = new stdClass();
    $userInfo->email = $_POST['email'];
    $user = $repo->create($userInfo);
  }
  $_SESSION["user"] = $user;
} elseif (!isset($_POST['email']) || strlen($_POST['email']) < 4){
  echo "Bad email<br>";
} elseif (!isset($_POST['csrf']) || $_POST['csrf'] != $_SESSION["csrf"]){
  echo "Bad CSRF<br>";
}

$_SESSION["csrf"] = random_int(1,999999);
?>
<form method="post">
  <?php
  if(array_key_exists("user", $_SESSION)){ 
    echo "<p>Connected:  " . $_SESSION["user"]->getEmail() . " </p>";
  }
  ?>
  <input type=text value="demo" name="email">
  <input type="hidden" name="csrf" value="<?= $_SESSION["csrf"] ?>">
  <button type="submit">OK</button>
</form>
