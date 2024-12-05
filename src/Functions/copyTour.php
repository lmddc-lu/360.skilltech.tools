<?php
/*
 * Duplicate the tour in another collection
 */

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

function copyTour($tourId, &$user):?int{
  $tourRepo = new TourRepository();
  $imageRepo = new ImageRepository();
  $tour = null;
  $tour = $tourRepo->find($tourId);

  // Verify if the Tour ID exists
  if ( !$tour ) {
    //TODO: log error
    return null;
  }
  $tour->setFilename(true);
  $tour->setUserID($user->getID());

  // Copy the thumbnail
  $tourThumb = $imageRepo->find($tour->getThumbID());
  if ($tourThumb) {
    copyImage($tourThumb, $imageRepo, $user);
    $tour->setThumbID($tourThumb->getID());
  }
  $tourRepo->persist($tour, true);
  // $tour is now a new object owned by the current user

  $spotRepo = new SpotRepository();
  $spots = $spotRepo->findAllBy(["tour_id" => $tourId]);
  $newSpots = []; //Keys are the demo spot id, value are the new spot ID

  // Copy the all the Spot entities
  $skyRepo = new SkyRepository();
  foreach ($spots as $spot){
    // The spot object will be modified later by reference
    // TODO replace variable name
    $demoSpotId = $spot->getID();
    $spot->setTourID($tour->getID());
    if ($tourId === DEMO_TOUR_ID){
      $spot->setCreationDate(DEMO_CREATION_DATE);
      $spot->setModificationDate(DEMO_CREATION_DATE);
    }
    $spotRepo->persist($spot, true);
    $newSpots[$demoSpotId] = $spot->getID();
    // Copy all the Sky entities and their Image entities
    $skies = $skyRepo->findAllBy(["spot_id" => $demoSpotId]);
    foreach ($skies as $sky){
      $demoImageId = $sky->getImageID();
      $image = $imageRepo->find($demoImageId);
      $oldFilename = $image->getFilename();
      $oldFiletype = $image->getFiletype();
      $demoFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
      $demoThumbFile = __DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype();

      $newFilename = $image->getFilename();
      $newFiletype = $image->getFiletype();
      $image->setFilename(true);
      $newFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
      $newThumbFile = __DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype();
      $image->setUserID($user->getID());
      if ($tourId === DEMO_TOUR_ID){
        $image->setCreationDate(DEMO_CREATION_DATE);
      }
      try {
        // Create a new image in DB
        $imageRepo->persist($image, true);
        // Copy the thumb and the preview of the sky image
        if (!file_exists($newFile) && ! file_exists($newThumbFile)){
          copy($demoFile, $newFile);
          copy($demoThumbFile, $newThumbFile);
        }
        // Set the max indice of tiles in x and y direction
        $xMax = 15;
        $yMax = 7;
        // Copy the tiles of the sky image
        for ($y = 0; $y <= $yMax; $y++){
          for ($x = 0; $x <= $xMax; $x++){
            $oldFile = __DIR__ . "/../../html/data/image/{$x}/{$y}/".$oldFilename . "." . $oldFiletype;
            if (file_exists($oldFile)){
              $newFile = __DIR__ . "/../../html/data/image/{$x}/{$y}/".$image->getFilename() . "." . $image->getFiletype();
              copy($oldFile, $newFile);
            }
          }
        }
      } catch(Exception $e){
        echo ($e);
        // image not saved, continue
      }
      
      $sky->setSpotID($spot->getID());
      $sky->setImageID($image->getID());
      if ($tourId === DEMO_TOUR_ID){
        $sky->setCreationDate(DEMO_CREATION_DATE);
      }
      $skyRepo->persist($sky, true);
    }

  }

  // Copy the SpotHasSpot entities
  $shsRepo = new SpotHasSpotRepository();
  $spotHasSpots = $shsRepo->findAllByTourID($tourId);
  foreach($spotHasSpots as $shs){
    if (isset($newSpots[$shs->getSpot1()]) && isset($newSpots[$shs->getSpot2()])){
      $newShs = new SpotHasSpot();
      $newShs->setSpot1($newSpots[$shs->getSpot1()]);
      $newShs->setSpot2($newSpots[$shs->getSpot2()]);
      $newShs->setSpot1x($shs->getSpot1x());
      $newShs->setSpot2x($shs->getSpot2x());
      $newShs->setSpot1y($shs->getSpot1y());
      $newShs->setSpot2y($shs->getSpot2y());
      $newShs->setSpot1t($shs->getSpot1t());
      $newShs->setSpot2t($shs->getSpot2t());
      $shsRepo->persist($newShs);
    }
  }

  // Copy the POI entities
  $poiRepo = new POIRepository();
  $pois = $poiRepo->findAllByTourID($tourId);
  foreach($pois as $poi){
    $poiImage = $imageRepo->find($poi->getImageID());
    if ($poiImage){
      copyImage($poiImage, $imageRepo, $user);
      $poi->setImageID($poiImage->getID());
    }
    if (isset($newSpots[$poi->getSpotID()])){
      // attach the POI to its new spot
      $poi->setSpotID($newSpots[$poi->getSpotID()]);
      if ($tourId === DEMO_TOUR_ID){
        $poi->setCreationDate(DEMO_CREATION_DATE);
      }
      // save changes as a new POI
      $poiRepo->persist($poi, true);
    }
  }

  // If we copy the demo tour, we need to set the modification and creation date whith their special values
  if ($tourId === DEMO_TOUR_ID){
    $tour->setCreationDate(DEMO_CREATION_DATE);
    $tour->setModificationDate(DEMO_CREATION_DATE);
    $tourRepo->setDates($tour);
  }

  return $tour->getID();
}
