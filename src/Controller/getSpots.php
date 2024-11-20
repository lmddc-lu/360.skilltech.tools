<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Spot;
use \tour\Repository\SpotRepository;
session_start();
$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
header('Content-Type: application/json; charset=utf-8');

$tourId = isset($_GET["tour_id"]) ? intval($_GET["tour_id"]) : 0;
if ( $user and $tourId>0 ) {
  $repo = new SpotRepository();
  $spots = $repo->getAllByTourID($tourId, $user->getID());
  if ($spots){
    echo json_encode($spots);
  } else {
    echo "[]";
  }
} elseif (!$user) {
  echo "{\"error\": \"User not connected\"}";
} else {
  echo "{\"error\": \"Bad id\"}";
}
