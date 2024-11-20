<?php
namespace tour\Repository;
require_once(__DIR__."/../Autoloader.php");

use \tour\Entity\DB;
use \tour\Entity\Image;

class ImageRepository
{
  private $pdo = null;

  public function __construct() {
    $db = new DB();
    $this->pdo = $db->getConnection();
  }

  public function find($id) {
    $statement = $this->pdo->prepare("SELECT * FROM `image` WHERE id=?");
    $statement->execute([$id]);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return Image::withRow($row);
    }
    return null;
  }

  public function delete($id):bool {

    // Updates the tour modification date
    try {
      // If image is linked to a tour
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `image` ON `image`.`id`=`tour`.`thumb_id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `image`.`id` = ?"
      );
      $statement->execute([ $id ]);

      // If image is linked to a POI
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `spot` ON `spot`.`tour_id`=`tour`.`id` ".
        "LEFT JOIN `poi` ON `poi`.`spot_id`=`spot`.`id` ".
        "LEFT JOIN `image` ON `image`.`id`=`poi`.`image_id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `image`.`id` = ?"
      );
      $statement->execute([ $id ]);

      // If image is linked to a sky
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `spot` ON `spot`.`tour_id`=`tour`.`id` ".
        "LEFT JOIN `sky` ON `sky`.`spot_id`=`spot`.`id` ".
        "LEFT JOIN `image` ON `image`.`id`=`sky`.`image_id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `image`.`id` = ?"
      );
      $statement->execute([ $id ]);
    } catch (Exception $e) {
      return false;
    }

    // Deletes the Image
    $statement = $this->pdo->prepare("DELETE FROM `image` WHERE id=?");
    return $statement->execute([$id]);
  }

  public function findAllBy($params, $userID=null):array {
    $req = "SELECT * FROM `image` WHERE";
    $i = 0;
    $values = [];
    foreach ($params as $key => $value){
      // We don't check SQL columns names as they are not user defined.
      if ($i != 0) {
        $req .= " AND";
      }
      $req .= " `image`.`".$key."`=?";
      $values[] = $value;
      $i++;
    }
    if ($userID){
      $req.=" AND `image`.`user_id` = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $results = $statement->fetchAll();
    $images = [];
    foreach ($results as $row){
      $images[] = Image::withRow($row);
    }
    return $images;
  }

  public function findOneBy($params):?Image {
    $req = "SELECT * FROM `image` WHERE";
    $i = 0;
    $values = [];
    foreach ($params as $key => $value){
      // We don't check SQL columns names as they are not user defined.
      if ($i != 0) {
        $req .= " AND";
      }
      $req .= " `".$key."`=?";
      $values[] = $value;
      $i++;
    }
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return Image::withRow($row);
    }
    return null;
  }

  public function persist(&$image, $copy = false):?bool {
    if (!$image->getID() || $copy == true){
      // Create a new Image
      $statement = $this->pdo->prepare("INSERT INTO `image` (".
        " `user_id`,".
        " `src_filename`,".
        " `filename`,".
        " `filetype`,".
        " `filesize`,".
        " `title`,".
        " `width`,".
        " `height`".
        ") values (?, ?, ?, ?, ?, ?, ?, ?)");
      try {
        $statement->execute([
          $image->getUserID(),
          $image->getSrcFilename(),
          $image->getFilename(),
          $image->getFiletype(),
          $image->getFilesize(),
          $image->getTitle(),
          $image->getWidth(),
          $image->getHeight(),
        ]);
        $newImage = $this->find($this->pdo->lastInsertId());
        $image = $newImage ? $newImage : $image;
      } catch (Exception $e) {
        return false;
      }
    } else {
      // Modify the Image
      $statement = $this->pdo->prepare("UPDATE `image` SET".
        " `user_id` = ?,".
        " `src_filename` = ?,".
        " `filename` = ?,".
        " `filetype` = ?,".
        " `filesize` = ?,".
        " `title` = ?,".
        " `width` = ?,".
        " `height` = ?".
        " WHERE `id` = ?");
      try {
        $statement->execute([
          $image->getUserID(),
          $image->getSrcFilename(),
          $image->getFilename(),
          $image->getFiletype(),
          $image->getFilesize(),
          $image->getTitle(),
          $image->getWidth(),
          $image->getHeight(),
          $image->getID(),
        ]);
      } catch (Exception $e) {
        return false;
      }
    }

    // Updates the tour modification date
    try {
      // If image is linked to a tour
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `image` ON `image`.`id`=`tour`.`thumb_id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `image`.`id` = ?"
      );
      $statement->execute([ $image->getID() ]);
      if ($statement->rowCount() > 0) {return true;}

      // If image is linked to a POI
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `spot` ON `spot`.`tour_id`=`tour`.`id` ".
        "LEFT JOIN `poi` ON `poi`.`spot_id`=`spot`.`id` ".
        "LEFT JOIN `image` ON `image`.`id`=`poi`.`image_id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `image`.`id` = ?"
      );
      $statement->execute([ $image->getID() ]);
      if ($statement->rowCount() > 0) {return true;}

      // If image is linked to a sky
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `spot` ON `spot`.`tour_id`=`tour`.`id` ".
        "LEFT JOIN `sky` ON `sky`.`spot_id`=`spot`.`id` ".
        "LEFT JOIN `image` ON `image`.`id`=`sky`.`image_id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `image`.`id` = ?"
      );
      $statement->execute([ $image->getID() ]);
      return true;
    } catch (Exception $e) {
      return false;
    }
    return false;
  }
}
