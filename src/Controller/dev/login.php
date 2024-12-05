<?php
require_once __DIR__ . "/../../Autoloader.php";
require_once __DIR__ . "/../../config.php";

use \tour\Entity\User;
use \tour\Repository\UserRepository;
session_start();

if (DEV !== true){
  exit("Prod");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Login as a developer</title>
<script>
  async function fetchGet(url){
    var response = new Object();
    try {
      response = await fetch(url,{
        method: "GET",
        mode: "cors",
        credentials: 'include',
        headers: {
          "Content-Type": "application/json",
        },
      });
    } catch (error) {
      console.log("error Get");
    }
    return response;
  }

  async function logout(){
    const response = await fetchGet("/logout.php");
    window.location = "/";
  }
</script>
</head>
<body>

<?php
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
} elseif (isset($_POST['email']) && strlen($_POST['email']) < 4){
  echo "Bad email<br>";
} elseif (isset($_POST['email']) && (!isset($_POST['csrf']) || $_POST['csrf'] != $_SESSION["csrf"])){
  echo "Bad CSRF<br>";
}

$_SESSION["csrf"] = random_int(1,999999);

if(array_key_exists("user", $_SESSION)){
  echo "<p>Connected as " . htmlspecialchars($_SESSION["user"]->getEmail()) . " </p>";
  echo "<a href='/'>Go to home page</a><br>";
  echo "<a href='#' onclick='logout()'>Log out</a>";
}
else {
?>

<form method="post">
  <label for="email">Login:</label>
  <input type=email id="email" value="demo@example.com" name="email">
  <input type="hidden" name="csrf" value="<?= $_SESSION["csrf"] ?>">
  <button type="submit">OK</button>
</form>

<?php
}
?>

</body>
</html>
