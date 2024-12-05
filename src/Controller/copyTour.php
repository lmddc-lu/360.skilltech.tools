<?php
namespace tour\Controller;
/*
 * Copies the requested tour into the user's collection
 */
use \tour\Repository\TourRepository;
require_once(__DIR__ . "/../Autoloader.php");
require_once(__DIR__ . "/../Functions/copyTour.php");
require_once(__DIR__ . "/../config.php");
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$filename = isset($_POST["filename"]) ? $_POST["filename"] : null;
$password = isset($_POST["password"]) ? $_POST["password"] : null;

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || !isset($_POST['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}
// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}

// Check tour ID
if ( !$filename ) {
  exit("{\"error\": \"Empty filename\"}");
}

$tourRepo = new TourRepository();
$tour = $tourRepo->findOneBy(["filename" => $filename]);

// Check tour
if ( !$tour ) {
  exit("{\"error\": \"Bad tour ID\"}");
}

// Check share option
if ( !$tour->getShare() ) {
  exit("{\"error\": \"Not allowed\"}");
}

// Check password
if ( !$tour->verifyPassword($password) ) {
  exit("{\"error\": \"Not allowed\"}");
}

$result = copyTour($tour->getID(), $user);

exit("{\"success\": \"Tour copied\", \"id\": \"{$result}\"}");
