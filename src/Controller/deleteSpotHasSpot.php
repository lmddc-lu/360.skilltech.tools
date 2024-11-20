<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Repository\SpotHasSpotRepository;
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$sHSID = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}
// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}
// Verify the presence of the Tour ID
if ( $sHSID < 0 ) {
  exit ("{\"error\": \"Bad ID\"}");
}

$sHSRepo = new SpotHasSpotRepository();
$sHS = $sHSRepo->find($sHSID, $user->getID());

// Verify the user rights
if ( !$sHS ){
  exit("{\"error\": \"User not allowed\"}");
}

$sHSRepo->delete($sHSID);

//~ unset($_SESSION['csrf']);
exit("{\"success\": \"SpotHasSpot deleted\"}");
