#!/usr/bin/env php
<?php
/*
 * Usage: php fixDeleteTiles.php [rm]
 *
 * Without the rm option the script will not delete anything.
 * 
 * Use this script to clean files that were not deleted due to a bug (fixed in #847f8f)
 * It will list all files in www/data/image/15/7/ and check for a corresponding file in www/data/images/0/0.
 * If there is no correspondance, we know that this file is remaining because of the bug then we delete all
 * remaining files of the same name
 */
namespace tour\Admin;

if (php_sapi_name() !== "cli") {
  echo "error: this program has to be launched from the command line\n";
  exit(10);
}

$dryrun = true;

if ($argc == 2 && $argv[1] === "rm") {
  $dryrun = false;
} else {
?>
  This is a dry run to check which file will
  be deleted. No file will be deleted for real.

  If the result is OK, launch it again with the
  "rm" option to delete the files, like this:

  php <?php echo $argv[0]; ?> rm

<?php
}

$imgDir = __DIR__ . "/../../html/data/image";
$files = scandir($imgDir."/15/7/");
$files2 = scandir($imgDir."/0/0/");

// We compute a diagnosis
$diff = count($files) - count($files2);
if ($diff > 0) {
  echo "{$diff} 360° pictures were not properly deleted...\n";
} else {
  echo "It seems that there is no file to delete, we continue nevertheless\n";
}

$deleted = 0;
$deletedTiles = 0;

echo "Start file deletion\n";

$xMin = 8;
$yMin = 4;
$xMax = 15;
$yMax = 7;

foreach ($files as $file){
  if (substr($file,-4) === ".jpg") {
    $bn = basename($file);
    if (@!is_file($imgDir."/0/0/".$bn)){
      // There is no file in the 0/0 folder, this means that this file was not properly removed.
      // We delete all left files with the same name

      // Search and delete all tiles
      for ($y = $yMin; $y <= $yMax; $y++){
        for ($x = $xMin; $x <= $xMax; $x++){
          $tile = "{$imgDir}/{$x}/{$y}/{$bn}";
          if (@file_exists($tile) && @is_file($tile)){
            if ($dryrun) {
              //Fake it
              $deletedTiles++;
            } else {
              // Delete file for real
              $success = unlink($tile);
              $deletedTiles += $success ? 1 : 0;
            }

          }
        }
      }
      $deleted++;
      if ($deleted % 100 === 0){
        echo "progress: {$deleted} / {$diff}";
      }
    }
  }
}

echo "Deletion finished\n";

if ($dryrun) {
  echo "A total of {$deletedTiles} tiles would have been deleted\n";
} else {
  echo "A total of {$deletedTiles} tiles have been deleted\n";
}

exit(0);
