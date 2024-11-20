<?php
/*
 * Modify the filename of the Image object and copies image files to this new filename
 */
function copyImage(&$image, &$imageRepo, &$user=null):bool{

    $oldFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
    $oldThumbFile = __DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype();
    $image->setFilename(true);
    $newFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
    $newThumbFile = __DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype();
    if ($user){
      $image->setUserID($user->getID());
    }
    try {
      $imageRepo->persist($image, true);
      if (file_exists($oldFile) && !file_exists($newFile)){
        copy($oldFile, $newFile);
      } else {
        return false;
      }
      if (file_exists($oldThumbFile) && !file_exists($newThumbFile)){
        copy($oldThumbFile, $newThumbFile);
      }
      return true;
    } catch(Exception $e){
      // image not saved, continue
    }
  return false;
}
