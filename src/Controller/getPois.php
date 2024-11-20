<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
//~ use \tour\Entity\Spot;
//~ use \tour\Repository\SpotRepository;
use \tour\Repository\POIRepository;
session_start();
$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
header('Content-Type: application/json; charset=utf-8');

$spotID = isset($_GET["spot_id"]) ? intval($_GET["spot_id"]) : 0;
if ( $user and $spotID>0 ) {
  $repo = new POIRepository();
  $pois = $repo->getAllBySpotID($spotID, $user->getID());
  if ($pois){
    exit (json_encode($pois));
  } 
  exit ("[]");
} elseif (!$user) {
  echo "{\"error\": \"User not connected\"}";
} else {
  echo "{\"error\": \"Bad id\"}";
}
