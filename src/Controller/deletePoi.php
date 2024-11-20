<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\POI;
use \tour\Entity\Image;
//~ use \tour\Entity\Sky;
use \tour\Repository\POIRepository;
use \tour\Repository\ImageRepository;
//~ use \tour\Repository\TourRepository;
//~ use \tour\Repository\SpotHasSpotRepository;
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$poiId = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}
// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}
// Verify the presence of the POI ID
if ( $poiId < 0 ) {
  exit ("{\"error\": \"Bad POI id\"}");
}

$poiRepo = new PoiRepository();
$poi = $poiRepo->findOneBy(["id" => $poiId], $user->getId());

// Verify if the POI ID exists
if ( !$poi ) {
  exit ("{\"error\": \"POI not found\"}");
}

$poiRepo->delete($poi->getID());

// Delete the image
if($poi->getImageID()){

  $imageRepo = new ImageRepository();
  $image = $imageRepo->find($poi->getImageID());

  if ($image){
    $filePath = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
    if (file_exists($filePath)){
      unlink($filePath);
    }
    $imageRepo->delete($image->getID());
  }
}

//~ unset($_SESSION['csrf']);
exit("{\"success\": \"POI deleted\"}");
