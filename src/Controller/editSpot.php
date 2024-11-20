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
//~ use \tour\Functions;
require_once(__DIR__."/../Functions/saveFile.php");
require_once(__DIR__."/../Functions/saveTiles.php");
require_once(__DIR__."/../Functions/deleteFile.php");
require_once(__DIR__."/../Functions/deleteTiles.php");

session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$spotId = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

// Test for img files. Image may be missing in case of edition of existing Sky
if( isset($_POST['url']) && $_POST['url'] && isset($_FILES['file']) ) {
  exit("{\"error\": \"Cannot handle a URL and an Image File at the same time\"}");
}
// Verify the CSRF token
if (!isset($_SESSION['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}
// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}
// Verify the presence of the Spot ID
if ( $spotId < 0 ) {
  exit ("{\"error\": \"Bad Spot id\"}");
}

$spotRepo = new SpotRepository();
$spot = $spotRepo->find($spotId);
// Verify if the Spot ID exists
if ( !$spot ) {
  exit ("{\"error\": \"Spot not found\"}");
}

$tourRepo = new TourRepository();
$tour = $tourRepo->find($spot->getTourID());

// Verify the user rights
if ( !$tour || $tour->getUserID() !== $user->getID() ){
  exit("{\"error\": \"User not allowed\"}");
}

if (isset($_POST['title'])) $spot->setTitle($_POST['title']);
if (isset($_POST['lat'])) $spot->setLat((float)$_POST['lat']);
if (isset($_POST['lng'])) $spot->setLng((float)$_POST['lng']);

$result = $spotRepo->persist($spot);

if (!$result){
  exit("{\"error\": \"Spot not saved into the database\"}");
}

/*
 * Sky may only be sent in simplified mode where a spot
 * will only have one sky. Then, if a sky URL or image is sent
 * we consider it is for the layer 0
 */
$skyRepo = new SkyRepository();
$sky = $skyRepo->findOneBy(["spot_id" => $spot->getID(), "layer" => 0]);
if (!$sky) {
  $sky = new Sky();
  $sky->setSpotID($spot->getID());
}

if (isset($_FILES['tile'], $_FILES['thumb'], $_FILES['preload'])){
  // Save the new uploaded sky
  $imageRepo = new ImageRepository();
  $image = $imageRepo->find($sky->getImageID());
  if (!$image) {
    $image = new Image();
  }
  $oldFilename = $image->getFilename();
  $oldFiletype = $image->getFiletype();

  $image->setUserID($user->getID());
  $image->setFilename(true);

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

    // The file is saved, we delete the old image
    deleteFile(__DIR__ . "/../../html/data/image/" . $oldFilename . "." . $oldFiletype);
    deleteFile(__DIR__ . "/../../html/data/image/thumb/" . $oldFilename . "." . $oldFiletype);
    deleteTiles($oldFilename . "." . $oldFiletype);

    // The file is saved, now we record it into the database
    $result = $imageRepo->persist($image);
    if ($result){
      $sky->setImageID($image->getID());
    }
  }
}

$sky->setLayer(0);
$result = $skyRepo->persist($sky);

if (!$result){
  exit("{\"error\": \"Sky not saved into the database\"}");
}

//~ unset($_SESSION['csrf']); 
echo json_encode($spot);
