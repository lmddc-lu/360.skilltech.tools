<?php

use \tour\Entity\Image;
use \tour\Entity\POI;
use \tour\Entity\Sky;
use \tour\Entity\Spot;
use \tour\Entity\SpotHasSpot;
use \tour\Entity\Tour;
use \tour\Entity\User;
use \tour\Repository\ImageRepository;
use \tour\Repository\POIRepository;
use \tour\Repository\SkyRepository;
use \tour\Repository\SpotRepository;
use \tour\Repository\SpotHasSpotRepository;
use \tour\Repository\TourRepository;

require_once(__DIR__."/deleteFile.php");
require_once(__DIR__."/deleteTiles.php");

/*
 * [x] Deletes the tour in the DB
 * [x] Deletes the cache file
 * [x] Deletes all linked spots, skies data and images
 * [x] Deletes all linked POIs and images
 * [x] Deletes the thumbnail (file and DB)
 */
function deleteTour($tour){

  $tourId = $tour->getId();

  $spotRepo = new SpotRepository();
  $spots = $spotRepo->findAllBy(["tour_id" => $tourId]);

  $skyRepo = new SkyRepository();
  $poiRepo = new POIRepository();
  $imageRepo = new ImageRepository();
  //~ $skies = $skyRepo->findAllBy(["spot_id" => $spot->getID()]);

  // Deletes all SpotHasSpot and related Images
  $shsRepo = new SpotHasSpotRepository();
  $result = $shsRepo->deleteAllByTourID($tourId);

  foreach ($spots as $spot){
    $skies = $skyRepo->findAllBy(["spot_id" => $spot->getID()]);
    // Deletes all related skies and files
    foreach ($skies as $sky){
      // First we have to delete the sky from the DB due to foreign key constraint
      $skyRepo->delete($sky->getID());
      $image = $imageRepo->find($sky->getImageID());
      if ($image){
        deleteFile(__DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype());
        deleteFile(__DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype());
        deleteTiles($image->getFilename() . "." . $image->getFiletype());
        $imageRepo->delete($image->getID());
      }
    }

    // Deletes all related POIs and Images and files
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
    
    $spotRepo->delete($spot->getID());
  }

  // Thumbnail
  $ImageRepo = new ImageRepository();
  $thumb = $ImageRepo->find($tour->getThumbID());
  if($thumb){
    $thumbPath = __DIR__ . "/../../html/data/image/" . $thumb->getFilename() . "." . $thumb->getFiletype();
    if (file_exists($thumbPath)){
      unlink($thumbPath);
    }
    $ImageRepo->delete($thumb->getID());
  }

  // Delete the cache file
  $filename = __DIR__ . "/../../html/data/tour/" . $tour->getFilename() . ".json";
  deleteFile($filename);

  // Delete the tour from DB
  $tourRepo = new TourRepository();
  $tourRepo->delete($tour->getID());

  return true;
}
