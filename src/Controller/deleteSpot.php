<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Spot;
use \tour\Entity\Sky;
use \tour\Entity\POI;
//use \tour\Entity\Image;
use \tour\Repository\SkyRepository;
use \tour\Repository\SpotRepository;
use \tour\Repository\POIRepository;
use \tour\Repository\TourRepository;
use \tour\Repository\SpotHasSpotRepository;
use \tour\Repository\ImageRepository;
require_once(__DIR__."/../Functions/deleteFile.php");
require_once(__DIR__."/../Functions/deleteTiles.php");

session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$spotId = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

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

$skyRepo = new SkyRepository();
$skies = $skyRepo->findAllBy(["spot_id" => $spot->getID()]);

$imageRepo = new imageRepository();

// Deletes all related skies and files
foreach ($skies as $sky){
  $skyRepo->delete($sky->getID());
  $image = $imageRepo->find($sky->getImageID());
  if ($image){
    deleteFile(__DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype());
    deleteFile(__DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype());
    deleteTiles($image->getFilename() . "." . $image->getFiletype());
  }
  $imageRepo->delete($image->getId());
}

// Deletes all related POIs and Images and files
$poiRepo = new POIRepository();
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

// Deletes all related SpotHasSpots

$sHSRepo = new SpotHasSpotRepository();
$sHSs = $sHSRepo->findAllBy(["spot1" => $spot->getID()]);
foreach ($sHSs as $sHS){
  $sHSRepo->delete($sHS->getID());
}
$sHSs = $sHSRepo->findAllBy(["spot2" => $spot->getID()]);
foreach ($sHSs as $sHS){
  $sHSRepo->delete($sHS->getID());
}

$spotRepo->delete($spot->getID());

//~ unset($_SESSION['csrf']);
exit("{\"success\": \"Spot deleted\"}");
