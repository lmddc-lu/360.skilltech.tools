<?php

function deleteTiles($filename){
  
  $path =  __DIR__ . "/../../html/data/image/";
  // Set the number of tiles in x and y direction
  $xMax = 7;
  $yMax = 3;
  // Search and delete all tiles
  if ($filename){
    for ($y = 0; $y <= $yMax; $y++){
      for ($x = 0; $x <= $xMax; $x++){
        $file = "{$path}{$x}/{$y}/".$filename;
        if (file_exists($file) && is_file($file)){
          unlink($file);
        }
      }
    }
  }

  return true;
}
