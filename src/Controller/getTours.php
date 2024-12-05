<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Tour;
use \tour\Repository\TourRepository;
session_start();
$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
header('Content-Type: application/json; charset=utf-8');

function isPassword($tour){
  $tour["password"] = $tour["password"] === null ? false : true;
  return $tour;
}

if ($user) {
  $repo = new TourRepository();
  $tour = $repo->getAllByUserID($user->getId());
  $tour = array_map('isPassword', $tour);
  echo json_encode($tour);
} else {
  echo "{\"error\": \"user not connected\"}";
}
