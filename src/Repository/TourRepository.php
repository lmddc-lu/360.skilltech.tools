<?php
namespace tour\Repository;
require_once(__DIR__."/../Autoloader.php");

use \tour\Entity\DB;
use \tour\Entity\Tour;

class TourRepository
{
  private $pdo = null;

  public function __construct() {
    $db = new DB();
    $this->pdo = $db->getConnection();
  }

  public function find($id, $userID=null) {
    $values = [$id];
    $condition = "";
    if ($userID){
      $condition = " AND user_id = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare("SELECT * FROM tour WHERE id=? $condition");
    $statement->execute($values);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return Tour::withRow($row);
    }
    return null;
  }

  public function delete($id):bool {
    $statement = $this->pdo->prepare("DELETE FROM `tour` WHERE id=?");
    return $statement->execute([$id]);
  }

  public function findAllByUserID($userID):array {
    $statement = $this->pdo->prepare("SELECT * FROM tour WHERE user_id=?");
    $statement->execute([$userID]);
    $results = $statement->fetchAll();
    $tours = [];
    foreach ($results as $row){
      $tours[] = Tour::withRow($row);
    }
    return $tours;
  }

  public function getAllByUserID($userID):array {
    // We want to have directly the thumb filename
    $statement = $this->pdo->prepare("SELECT ".
    "`tour`.`id`, ".
    "`tour`.`title`, ".
    "`tour`.`description`, ".
    "`tour`.`filename`, ".
    "`tour`.`author`, ".
    "`tour`.`license`, ".
    "`tour`.`start_id`, ".
    "`tour`.`thumb_id`, ".
    "`tour`.`user_id`, ".
    "`tour`.`creation_date`, ".
    "`tour`.`modification_date`, ".
    "COUNT(`spot_has_spot`.`id`) AS `nb_shs`, ".
    "NULLIF (CONCAT(`image`.`filename`, \".\", `image`.`filetype`), \".\") AS thumb_filename ".
    "FROM tour ".
    "LEFT JOIN `image` ON `image`.`id` = `tour`.`thumb_id` ".
    "LEFT JOIN `spot` ON `spot`.`tour_id` = `tour`.`id` ".
    "LEFT JOIN `spot_has_spot` ON (`spot_has_spot`.`spot1` = `spot`.`id`) ".
    "WHERE `tour`.`user_id`=? ".
    "GROUP BY `tour`.`id`");
    $statement->execute([$userID]);
    $results = $statement->fetchAll(\PDO::FETCH_NAMED);
    return $results;
  }

  public function getSize($tourID, $userID=null):?int {
    // Computes the size of all Skies images and POIs images of this tour (except thumbnails)
    $values = [$tourID];
    $condition = "";
    if ($userID){
      $condition=" AND `tour`.`user_id` = ? ";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare(
      "select SUM(images.filesize) as size FROM ".
      "(SELECT distinct image.filesize, image.filename ".
      "FROM tour ".
      "LEFT JOIN `spot` on `spot`.`tour_id` = `tour`.`id` ".
      "LEFT JOIN `poi` on `poi`.`spot_id` = `spot`.`id` ".
      "LEFT JOIN `sky` on `sky`.`spot_id` = `spot`.`id` ".
      "LEFT JOIN `image` on `image`.`id` = `sky`.`image_id` or `image`.`id` = `poi`.`image_id` ".
      "WHERE `tour`.`id`=? $condition) as images"
    );
    
    $statement->execute($values);
    $result = $statement->fetchColumn(0);
    return $result;
  }

  public function getImages($tourID, $userID=null):array {
    $values = [$tourID];
    $condition = "";
    if ($userID){
      $condition=" AND `tour`.`user_id` = ? ";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare(
      "SELECT distinct CONCAT(image.filename, '.', image.filetype) AS filename, image.src_filename ".
      "FROM tour ".
      "LEFT JOIN `spot` on `spot`.`tour_id` = `tour`.`id` ".
      "LEFT JOIN `poi` on `poi`.`spot_id` = `spot`.`id` ".
      "LEFT JOIN `sky` on `sky`.`spot_id` = `spot`.`id` ".
      "LEFT JOIN `image` on `image`.`id` = `sky`.`image_id` or `image`.`id` = `poi`.`image_id` ".
      "WHERE `tour`.`id`=? $condition"
    );
    
    $statement->execute($values);
    return $statement->fetchAll(\PDO::FETCH_NAMED);
  }

  public function findOneBy($params):?Tour {
    $req = "SELECT * FROM tour WHERE";
    $i = 0;
    $values = [];
    foreach ($params as $key => $value){
      // We don't check SQL columns names as they are not user defined.
      if ($i != 0) {
        $req .= " AND";
      }
      $req .= " ".$key."=?";
      $values[] = $value;
      $i++;
    }
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return Tour::withRow($row);
    }
    return null;
  }

  public function setDates(&$tour):?bool {
    // Force the dates
    $statement = $this->pdo->prepare(
      "UPDATE `tour` SET ".
      "`creation_date`=COALESCE(?, DEFAULT(creation_date)), ".
      "`modification_date`=COALESCE(?, DEFAULT(modification_date)) ".
      "WHERE `id`=?"
    );
    try {
      $statement->execute([
        $tour->getCreationDate(),
        $tour->getModificationDate(),
        $tour->getID(),
      ]);
      return true;
    } catch (Exception $e) {
      return false;
    }
  }

  public function persist(&$tour, $copy = false):?bool {
    if (!$tour->getID() || $copy == true){
      // Create a new Tour
      $tour->setFilename();
      $statement = $this->pdo->prepare("INSERT INTO `tour` ".
        "(`title`, `description`, `filename`, `author`, `license`, `start_id`, `thumb_id`, `user_id`, `creation_date`, `modification_date`) ".
        "values (?, ?, ?, ?, ?, ?, ?, ?, COALESCE(?, DEFAULT(creation_date)), COALESCE(?, DEFAULT(modification_date)))");
      try {
        $statement->execute([
          $tour->getTitle(),
          $tour->getDescription(),
          $tour->getFilename(),
          $tour->getAuthor(),
          $tour->getLicense(),
          $tour->getStartID(),
          $tour->getThumbID(),
          $tour->getUserID(),
          $tour->getCreationDate(),
          $tour->getModificationDate(),
        ]);
        $newTour = $this->find($this->pdo->lastInsertId());
        $tour = $newTour ? $newTour : $tour;
        return true;
      } catch (Exception $e) {
        return false;
      }
    } else {
      // Modify the Tour
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "SET `title`=?, `description`=?, `filename`=?, `author`=?, `license`=?, `start_id`=?, `thumb_id`=? ".
        "WHERE `id`=?"
      );
      try {
        $statement->execute([
          $tour->getTitle(),
          $tour->getDescription(),
          $tour->getFilename(),
          $tour->getAuthor(),
          $tour->getLicense(),
          $tour->getStartID(),
          $tour->getThumbID(),
          $tour->getID(),
        ]);
        return true;
      } catch (Exception $e) {
        return false;
      }
    }
    return false;
  }
}
