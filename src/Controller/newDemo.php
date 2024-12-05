<?php
namespace tour\Controller;
/*
 * Copies the default demo tour to the user's collection
 */
require_once(__DIR__."/../Autoloader.php");
require_once(__DIR__."/../config.php");
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
require_once(__DIR__."/../Functions/copyTour.php");
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

$result = copyTour(DEMO_TOUR_ID, $user);

if ( !$result ) {
  exit("{\"error\": \"Tour not found\"}");
}
exit("{\"success\": \"Tour copied\", \"id\": " . $result . "}");
