<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Sky;
use \tour\Repository\SkyRepository;
session_start();
$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
header('Content-Type: application/json; charset=utf-8');

$spotId = isset($_GET["spot_id"]) ? intval($_GET["spot_id"]) : 0;
$tourId = isset($_GET["tour_id"]) ? intval($_GET["tour_id"]) : 0;
if ( $user and $spotId>0 ) {
  $repo = new SkyRepository();
  $skies = $repo->getAllBySpotID($spotId, $user->getId());
  if ($skies){
    exit(json_encode($skies));
  } else {
    echo "[]";
  }
} elseif ( $user and $tourId>0 ) {
  $repo = new SkyRepository();
  $skies = $repo->findAllByTourID($tourId, $user->getId());
  if ($skies){
    exit(json_encode($skies));
  } else {
    echo "[]";
  }
} elseif (!$user) {
  echo "{\"error\": \"User not connected\"}";
} else {
  echo "{\"error\": \"Bad id\"}";
}
