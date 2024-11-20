<?php
namespace tour\Repository;
require_once(__DIR__."/../Autoloader.php");

use \tour\Entity\DB;
use \tour\Entity\POI;

class POIRepository
{
  private $pdo = null;

  public function __construct() {
    $db = new DB();
    $this->pdo = $db->getConnection();
  }

  public function find($id):?POI {
    $statement = $this->pdo->prepare("SELECT * FROM `poi` WHERE id=?");
    $statement->execute([$id]);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return POI::withRow($row);
    }
    return null;
  }

  public function delete($id):bool {
    // Updates the tour modification date
    try {
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `spot` ON `spot`.`tour_id`=`tour`.`id` ".
        "LEFT JOIN `poi` ON `poi`.`spot_id`=`spot`.`id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `poi`.`id` = ?"
      );
      $statement->execute([ $id ]);
    } catch (Exception $e) {
      return false;
    }
    // Deletes the POI
    $statement = $this->pdo->prepare("DELETE FROM `poi` WHERE id=?");
    return $statement->execute([$id]);
  }

  public function findAllBy($params, $userID=null):array {
    $join = "";
    if ($userID){
      $join = " LEFT JOIN `spot` ON `spot`.`id` = `poi`.`spot_id`";
      $join .= " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`";
    }
    // We need to name all columns because of the join
    $req = "SELECT".
      " `poi`.`id`,".
      " `poi`.`icon`,".
      " `poi`.`title`,".
      " `poi`.`text`,".
      " `poi`.`image_id`,".
      " `poi`.`spot_id`,".
      " `poi`.`x`,".
      " `poi`.`y`,".
      " `poi`.`layer`,".
      " `poi`.`template`,".
      " `poi`.`creation_date`,".
      " `poi`.`modification_date`".
      " FROM `poi` $join WHERE";
    $i = 0;
    $values = [];
    foreach ($params as $key => $value){
      // We don't check SQL columns names as they are not user defined.
      if ($i != 0) {
        $req .= " AND";
      }
      $req .= " `poi`.`".$key."`=?";
      $values[] = $value;
      $i++;
    }
    if ($userID){
      $req.=" AND `tour`.`user_id` = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $results = $statement->fetchAll();
    $pois = [];
    foreach ($results as $row){
      $pois[] = POI::withRow($row);
    }
    return $pois;
  }

  public function findAllByTourID($tourId):array {
    // We need to name all columns because of the join
    $req = "SELECT".
      " `poi`.`id`,".
      " `poi`.`icon`,".
      " `poi`.`title`,".
      " `poi`.`text`,".
      " `poi`.`image_id`,".
      " `poi`.`spot_id`,".
      " `poi`.`x`,".
      " `poi`.`y`,".
      " `poi`.`layer`,".
      " `poi`.`template`,".
      " `poi`.`creation_date`,".
      " `poi`.`modification_date`".
      " FROM `poi`".
      " LEFT JOIN `spot` ON `spot`.`id` = `poi`.`spot_id`".
      " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`".
      " WHERE `tour`.`id` = ?";
    $statement = $this->pdo->prepare($req);
    $statement->execute([$tourId]);
    $results = $statement->fetchAll();
    $pois = [];
    foreach ($results as $row){
      $pois[] = POI::withRow($row);
    }
    return $pois;
  }

  public function getAllBySpotID($spotID, $userID=null):array {
    $join = "";
    if ($userID){
      $join = " LEFT JOIN `spot` ON `spot`.`id` = `poi`.`spot_id`";
      $join .= " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`";
    }
    // We need to name all columns because of the join
    $req = "SELECT".
      " `poi`.`id`,".
      " `poi`.`icon`,".
      " `poi`.`title`,".
      " `poi`.`text`,".
      " `poi`.`image_id`,".
      " `poi`.`spot_id`,".
      " `poi`.`x`,".
      " `poi`.`y`,".
      " `poi`.`layer`,".
      " `poi`.`template`,".
      " `poi`.`creation_date`,".
      " `poi`.`modification_date`,".
      " NULLIF (CONCAT(`image`.`filename`, \".\", `image`.`filetype`), \".\") AS image_filename,".
      " `image`.`width` AS image_width,".
      " `image`.`height` AS image_height".
      " FROM `poi` $join".
      " LEFT JOIN `image` ON `image`.`id` = `poi`.`image_id`".
      " WHERE `poi`.`spot_id` = ?";

    $values = array($spotID);
    if ($userID){
      $req.=" AND `tour`.`user_id` = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    //~ $statement->debugDumpParams();
    $results = $statement->fetchAll(\PDO::FETCH_NAMED);
    return $results;
  }

  public function findOneBy($params, $userID=null):?POI {
    $join = "";
    if ($userID){
      $join = " LEFT JOIN `spot` ON `spot`.`id` = `poi`.`spot_id`";
      $join .= " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`";
    }
    // We need to name all columns because of the join
    $req = "SELECT".
      " `poi`.`id`,".
      " `poi`.`icon`,".
      " `poi`.`title`,".
      " `poi`.`text`,".
      " `poi`.`image_id`,".
      " `poi`.`spot_id`,".
      " `poi`.`x`,".
      " `poi`.`y`,".
      " `poi`.`layer`,".
      " `poi`.`template`,".
      " `poi`.`creation_date`,".
      " `poi`.`modification_date`".
      " FROM `poi` $join WHERE";
    $i = 0;
    $values = [];
    foreach ($params as $key => $value){
      // We don't check SQL columns names as they are not user defined.
      if ($i != 0) {
        $req .= " AND";
      }
      $req .= " `poi`.`".$key."`=?";
      $values[] = $value;
      $i++;
    }
    if ($userID){
      $req.=" AND `tour`.`user_id` = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return POI::withRow($row);
    }
    return null;
  }

  public function persist(&$poi, $copy = false):?bool {
    if (!$poi->getID() || $copy == true){
      // Create a new poi
      $statement = $this->pdo->prepare("INSERT INTO `poi` (".
        "`icon`,".
        "`title`,".
        "`text`,".
        "`image_id`,".
        "`spot_id`,".
        "`x`,".
        "`y`,".
        "`layer`,".
        "`template`".
       ") values (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      try {
        $statement->execute([
          $poi->getIcon(),
          $poi->getTitle(),
          $poi->getText(),
          $poi->getImageID(),
          $poi->getSpotID(),
          $poi->getX(),
          $poi->getY(),
          $poi->getLayer(),
          $poi->getTemplate()
        ]);
        $newPoi = $this->find($this->pdo->lastInsertId());
        $poi = $newPoi ? $newPoi : $poi;
      } catch (Exception $e) {
        return false;
      }
    } else {
      // Modify the poi
      $statement = $this->pdo->prepare("UPDATE `poi` SET ".
        "`icon` = ?, ".
        "`title` = ?, ".
        "`text` = ?, ".
        "`image_id` = ?, ".
        "`spot_id` = ?, ".
        "`x` = ?, ".
        "`y` = ?, ".
        "`layer` = ?, ".
        "`template` = ? ".
        "WHERE `id`=?");
      try {
        $statement->execute([
          $poi->getIcon(),
          $poi->getTitle(),
          $poi->getText(),
          $poi->getImageID(),
          $poi->getSpotID(),
          $poi->getX(),
          $poi->getY(),
          $poi->getLayer(),
          $poi->getTemplate(),
          $poi->getID(),
        ]);
      } catch (Exception $e) {
        return false;
      }
    }
    // Updates the tour modification date
    try {
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `spot` ON `spot`.`tour_id`=`tour`.`id` ".
        "LEFT JOIN `poi` ON `poi`.`spot_id`=`spot`.`id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `poi`.`id` = ?"
      );
      $statement->execute([ $poi->getID() ]);
      return true;
    } catch (Exception $e) {
      return false;
    }
    return false;
  }
}
 
