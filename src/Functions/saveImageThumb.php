<?php
/*
 * This function generates and saves a thumbnail of an already saved image
 */
function saveImageThumb($image, $maxlength=1000000, $maxwidth=1024, $maxheight=1024, $quality=85){
  $pic = null;
  try {
    $imgFilepath = __DIR__ . "/../../html/data/image/" . $image->getFilename() . "." . $image->getFiletype();
    $pic = new \Imagick($imgFilepath);
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
  @$exif = exif_read_data($imgFilepath);
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
    $thumbFilepath = __DIR__ . "/../../html/data/image/thumb/" . $image->getFilename() . "." . $type;
    try {
      // We need to edit the image
      if ($pic->getImageWidth() > $maxwidth || $pic->getImageHeight() > $maxheight) {
        $pic->setImageCompressionQuality($quality);
        // Resize the image
        $pic->thumbnailImage($maxwidth, $maxheight, true);
        $pic->writeImages($thumbFilepath, true);
      } elseif ($pic->getImageLength() > $maxlength) {
        // Only recompress the image
        $pic->setImageCompressionQuality($quality);
        $pic->writeImages($thumbFilepath, true);
      } elseif ($convert) {
        $pic->setImageBackgroundColor("white");
        $pic->setImageCompressionQuality($quality);
        $pic->thumbnailImage($maxwidth, $maxheight, true);
        //~ $pic->resizeImage($maxwidth, $maxheight, \Imagick::FILTER_CUBIC, 0.9, true);
        $pic->setImageFormat('jpeg');
        $pic->writeImages($thumbFilepath, true);

      } else {
        // The dimensions and size of the image are OK, we just move the file
        if (file_exists($imgFilepath)){
          copy($imgFilepath, $thumbFilepath);
        }
      }
    } catch (Exception $e){
      // Continue without the file
      echo $e->getMessage();
      return(false);
    }
    return true;
  }
  return false;
}
