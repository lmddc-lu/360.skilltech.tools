<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\POI;
use \tour\Entity\Image;
use \tour\Repository\POIRepository;
use \tour\Repository\SpotRepository;
use \tour\Repository\ImageRepository;
//~ use \tour\Functions;
//~ require_once(__DIR__."/../Functions/saveSkyImages.php");
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../Functions/saveFile.php");
require_once(__DIR__."/../Functions/deleteFile.php");
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$poiId = isset($_POST["id"]) ? intval($_POST["id"]) : null;
$spotId = isset($_POST["spot_id"]) ? intval($_POST["spot_id"]) : null;
$x = isset($_POST["x"]) ? intval($_POST["x"]) : null;
$y = isset($_POST["y"]) ? intval($_POST["y"]) : null;
$layer = isset($_POST["layer"]) ? intval($_POST["layer"]) : null;
$template = isset($_POST["template"]) ? intval($_POST["template"]) : null;
$deleteImage = isset($_POST["delete_image"]) ? intval($_POST["delete_image"]) : null;

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}
// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}
// Verify the presence of the POI ID
if ( !$poiId && !$spotId) {
  exit ("{\"error\": \"Bad POI id\"}");
}

$poiRepo = new POIRepository();
if ($poiId > 0) {

  $poi = $poiRepo->findOneBy(["id" => $poiId], $user->getID());
  // Verify if the POI ID exists
  if ( !$poi ) {
    exit ("{\"error\": \"Poi not found\"}");
  }
} else {
  $poi = new POI();
}

$poi->setIcon($_POST["icon"]);
$poi->setText($_POST["text"]);
$poi->setTitle($_POST["title"]);
$poi->setX($x);
$poi->setY($y);
$poi->setLayer($layer);
$poi->setTemplate($template);

if ($spotId){
  $spotRepo = new SpotRepository();
  $spot = $spotRepo->find($spotId, $user->getID());
  if ($spot){
    $poi->setSpotID($spot->getID());
  } else {
    exit ("{\"error\": \"Spot not found\"}");
  }
}

$result = $poiRepo->persist($poi);

if (!$result){
  exit("{\"error\": \"POI not saved into the database\"}");
}

if (isset($_FILES['file'])){
  // Save the new POI's image
  $imageRepo = new ImageRepository();
  $image = $imageRepo->find($poi->getImageID(), $user->getId());
  if (!$image){
    $image = new Image();
    $image->setUserID($user->getID());
  }
  $oldFilename = $image->getFilename();
  $oldFiletype = $image->getFiletype();
  // Init the filename or set a new filename if already set
  $image->setFilename(true);
  // Save the image file and thumb
  if (saveFile($image,
    [
      'file' => $_FILES['file'],
      'maxlength' => SKY_LENGTH,
      'maxwidth'  => SKY_WIDTH,
      'maxheight' => SKY_HEIGHT,
      'setinfos' => true,
      'path'      => __DIR__ . "/../../html/data/image/"
    ]
  )){
    // The file is saved, we record it into the database
    $result = $imageRepo->persist($image);
    if ($result){
      // Image object saved, we delete the old image file then update the POI object
      if ($oldFilename && $oldFiletype){
        deleteFile(__DIR__ . "/../../html/data/image/" . $oldFilename . "." . $oldFiletype);
      }
      $poi->setImageID($image->getID());
      $result = $poiRepo->persist($poi);
    }
  }
} elseif($deleteImage == 1) {
  $imageRepo = new ImageRepository();
  $image = $imageRepo->find($poi->getImageID(), $user->getId());
  if ($image){
    $oldFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
    if (file_exists($oldFile)){
      unlink($oldFile);
    }
    $poi->setImageID(null);
    $result = $poiRepo->persist($poi);
    $imageRepo->delete($image->getID());
  }
}

exit (json_encode($poi));
