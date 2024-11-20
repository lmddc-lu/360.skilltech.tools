<?php
require_once(__DIR__."/../Autoloader.php");
use \tour\Entity\User;
use \tour\Entity\Tour;
use \tour\Repository\TourRepository;
use \tour\Repository\ImageRepository;
require_once(__DIR__."/../Functions/tree.php");
session_start();

$user = isset($_SESSION["user"]) ? $_SESSION["user"] : null;
$tourId = isset($_GET["tour_id"]) ? intval($_GET["tour_id"]) : 0;

// User must be connected
if ( !$user ) {
  exit("{\"error\": \"User not connected\"}");
}
// Verify the presence of the Tour ID
if ( $tourId <= 0 ) {
  exit ("{\"error\": \"Bad Tour id\"}");
}

$repo = new TourRepository();
$tour = $repo->find($tourId, $user->getID());

if ( !$tour ) {
  exit ("{\"error\": \"Access denied\"}");
}

if($tour) {
  $tourJSON = $tour->getJSON();
} else {
  exit ("{\"error\": \"Not found\"}");
}

$images = $repo->getImages($tourId, $user->getID());

$imageRepo = new ImageRepository();
$thumb = $imageRepo->find($tour->getThumbID());

$zip = new \ZipArchive;
$filepath = sys_get_temp_dir() . "/tour_" . $tourId . ".zip" ;
//Deletes previously generated file
@unlink($filepath);

if ($zip->open($filepath, \ZIPARCHIVE::CREATE) === TRUE) {
  foreach ($images as $img) {
    $filePath = __DIR__.'/../../html/data/image/' . $img['filename'];
    if (file_exists($filePath)){
      // Preload image
      $zip->addFile(__DIR__.'/../../html/data/image/' . $img['filename'], "data/image/" . $img['filename']);
    }
    // Tiles
    $path =  __DIR__ . "/../../html/data/image/";
    // Set the max index of tiles in x and y direction
    $xMax = 15;
    $yMax = 7;
    // Search and delete all tiles
    for ($y = 0; $y <= $yMax; $y++){
      for ($x = 0; $x <= $xMax; $x++){
        $file = __DIR__ . "/../../html/data/image/{$x}/{$y}/{$img['filename']}";
        if (file_exists($file)){
          $zip->addFile($file, "data/image/{$x}/{$y}/{$img['filename']}");
        }
      }
    }
  }
  // Add the tour JSON
  $zip->addFromString('data/tour/tour.json', $tourJSON);
  // Add the viewer application
  $viewerPath = __DIR__."/../../html/v";
  $viewerPathLen = strlen($viewerPath);
  foreach (tree($viewerPath) as $file){
    $zip->addFile($file, "v/" . substr($file, $viewerPathLen+1));
  }
  // Add the README into the archive
  $zip->addFile(__DIR__."/../../html/README.md", "README.md");

  // Add the Thumbnail
  if ($thumb){
    $zip->addFile(
      __DIR__."/../../html/data/image/" . $thumb->getFilename() . "." . $thumb->getFiletype(),
      "thumbnail" . "." . $thumb->getFiletype()
    );
  }

  $zip->close();
  // Let's send the zip file
  header('Content-Type: application/zip');
  header('Content-Disposition: attachment; filename="360_visite.zip"');
  readfile($filepath);
  exit();
}

echo 'Cannot create zip file';
