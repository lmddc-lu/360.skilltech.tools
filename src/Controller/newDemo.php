<?php
namespace tour\Controller;
/*
 * Copies the default demo tour to the user's collection
 */
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Spot;
use \tour\Entity\SpotHasSpot;
use \tour\Entity\Tour;
use \tour\Entity\Sky;
use \tour\Entity\Image;
use \tour\Entity\POI;
use \tour\Repository\SkyRepository;
use \tour\Repository\SpotRepository;
use \tour\Repository\SpotHasSpotRepository;
use \tour\Repository\TourRepository;
use \tour\Repository\ImageRepository;
use \tour\Repository\POIRepository;
require_once(__DIR__."/../Functions/copyImage.php");
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || !isset($_POST['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}
// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}

$tourRepo = new TourRepository();
$imageRepo = new ImageRepository();
$tour = $tourRepo->find(DEMO_TOUR_ID);
// Verify if the Tour ID exists
if ( !$tour ) {
  exit ("{\"error\": \"Tour not found\"}");
}
$demoTourId=$tour->getID();
$tour->setFilename(true);
$tour->setUserID($user->getID());
$tour->setCreationDate(DEMO_CREATION_DATE);
$tour->setModificationDate(DEMO_CREATION_DATE);

// Copy the thumbnail
$tourThumb = $imageRepo->find($tour->getThumbID());
copyImage($tourThumb, $imageRepo, $user);
$tour->setThumbID($tourThumb->getID());
$tourRepo->persist($tour, true);
// $tour is now a new object owned by the current user

$spotRepo = new SpotRepository();
$spots = $spotRepo->findAllBy(["tour_id" => DEMO_TOUR_ID]);
$newSpots = []; //Keys are the demo spot id, value are the new spot ID

// Copy the all the Spot entities
$skyRepo = new SkyRepository();
foreach ($spots as $spot){
  // The spot object will be modified later by reference
  $demoSpotId = $spot->getID();
  $spot->setTourID($tour->getID());
  $spot->setCreationDate(DEMO_CREATION_DATE);
  $spot->setModificationDate(DEMO_CREATION_DATE);
  $spotRepo->persist($spot, true);
  $newSpots[$demoSpotId] = $spot->getID();
  // Copy all the Sky entities and their Image entities
  $skies = $skyRepo->findAllBy(["spot_id" => $demoSpotId]);
  foreach ($skies as $sky){
    $demoImageId = $sky->getImageID();
    $image = $imageRepo->find($demoImageId);
    $demoFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
    $demoThumbFile = __DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype();
    $image->setFilename(true);
    $newFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
    $newThumbFile = __DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype();
    $image->setUserID($user->getID());
    $image->setCreationDate(DEMO_CREATION_DATE);
    try {
      $imageRepo->persist($image, true);
      if (!file_exists($newFile) && ! file_exists($newThumbFile)){
        copy($demoFile, $newFile);
        copy($demoThumbFile, $newThumbFile);
      }
    } catch(Exception $e){
      // image not saved, continue
    }
    
    $sky->setSpotID($spot->getID());
    $sky->setImageID($image->getID());
    $sky->setCreationDate(DEMO_CREATION_DATE);
    $skyRepo->persist($sky, true);
  }

}

// Copy the SpotHasSpot entities
$shsRepo = new SpotHasSpotRepository();
$spotHasSpots = $shsRepo->findAllByTourID($demoTourId);
foreach($spotHasSpots as $shs){
  if (isset($newSpots[$shs->getSpot1()]) && isset($newSpots[$shs->getSpot2()])){
    $newShs = new SpotHasSpot();
    $newShs->setSpot1($newSpots[$shs->getSpot1()]);
    $newShs->setSpot2($newSpots[$shs->getSpot2()]);
    $newShs->setSpot1x($shs->getSpot1x());
    $newShs->setSpot2x($shs->getSpot2x());
    $newShs->setSpot1y($shs->getSpot1y());
    $newShs->setSpot2y($shs->getSpot2y());
    $newShs->setSpot1t($shs->getSpot1t());
    $newShs->setSpot2t($shs->getSpot2t());
    $shsRepo->persist($newShs);
  }
}

// Copy the POI entities
$poiRepo = new POIRepository();
$pois = $poiRepo->findAllByTourID($demoTourId);
foreach($pois as $poi){
  $poiImage = $imageRepo->find($poi->getImageID());
  if ($poiImage){
    copyImage($poiImage, $imageRepo, $user);
    $poi->setImageID($poiImage->getID());
  }
  if (isset($newSpots[$poi->getSpotID()])){
    $poi->setSpotID($newSpots[$poi->getSpotID()]);
    $poi->setCreationDate(DEMO_CREATION_DATE);
    $poiRepo->persist($poi, true);
  }
}

// Reset the tour dates
$tourRepo->setDates($tour);

exit("{\"success\": \"Tour copied\", \"id\": " . $tour->getID() . "}");
