<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");

use \tour\Repository\TourRepository;
require_once(__DIR__."/../Functions/deleteTour.php");

session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$tourId = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

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

deleteTour($tour);

exit("{\"success\": \"Tour deleted\"}");
