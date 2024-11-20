<?php
exit("Replaced by saveImage");
function saveSkyImages($image, $sky, $tmpFile){
  // Resize the image file if needed
  if ($image) {
    if ($sky->getFilename()){
      $oldFile = __DIR__ . "/../../html/data/sky/" . $sky->getFilename() . ".jpg";
      $oldThumb = __DIR__ . "/../../html/data/sky/thumb/" . $sky->getFilename() . ".jpg";
    }
    $sky->setFilename(true);
    $newFile = __DIR__ . "/../../html/data/sky/" . $sky->getFilename() . ".jpg";
    $newThumb = __DIR__ . "/../../html/data/sky/thumb/" . $sky->getFilename() . ".jpg";
    try {
      set_time_limit(120); // Just for dev! TODO: remove
      // We need to edit the image
      $image->setImageCompressionQuality(SKY_QUALITY);
      if ($image->getImageWidth() > SKY_WIDTH || $image->getImageHeight() > SKY_WIDTH/2) {
        $image->setImageCompressionQuality(SKY_QUALITY);
        // Resize the image
        $image->thumbnailImage(SKY_WIDTH, SKY_WIDTH/2, true);
        $image->writeImages($newFile, true);
      } elseif ($image->getImageLength() > SKY_LENGTH) {
        // Only recompress the image
        $image->setImageCompressionQuality(SKY_QUALITY);
        $image->writeImages($newFile, true);
      } else {
        // The dimensions and size of the image are OK, we just move the file
        if (is_uploaded_file($tmpFile)){
          move_uploaded_file($tmpFile, $newFile);
        } else {
          rename($tmpFile, $newFile);
        }
      }
      // Finally we delete the former stored files
      if (isset($oldFile) && file_exists($oldFile)){
        unlink($oldFile);
      }
    } catch (Exception $e){
      // Continue without the file
      exit("{\"error\": \"Image not saved\"}");
    }

    // Generates a thumbnail of the file
    try {
      $image->setImageCompressionQuality(SKY_THUMB_QUALITY);
      $image->thumbnailImage(SKY_THUMB_WIDTH, 0);
      $image->writeImages(__DIR__ . "/../../html/data/sky/thumb/" . $sky->getFilename() . ".jpg", true);
      // Finally we delete the former stored files
      if (isset($oldThumb) &&file_exists($oldThumb)){
        unlink($oldThumb);
      }
    } catch (Exception $e){
      // Continue without the thumb
    }
  }
}
