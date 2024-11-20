<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Spot;
use \tour\Entity\SpotHasSpot;
use \tour\Repository\SpotRepository;
use \tour\Repository\SpotHasSpotRepository;
use \tour\Repository\TourRepository;
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$spot1ID = isset($_POST["spot1"]) ? intval($_POST["spot1"]) : 0;
$spot2ID = isset($_POST["spot2"]) ? intval($_POST["spot2"]) : 0;
$spot1x = isset($_POST["spot1x"]) ? intval($_POST["spot1x"]) : null;
$spot1y = isset($_POST["spot1y"]) ? intval($_POST["spot1y"]) : null;
$spot1t = isset($_POST["spot1t"]) ? intval($_POST["spot1t"]) : 0;
$spot2x = isset($_POST["spot2x"]) ? intval($_POST["spot2x"]) : null;
$spot2y = isset($_POST["spot2y"]) ? intval($_POST["spot2y"]) : null;
$spot2t = isset($_POST["spot2t"]) ? intval($_POST["spot2t"]) : 0;

// Check that the target values are 0 or 1
$spot1t = ($spot1t == 0 || $spot1t == 1) ? $spot1t : 0;
$spot2t = ($spot2t == 0 || $spot2t == 1) ? $spot2t : 0;

// Verify the presence of the Spots IDs
if ( $spot1ID < 0 || $spot2ID < 0 || $spot1ID === $spot2ID ) {
  exit ("{\"error\": \"Bad Spot ID\"}");
}

// Sort the Spots by IDs
if ($spot1ID > $spot2ID){
  $spot1IDbak = $spot1ID;
  $spot1xbak = $spot1x;
  $spot1ybak = $spot1y;
  $spot1tbak = $spot1t;

  $spot1ID = $spot2ID;
  $spot2ID = $spot1IDbak;

  $spot1x = $spot2x;
  $spot2x = $spot1xbak;
  
  $spot1y = $spot2y;
  $spot2y = $spot1ybak;
  
  $spot1t = $spot2t;
  $spot2t = $spot1tbak;
}

/*
 * Normalize the values between -$limit and $limit
 */
function crop($value, $limit){
  $limit = abs($limit);
  while($value < -$limit){
    $value += 2*$limit;
  }
  while ($value > $limit){
    $value -= 2*$limit;
  }
  return $value;
}

// Crop the values of the coordinates
$spot1x = crop($spot1x, 1800);
$spot2x = crop($spot2x, 1800);
$spot1y = crop($spot1y, 900);
$spot2y = crop($spot2y, 900);

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}

// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}

$spotRepo = new SpotRepository();
$spots = [$spotRepo->find($spot1ID, $user->getID()), $spotRepo->find($spot2ID, $user->getID())];

if (!$spots[0] || !$spots[1]){
  exit("{\"error\": \"Spot not found\"}");
}
$spotHasSpotRepo = new SpotHasSpotRepository();
$spotHasSpot = $spotHasSpotRepo->findOneBy(["spot1" => $spot1ID, "spot2" => $spot2ID]);

if ($spotHasSpot){
  exit("{\"error\": \"Spots are already connected\"}");
}

$spotHasSpot = new SpotHasSpot();

$spotHasSpot->setSpot1($spot1ID);
$spotHasSpot->setSpot2($spot2ID);
$spotHasSpot->setSpot1x($spot1x);
$spotHasSpot->setSpot1y($spot1y);
$spotHasSpot->setSpot2x($spot2x);
$spotHasSpot->setSpot2y($spot2y);
$spotHasSpot->setSpot1t($spot1t);
$spotHasSpot->setSpot2t($spot2t);

$result = $spotHasSpotRepo->persist($spotHasSpot);
//~ unset($_SESSION['csrf']);

if (!$result){
  exit("{\"error\": \"Spots connection not saved into the database\"}");
}

echo json_encode($spotHasSpot);
