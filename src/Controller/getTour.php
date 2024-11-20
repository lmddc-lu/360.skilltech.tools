<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Tour;
use \tour\Repository\TourRepository;
use \tour\Repository\ImageRepository;
session_start();
$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
header('Content-Type: application/json; charset=utf-8');

$tourId = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
if ( $user and $tourId>0 ) {
  $repo = new TourRepository();
  $tour = $repo->findOneBy(array('id' => $tourId, 'user_id' => $user->getId()));
  $tour = $repo->find($tourId, $user->getId());
  if ($tour){
    //search for the thumbnail
    if($tour->getThumbID()){
      $tour_array = $tour->jsonSerialize();
      $imageRepo = new ImageRepository();
      $thumb = $imageRepo->find($tour->getThumbID(), $user->getId());
      if ($thumb){
        $tour_array["thumb_filename"] = $thumb->getFilename() . "." . $thumb->getFiletype();
      }
      exit(json_encode($tour_array));
    }
    exit(json_encode($tour));
  } else {
    exit("{\"error\": \"Tour not found\"}");
  }
} elseif (!$user) {
  exit("{\"error\": \"User not connected\"}");
} else {
  exit("{\"error\": \"Bad id\"}");
}
