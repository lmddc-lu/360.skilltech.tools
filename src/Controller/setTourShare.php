<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Tour;
use \tour\Repository\TourRepository;
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
$share = isset($_POST["share"]) ? intval($_POST["share"]) : null;
$password = isset($_POST["password"]) ? $_POST["password"] : null;

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}

// The share param must be set
if ($share !== 0 && $share !== 1) {
  exit("{\"error\": \"Invalid param\"}");
}

$tourRepo = new TourRepository();
$tour = $tourRepo->find($id);

// Verify the Tour and User
if ( !$tour || $tour->getUserID() !== $user->getID() ){
  exit("{\"error\": \"User not allowed\"}");
}

$tour->setShare($share);
$tour->setPassword($password);
$result = $tourRepo->persist($tour);

if (!$result){
  exit("{\"error\": \"Not saved\"}");
}

//Add the thumb filename to the tour JSON
$tour_array = $tour->jsonSerialize();
$tour_array["password"] = $tour_array["password"] == null ? false : true;
exit(json_encode($tour_array));
