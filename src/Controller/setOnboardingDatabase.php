<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Repository\UserRepository;

session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$value = isset($_POST["value"]) ? intval($_POST["value"]) : null;

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || !isset($_POST['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}
// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}
// Value must be 0 or 1
if ($value != 0 && $value != 1){
    exit("{\"error\": \"Unexpected value\"}");
}

$user->setOnboarding($value);
$userRepo = new UserRepository();
if ($userRepo->persist($user)) {
  exit(json_encode($user));
}
exit("{\"error\": \"User not updated\"}");
