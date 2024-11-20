<?php

function saveImage($image, $file, $maxlength=1000000, $maxwidth=1024, $maxheight=1024, $quality=85){
  // TODO: take the image rotation into account (Exif)
  $pic = null;
  try {
    $pic = new \Imagick($file['tmp_name']);
    $type = $pic->getImageMimeType();
    $convert = false;
    switch ($type) {
      case "image/jpeg":
        $type = "jpg";
        break;
      case "image/png":
        $type = "png";
        break;
      default:
      //image/svg+xml
        $type = "jpg";
        $convert = true;
        break;
    }
  } catch (Exception $e){
    return false;
  }
  // Test if we need to rotate the image
  @$exif = exif_read_data($file['tmp_name']);
  if($exif && isset($exif['Orientation'])){
    switch($exif['Orientation']) {
        case 6: // rotate 90 degrees CW
            $pic->rotateimage("#FFF", 90);
            $convert = true; //force the generation of a jpeg
        break;
        case 8: // rotate 90 degrees CCW
           $pic->rotateimage("#FFF", -90);
           $convert = true; //force the generation of a jpeg
        break;
    }
  }

  // Resize the image file if needed
  if ($pic) {
    $image->setSrcFilename($file['name']);
    if ($image->getFilename()){
      $oldFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
      $oldThumbFile = __DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $image->getFiletype();
    }
    $image->setFilename(true);
    $newFile = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $type;
    try {
      // We need to edit the image
      if ($pic->getImageWidth() > $maxwidth || $pic->getImageHeight() > $maxheight) {
        $pic->setImageCompressionQuality($quality);
        // Resize the image
        $pic->thumbnailImage($maxwidth, $maxheight, true);
        $pic->writeImages($newFile, true);
      } elseif ($pic->getImageLength() > $maxlength) {
        // Only recompress the image
        $pic->setImageCompressionQuality($quality);
        $pic->writeImages($newFile, true);
      } elseif ($convert) {
        // TODO
        // The upscaling of a SVG will be blurry
        // Some infos here to how to fix this bug : https://stackoverflow.com/a/13625767
        $pic->setImageBackgroundColor("white");
        $pic->setImageCompressionQuality($quality);
        $pic->thumbnailImage($maxwidth, $maxheight, true);
        //$pic->resizeImage($maxwidth, $maxheight, \Imagick::FILTER_BOX, 1, true);
        $pic->setImageFormat('jpeg');
        $pic->writeImages($newFile, true);

      } else {
        // The dimensions and size of the image are OK, we just move the file
        if (is_uploaded_file($file['tmp_name'])){
          move_uploaded_file($file['tmp_name'], $newFile);
        }
      }
      // Finally we delete the former stored files
      if (isset($oldFile) && file_exists($oldFile)){
        unlink($oldFile);
      }
      if (isset($oldThumbFile) && file_exists($oldThumbFile)){
        unlink($oldThumbFile);
      }
      // Add infos to the Image object
      $image->setFilesize($pic->getImageLength());
      $image->setWidth($pic->getImageWidth());
      $image->setHeight($pic->getImageHeight());
      $image->setFiletype($type);
    } catch (Exception $e){
      // Continue without the file
      return(false);
    }
    return true;
  }
  return false;
}
