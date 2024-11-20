<?php

function deleteFile($filepath){

  // Delete the file
  if ($filepath && file_exists($filepath) && is_file($filepath)){
    unlink($filepath);
  }

  return true;
}
