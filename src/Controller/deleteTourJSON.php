<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\Tour;
use \tour\Repository\TourRepository;
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$tourId = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

// Verify the presence of the tour ID
if ( $tourId <= 0 ) {
  exit ("{\"error\": \"Bad Tour ID\"}");
}

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}

// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}

//Deletes the cached tour JSON
$tourRepo = new TourRepository();
$tour = $tourRepo->find($tourId, $user->getID());
if($tour){
  $filename = __DIR__ . "/../../html/data/tour/" . $tour->getFilename() . ".json";
  if (file_exists($filename)){
    if (unlink($filename)){
      exit("{\"success\": \"File deleted\"}");
    }
    exit("{\"error\": \"File not deleted\"}");
  }
  exit("{\"warning\": \"File not found\"}");
}
exit("{\"error\": \"Tour not found\"}");
