<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
session_start();
$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
header('Content-Type: application/json; charset=utf-8');

if ($user) {
  echo json_encode($user);
} else {
  echo "{\"error\": \"user not connected\"}";
}
