<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Tour;
use \tour\Repository\TourRepository;
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$tourId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

if (!$user) {
  exit("{\"error\": \"User not connected\"}");
}

if ($tourId == 0) {
    exit("{\"error\": \"Bad id\"}");
}

$repo = new TourRepository();
$size = $repo->getSize($tourId);
if ($size){
  exit("{\"size\": \"$size\"}");
} else {
  exit("{}");
}
