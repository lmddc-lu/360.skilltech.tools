<?php

function saveTiles($image, $fileParams){

  $path =  __DIR__ . "/../../html/data/image/";

  $rollback = false;

  $tiles = $fileParams['tiles'];
  $maxlength = isset($fileParams['maxlength']) ? $fileParams['maxlength'] : 1000000;
  $maxwidth = isset($fileParams['maxwidth']) ? $fileParams['maxwidth'] : 1024;
  $maxheight = isset($fileParams['maxheight']) ? $fileParams['maxheight'] : 1024;
  $cols = isset($fileParams['cols']) ? $fileParams['cols'] : 16;
  $rows = isset($fileParams['rows']) ? $fileParams['rows'] : 8;
  $oldFilename = isset($fileParams['oldfilename']) ? $fileParams['oldfilename'] : null;
  $oldFiletype = isset($fileParams['oldfiletype']) ? $fileParams['oldfiletype'] : null;

  if (count($tiles['error']) != $cols * $rows) {
    return false;
  }

  // The image filename must be set
  if (!$image->getFilename()){
    return false;
  }

  $totalLength = 0;
  $tileWidth = null;
  $tileHeight = null;
  $type = null;
  // Check the mime type
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $files = [];

  // A first loop to check the tiles
  foreach ($tiles['error'] as $key => $error){

    // Error during upload
    if ($error !== UPLOAD_ERR_OK) {
      return false;
    }

    // Check the presence of the uploaded file
    if (!is_uploaded_file($tiles['tmp_name'][$key])){
      return;
    }
  
    // Check for all the required file properties
    if (!isset(
        $tiles['tmp_name'][$key],
        $tiles['name'][$key], $tiles['size'][$key],
        $tiles['error'][$key]
      )){
      return false;
    }

    // Compute the size of all the tiles
    $totalLength += $tiles['size'][$key];


    $imageSize = getimagesize($tiles['tmp_name'][$key]);
    // Dimensions of all tiles must be the same
    if ($tileWidth == null) $tileWidth = $imageSize[0];
    if ($tileHeight == null) $tileHeight = $imageSize[1];
    if ($tileWidth != $imageSize[0] || $tileHeight != $imageSize[1]){
      return;
    }

    // Check max dimensions
    if( $tileWidth * $cols > $maxwidth || $tileHeight * $rows > $maxheight ){
      return false;
    }

    // All tiles must be of the same format, either png or jpeg
    switch (finfo_file($finfo, $tiles['tmp_name'][$key])) {
      case "image/jpeg":
        if ($type == null) $type = "jpg";
        if ($type != "jpg") return false;
        break;
      case "image/png":
        if ($type == null) $type = "png";
        if ($type != "png") return false;
        break;
      default:
        // Type not managed
        return false;
    }
    $image->setFiletype($type);
  }
  finfo_close($finfo);

  // Check file size
  if( $totalLength > $maxlength ){
    return false;
  }

  foreach ($tiles['error'] as $key => $error){
    try {
      $x = $key % $cols;
      $y = intdiv($key, $cols);

      $newFile = "{$path}{$x}/{$y}/".$image->getFilename().".".$image->getFiletype();
      $files[] = $newFile;
      move_uploaded_file($tiles['tmp_name'][$key], $newFile);

    } catch (Exception $e){
      // delete new images
      $rollback = true;
    }
  }

  // Add infos to the Image object
  $image->setFilesize($totalLength);
  //~ $image->setWidth($tileWidth * $cols);
  //~ $image->setHeight($tileHeight * $rows);

  $image->setSrcFilename($tiles['name'][0]);

  if ($rollback){
    // An error occured during the image saving, we delete new images
    foreach ($files as $file){
      if (file_exists($file)){
        unlink($file);
      }
    }
    return false;
  }

  // Delete the old images
  // Set the number of tiles in x and y direction
  $xMax = 15;
  $yMax = 7;
  // Search and delete all tiles
  if ($oldFilename){
    for ($y = 0; $y <= $yMax; $y++){
      for ($x = 0; $x <= $xMax; $x++){
        $oldFile = "{$path}{$x}/{$y}/".$oldFilename . "." . $oldFiletype;
        if (file_exists($oldFile)){
          unlink($oldFile);
        }
      }
    }
  }

  return true;
}
