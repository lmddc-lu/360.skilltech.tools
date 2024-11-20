<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Spot;
use \tour\Repository\SpotRepository;
session_start();
$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
header('Content-Type: application/json; charset=utf-8');

$spotId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ( $user and $spotId>0 ) {
  $repo = new SpotRepository();
  $spot = $repo->find($spotId, $user->getID());
  if ($spot){
    exit(json_encode($spot));
  } else {
    exit("{\"error\": \"Spot not found\"}");
  }
} elseif (!$user) {
  exit("{\"error\": \"User not connected\"}");
} else {
  exit("{\"error\": \"Bad id\"}");
}
