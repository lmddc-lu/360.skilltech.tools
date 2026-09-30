#!/usr/bin/env php
<?php
namespace tour\Admin;

if (php_sapi_name() !== "cli") {
  echo "error: this program has to be launched from the command line\n";
  exit(10);
}

require_once(__DIR__."/../Autoloader.php");

use \tour\Repository\TourRepository;
use \tour\Repository\UserRepository;

require_once(__DIR__."/../Functions/deleteTour.php");


if ($argc != 2 || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>
  Usage:
  php <?php echo $argv[0]; ?> <userEMail>

  <userEMail> is the e-mail of the user
  you want to delete. All of its tour and
  related images will be deleted without
  asking for confirmation.

  You may want to use the showUser.php
  script first.
<?php
  exit(1);
}

$userEmail = $argv[1];

$userRepo = new UserRepository();
$user = $userRepo->findByEmail($userEmail);

if ($user == null) {
  echo("error: $userEmail not found\n");
  exit(2);
}

$tourRepo = new TourRepository();
$tourListArray = $tourRepo->getAllByUserID($user->getId());

echo count($tourListArray);
echo " tour(s) will be deleted.\n";

foreach ($tourListArray as $tourArray) {
  echo "Tour id: {$tourArray['id']}\n";
  $tour = $tourRepo->find($tourArray['id']);
  if (!$tour) {
    echo "error: tour not found\n";
    exit(3);
  }
  $result = deleteTour($tour);
  if (!$result) {
    echo "error: deleteTour\n";
    exit(4);
  }
}

$result = $userRepo->delete($user->getId());
if (!$result) {
  echo "error: unable to delete the user from the database\n";
  exit(5);
}

echo ("success: User deleted\n");
exit(0);
