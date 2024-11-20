<?php

function saveFile($image, $params){

  //~ $paths = [
    //~ __DIR__ . "/../../html/data/image/",
    //~ __DIR__ . "/../../html/data/image/thumb/"
  //~ ];

  // The image filename must be set
  if (!$image->getFilename()){
    echo "filename";
    return false;
  }

  //~ $oldFilename = $image->getFilename();
  //~ $oldFiletype = $image->getFiletype();
  //~ $image->setFilename(true);
  $rollback = false;

  if ($params == null){
    echo "params";
    return false;
  }
  $file = $params['file'];
  $maxlength = isset($params['maxlength']) ? $params['maxlength'] : 1000000;
  $maxwidth = isset($params['maxwidth']) ? $params['maxwidth'] : 1024;
  $maxheight = isset($params['maxheight']) ? $params['maxheight'] : 1024;
  $setInfos = isset($params['setinfos']) ? $params['setinfos'] : null;
  $oldFilename = isset($params['oldfilename']) ? $params['oldfilename'] : null;
  if (!isset($params['path'])) {
    echo "params path";
    return false;
  }
  $path = $params['path'];

  // Check for all the required file properties
  if(
    !isset(
        $file, $file['tmp_name'],
        $file['name'], $file['size'],
        $file['error']
    )
  ){
    echo "file";
    return false;
  }

  // Error during upload?
  if( UPLOAD_ERR_OK !== $file['error'] ){
    echo "upload";
    return false;
  }

  // File too big
  if( $file['size'] > $maxlength ){
    echo "length";
    return false;
  }

  // Check dimensions
  $imageSize = getimagesize($file['tmp_name']);
  if( $imageSize[0] > $maxwidth || $imageSize[1] > $maxheight ){
    return false;
  }

  // Check the mime type
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  switch (finfo_file($finfo, $file['tmp_name'])) {
    case "image/jpeg":
      $type = "jpg";
      break;
    case "image/png":
      $type = "png";
      break;
    default:
      // Not managed type
      return false;
  }
  finfo_close($finfo);

  try {
    if (is_uploaded_file($file['tmp_name'])){
      // Add infos to the Image object, but not for the thumb
      if ($setInfos){
        if(!$image->getFilesize()){
          $image->setFilesize($file['size']);
        }
        $image->setWidth($imageSize[0]);
        $image->setHeight($imageSize[1]);
        $image->setFiletype($type);
        $image->setSrcFilename($file['name']);
      }
      move_uploaded_file($file['tmp_name'], $path.$image->getFilename().".".$image->getFiletype());
    }
  } catch (Exception $e){
    // delete new images
    $rollback = true;
  }


  if ($rollback){
    // An error occured during the image recording, we delete new images
    $newFile = $path . $image->getFilename() . "." . $image->getFiletype();
    if (file_exists($newFile)){
      unlink($newFile);
    }
    return false;
  }

  // Delete the old images
  if ($oldFilename){
    $oldFile = $path . $oldFilename . "." . $oldFiletype;
    if (file_exists($oldFile)){
      unlink($oldFile);
    }
  }

  return true;
}
