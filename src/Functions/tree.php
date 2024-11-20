<?php
// List all files contained into the path and its subfolder
function tree(string $path, int $limit=6):array {
  $files = scandir($path);
  $list = [];
  foreach ($files as $file){
    if ($file != '.' && $file != '..'){
      if (is_dir($path."/".$file)){
        if ($limit>0) {
          $list = array_merge($list, tree($path.'/'.$file, $limit-1));
        }
        continue;
      }
      $list[] = $path.'/'.$file;
    }
  }
  return $list;
}
