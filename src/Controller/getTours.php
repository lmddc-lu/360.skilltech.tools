<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Tour;
use \tour\Repository\TourRepository;
session_start();
$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
header('Content-Type: application/json; charset=utf-8');

if ($user) {
  $repo = new TourRepository();
  $tour = $repo->getAllByUserID($user->getId());
  echo json_encode($tour);
} else {
  echo "{\"error\": \"user not connected\"}";
}
