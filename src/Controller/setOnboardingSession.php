<?php
namespace tour\Controller;

/*
 * This script decline the onboarding the the length of the session
 */

require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;

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

if ($value != 0 && $value != 1){
    exit("{\"error\": \"Unexpected value\"}");
}

$user->setOnboarding($value);
exit(json_encode($user));
