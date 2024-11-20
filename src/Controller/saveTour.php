<?php
namespace tour\Controller;
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Tour;
use \tour\Entity\Image;
use \tour\Repository\TourRepository;
use \tour\Repository\ImageRepository;
require_once(__DIR__."/../config.php");
require_once(__DIR__."/../Functions/saveFile.php");
session_start();
header('Content-Type: application/json; charset=utf-8');

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
$startId = isset($_POST["start_id"]) ? intval($_POST["start_id"]) : 0;
$title = $_POST["title"];
$description = $_POST["description"];
$author = $_POST["author"];
$license = $_POST["license"];
$deleteImage = isset($_POST["delete_image"]) ? intval($_POST["delete_image"]) : null;

// Verify the CSRF token
if (!isset($_SESSION['csrf']) || $_POST['csrf'] != $_SESSION['csrf']) {
  exit("{\"error\": \"Invalid CSRF token\"}");
}

// Title must be at least one character long (grapheme)
if ( grapheme_strlen($title) < 1 ) {
  exit("{\"error\": \"Title is blank\"}");
}

// Verify the license
if (!in_array($license, LICENSES)){
  $license = "UNLICENSED";
}

$tourRepo = new TourRepository();

if ( $id <= 0 ) {
  // Creation of a new Tour
  $tour = new Tour();
  $tour->setTitle($title);
  $tour->setDescription($description);
  $tour->setAuthor($author);
  $tour->setLicense($license);
  $tour->setUserID($user->getID());
  $tour->setFilename();
  $result = $tourRepo->persist($tour);
  if (!$result){
    exit("{\"error\": \"Tour not created\"}");
  }
} else {
  // Modify a tour
  $tour = $tourRepo->find($id);

  // Verify the Tour and User
  if ( !$tour || $tour->getUserID() !== $user->getID() ){
    exit("{\"error\": \"User not allowed\"}");
  }

  $tour->setTitle($title);
  $tour->setDescription($description);
  $tour->setAuthor($author);
  $tour->setLicense($license);
  $tour->setStartID($startId > 0 ? $startId : null);
  $result = $tourRepo->persist($tour);

  if (!$result){
    exit("{\"error\": \"Tour not saved into the database\"}");
  }
}

$imageRepo = new ImageRepository();
$image = $imageRepo->find($tour->getThumbID(), $user->getId());

// Save the new thumbnail
if (isset($_FILES['file'])){
  $oldFilename = null;
  if (!$image){
    $image = new Image();
    $image->setUserID($user->getID());
  } else {
    $oldFilename = $image->getFilename();
    $oldFiletype = $image->getFiletype();
  }
  // We need a new filename in all cases to fix cache issue
  $image->setFilename(true);
  // Resizing and saving the image file
  if (saveFile($image, [
    'file' => $_FILES['file'],
    'maxlength' => TOUR_THUMB_LENGTH,
    'maxwidth'  => TOUR_THUMB_WIDTH,
    'maxheight' => TOUR_THUMB_HEIGHT,
    'setinfos'  => true,
    'path'      => __DIR__ . "/../../html/data/image/"
  ])){
    // The file is saved, now we record it into the database
    $result = $imageRepo->persist($image);
    if ($result){
      if ($oldFilename && $oldFiletype) {
        // We delete the former thumb
        $oldFile = __DIR__ . "/../../html/data/image/" . $oldFilename . "." . $oldFiletype;
        if (file_exists($oldFile)){
          unlink($oldFile);
        }
      }
      $tour->setThumbID($image->getID());
      $result = $tourRepo->persist($tour);
    }
  }
} elseif($deleteImage == 1) {
  if ($image){
    $oldFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
    if (file_exists($oldFile)){
      unlink($oldFile);
    }
    $tour->setThumbID(null);
    $result = $tourRepo->persist($tour);
    $imageRepo->delete($image->getID());
  }
}

//Add the thumb filename to the tour JSON
$tour_array = $tour->jsonSerialize();
if (!$deleteImage && isset ($image) && $image->getFilename()){
  $tour_array["thumb_filename"] = $image->getFilename() . "." . $image->getFiletype();
}

exit(json_encode($tour_array));
