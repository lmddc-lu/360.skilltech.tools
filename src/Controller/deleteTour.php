<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\Tour;
use \tour\Entity\User;
use \tour\Entity\Spot;
use \tour\Entity\SpotHasSpot;
use \tour\Entity\Sky;
use \tour\Entity\POI;
use \tour\Entity\Image;
use \tour\Repository\POIRepository;
use \tour\Repository\ImageRepository;
use \tour\Repository\SkyRepository;
use \tour\Repository\SpotRepository;
use \tour\Repository\TourRepository;
use \tour\Repository\SpotHasSpotRepository;
require_once(__DIR__."/../Functions/deleteFile.php");
require_once(__DIR__."/../Functions/deleteTiles.php");
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$tourId = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

/*
 * [x] Deletes the tour in the DB
 * [x] Deletes all linked spots, skies data and images
 * [ ] Deletes all linked POIs and images
 * [x] Deletes the thumbnail (file and DB)
 */

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
  exit ("{\"error\": \"Bad Tour ID\"}");
}

$tourRepo = new TourRepository();
$tour = $tourRepo->find($tourId);

// Verify the user rights
if ( !$tour || $tour->getUserID() !== $user->getID() ){
  exit("{\"error\": \"User not allowed\"}");
}

$spotRepo = new SpotRepository();
$spots = $spotRepo->findAllBy(["tour_id" => $tourId]);

$skyRepo = new SkyRepository();
$poiRepo = new POIRepository();
$imageRepo = new ImageRepository();
//~ $skies = $skyRepo->findAllBy(["spot_id" => $spot->getID()]);

// Deletes all SpotHasSpot and related Images
$shsRepo = new SpotHasSpotRepository();
$result = $shsRepo->deleteAllByTourID($tourId);

foreach ($spots as $spot){
  $skies = $skyRepo->findAllBy(["spot_id" => $spot->getID()]);
  // Deletes all related skies and files
  foreach ($skies as $sky){
    // First we have to delete the sky from the DB due to foreign key constraint
    $skyRepo->delete($sky->getID());
    $image = $imageRepo->find($sky->getImageID());
    if ($image){
      deleteFile(__DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype());
      deleteFile(__DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype());
      deleteTiles($image->getFilename() . "." . $image->getFiletype());
      $imageRepo->delete($image->getID());
    }
  }

  // Deletes all related POIs and Images and files
  $pois = $poiRepo->findAllBy(["spot_id" => $spot->getID()]);
  foreach ($pois as $poi){
    $image = $imageRepo->find($poi->getImageID());
    $poiRepo->delete($poi->getID());
    if ($image){
      $filePath = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
      if (file_exists($filePath)){
        unlink($filePath);
      }
      $imageRepo->delete($image->getID());
    }
  }
  
  $spotRepo->delete($spot->getID());
}

// Thumbnail
$ImageRepo = new ImageRepository();
$thumb = $ImageRepo->find($tour->getThumbID());
if($thumb){
  $thumbPath = __DIR__ . "/../../html/data/image/" . $thumb->getFilename() . "." . $thumb->getFiletype();
  if (file_exists($thumbPath)){
    unlink($thumbPath);
  }
  $ImageRepo->delete($thumb->getID());
}

// Delete the tour from DB
$tourRepo->delete($tour->getID());

//~ unset($_SESSION['csrf']);
exit("{\"success\": \"Tour deleted\"}");
