<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Tour;
use \tour\Repository\TourRepository;
session_start();
$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
header('Content-Type: application/json; charset=utf-8');

$tourId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$query = isset($_GET["query"]) ? $_GET["query"] : null;
$save = isset($_GET["save"]) ? true : false;

// If a filemane has been given, we try to find it in database
if ($query){
  $repo = new TourRepository();
  $filename = (strrchr($query, "/"));
  $filename = substr($filename, 1, strlen($filename)-6);

  $repo = new TourRepository();
  $tour = $repo->findOneBy(["filename" => $filename]);
  if ($tour){
    $tourJSON = $tour->getJSON();
    $fileName = __DIR__ . "/../../html/data/tour/" . $tour->getFilename() . ".json";
    file_put_contents($fileName, $tourJSON);
    exit($tourJSON);
  } else {
    exit("{\"error\": \"Tour not found\"}");
  }
}

// If an id has been given, we generate the json only if user has needed rights
if ( $user and $tourId>0 ) {
  $repo = new TourRepository();
  //~ $tour = $repo->findOneBy(array('id' => $tourId, 'user_id' => $user->getId()));
  $tour = $repo->find($tourId, $user->getId());
  if ($tour){
    $tourJSON = $tour->getJSON();
    $fileName = __DIR__ . "/../../html/data/tour/" . $tour->getFilename() . ".json";
    file_put_contents($fileName, $tourJSON);
    exit($tourJSON);
  } else {
    echo "{}";
  }
} elseif (!$user) {
  echo "{\"error\": \"User not connected\"}";
} else {
  echo "{\"error\": \"Bad id\"}";
}
