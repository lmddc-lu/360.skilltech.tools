<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Spot;
use \tour\Entity\Sky;
use \tour\Entity\Image;
use \tour\Repository\SkyRepository;
use \tour\Repository\SpotRepository;
use \tour\Repository\TourRepository;
use \tour\Repository\ImageRepository;
require_once(__DIR__."/../Functions/saveFile.php");
require_once(__DIR__."/../Functions/saveTiles.php");
require_once(__DIR__."/../Functions/deleteFile.php");
require_once(__DIR__."/../Functions/deleteTiles.php");
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$tourId = isset($_POST["tour_id"]) ? intval($_POST["tour_id"]) : 0;

// Test for img files. Image may be missing in case of edition of existing Sky
if( !isset($_FILES['tile'], $_FILES['thumb'], $_FILES['preload'])) {
  exit("{\"error\": \"Missing file or URL\"}");
}
// Verify the CSRF token
if (!isset($_SESSION['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}
// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}
// Verify the presence of the Tour ID
if ( $tourId < 0 ) {
  exit ("{\"error\": \"Bad Tour id\"}");
}

$tourRepo = new TourRepository();
$tour = $tourRepo->find($tourId);
// Verify the user
if ( !$tour || $tour->getUserID() !== $user->getID() ){
  exit("{\"error\": \"User not allowed\"}");
}

$spot = new Spot();

$spot->setTitle($_POST['title']);
$spot->setTourID((int)$_POST['tour_id']);
if (isset($_POST['lat']) && isset($_POST['lng'])){
  $spot->setLat((float)$_POST['lat']);
  $spot->setLng((float)$_POST['lng']);
}

$spotRepo = new SpotRepository();
$result = $spotRepo->persist($spot);

if (!$result){
  exit("{\"error\": \"Spot not saved into the database (1)\"}");
}

/*
 * Sky may only be sent in simplified mode where a spot
 * will only have one sky. Then, if a sky URL or image is sent
 * we consider it is for the layer 0
 */
$sky = new Sky();
$sky->setSpotID($spot->getID());
$sky->setLayer(0);

if (isset($_FILES['tile'], $_FILES['thumb'], $_FILES['preload'])){

  $imageRepo = new ImageRepository();
  $image = new Image();
  $image->setUserID($user->getID());
  $image->setFilename(true);
  // Test if a filename has been generated successfully
  if (!$image->getFilename()){
    if ($spot){
      $spotRepo->delete($spot->getID());
    }
    exit("{\"error\": \"Spot not saved into the database (2)\"}");
  }

  // Save the image file and thumb
  $resultTiles = false;
  $resultThumb = false;
  $resultPreload = false;
  $resultTiles = saveTiles($image,
    [
      'tiles' => $_FILES['tile'],
      'maxlength' => SKY_LENGTH,
      'maxwidth'  => SKY_WIDTH,
      'maxheight' => SKY_HEIGHT,
      'setinfos' => true       // To record the size (MB) of the tiles
    ]
  );
  if ($resultTiles){
    $resultThumb = saveFile($image,
      [
        'file' => $_FILES['thumb'],
        'maxlength' => SKY_THUMB_LENGTH,
        'maxwidth'  => SKY_THUMB_WIDTH,
        'maxheight' => SKY_THUMB_HEIGHT,
        'path'      => __DIR__ . "/../../html/data/image/thumb/"
      ]
    );
  }
  if ($resultThumb){
    $resultPreload = saveFile($image,
      [
        'file' => $_FILES['preload'],
        'maxlength' => SKY_PRELOAD_LENGTH,
        'maxwidth'  => SKY_PRELOAD_WIDTH,
        'maxheight' => SKY_PRELOAD_HEIGHT,
        'path'      => __DIR__ . "/../../html/data/image/",
        'setinfos' => true       // To record the width and height
      ]
    );
  }

  if ($resultTiles && $resultThumb && $resultPreload){
    // The file is saved, now we record it into the database
    $result = $imageRepo->persist($image);
    if ($result){
      $sky->setImageID($image->getID());
    }
  } else {
    // Error during the file recording, we delete all files already saved
    deleteFile(__DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype());
    deleteFile(__DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype());
    deleteTiles($image->getFilename() . "." . $image->getFiletype());
    // We delete the spot
    if ($spot){
      $spotRepo->delete($spot->getID());
    }
    exit("{\"error\": \"Spot not saved into the database (3)\"}");
  }
}

$result = false;
if($sky->getImageID()){
  $skyRepo = new SkyRepository();
  $result = $skyRepo->persist($sky);
}

if (!$result){
  exit("{\"error\": \"Spot not saved into the database (4)\"}");
}

echo json_encode($spot);
