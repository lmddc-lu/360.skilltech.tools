<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . "/../Autoloader.php";
require_once __DIR__ . "/../config.php";

use Jumbojett\OpenIDConnectClient;
use \tour\Entity\User;
use \tour\Repository\UserRepository;

session_start();

if (isset($_SESSION["user"])){
  header('Location: ' . "/");
  exit();
}

$oidc = new OpenIDConnectClient(OIDC_ID_PROVIDER,
                                OIDC_CLIENT_ID,
                                OIDC_CLIENT_SECRET);
$oidc->addScope(['profile', 'email']);
$oidc->setRedirectUrl("https://360.skilltech.tools/oidc.php");
$oidc->setRedirectUrl(REDIRECT_PROTOCOL . '://' . $_SERVER['HTTP_HOST'] . '/oidc.php');

try {
  $oidc->authenticate();
  $userInfo = $oidc->requestUserInfo();
} catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), "\n";
}

if (isset($userInfo)){
  // User is connected to the OIDC provider
  $repo = new UserRepository();
  $user = $repo->findByEmail($userInfo->email);
  if ($user == null) {
    $user = $repo->create($userInfo);
  }

  $_SESSION["user"] = $user;
} else {
  // User not connected
  unset($_SESSION["user"]);
}

header('Location: ' . "/");
