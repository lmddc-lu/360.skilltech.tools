<?php
namespace tour\Repository;
require_once(__DIR__."/../Autoloader.php");

use \tour\Entity\DB;
use \tour\Entity\Sky;

class SkyRepository
{
  private $pdo = null;

  public function __construct() {
    $db = new DB();
    $this->pdo = $db->getConnection();
  }

  public function find($id) {
    $statement = $this->pdo->prepare("SELECT * FROM `sky` WHERE id=?");
    $statement->execute([$id]);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return Sky::withRow($row);
    }
    return null;
  }

  public function delete($id):bool {

    // Updates the tour modification date
    try {
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `spot` ON `spot`.`tour_id`=`tour`.`id` ".
        "LEFT JOIN `sky` ON `sky`.`spot_id`=`spot`.`id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `sky`.`id` = ?"
      );
      $statement->execute([ $id ]);
    } catch (Exception $e) {
      return false;
    }

    // Deletes the sky
    $statement = $this->pdo->prepare("DELETE FROM `sky` WHERE id=?");
    return $statement->execute([$id]);
  }

  public function findAllBy($params, $userID=null):array {
    $join = "";
    if ($userID){
      $join = " LEFT JOIN `spot` ON `spot`.`id` = `sky`.`spot_id`";
      $join .= " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`";
    }
    // We need to name all columns because of the join
    $req = "SELECT".
      " `sky`.`id`,".
      " `sky`.`image_id`,".
      " `sky`.`spot_id`,".
      " `sky`.`layer`,".
      " `sky`.`creation_date`,".
      " `sky`.`modification_date`".
      " FROM `sky` $join WHERE";
    $i = 0;
    $values = [];
    foreach ($params as $key => $value){
      // We don't check SQL columns names as they are not user defined.
      if ($i != 0) {
        $req .= " AND";
      }
      $req .= " `sky`.`".$key."`=?";
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
    $skies = [];
    foreach ($results as $row){
      $skies[] = Sky::withRow($row);
    }
    return $skies;
  }

  public function findOneBy($params):?Sky {
    $req = "SELECT * FROM `sky` WHERE";
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
      return Sky::withRow($row);
    }
    return null;
  }

  public function findAllByTourID($tourID, $userID=null):array {
    // TODO: rename it to getAllByTourID because we return a simple array, not objects
    // We need to name all columns because of the join
    $req = "SELECT".
      " `sky`.`id`,".
      " `sky`.`spot_id`,".
      " `sky`.`image_id`,".
      " `sky`.`layer`,".
      " `sky`.`creation_date`,".
      " `sky`.`modification_date`,".
      " \"TEST\" as test,".
      " NULLIF (CONCAT(`image`.`filename`, \".\", `image`.`filetype`), \".\") AS filename".
      " FROM `sky`".
      " LEFT JOIN `image` ON `image`.`id` = `sky`.`image_id`".
      " LEFT JOIN `spot` ON `spot`.`id` = `sky`.`spot_id`".
      " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`".
      " WHERE `tour`.`id` = ?";
    $values = [$tourID];
    if ($userID){
      $req.=" AND `tour`.`user_id` = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $results = $statement->fetchAll(\PDO::FETCH_NAMED);
    //~ $skies = [];
    //~ foreach ($results as $row){
      //~ $skies[] = Sky::withRow($row);
    //~ }
    //~ return $skies;
    return $results;
  }

  public function getAllBySpotID($spotID, $userID=null):array {
    // We need to name all columns because of the join
    $req = "SELECT".
      " `sky`.`id`,".
      " `sky`.`spot_id`,".
      " `sky`.`image_id`,".
      " `sky`.`layer`,".
      " `sky`.`creation_date`,".
      " `sky`.`modification_date`,".
      " NULLIF (CONCAT(`image`.`filename`, \".\", `image`.`filetype`), \".\") AS filename,".
      " `image`.`width`,".
      " `image`.`height`".
      " FROM `sky`".
      " LEFT JOIN `image` ON `image`.`id` = `sky`.`image_id`".
      " LEFT JOIN `spot` ON `spot`.`id` = `sky`.`spot_id`".
      " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`".
      " WHERE `spot`.`id` = ?";
    $values = [$spotID];
    if ($userID){
      $req.=" AND `tour`.`user_id` = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $results = $statement->fetchAll(\PDO::FETCH_NAMED);
    return $results;
  }

  public function persist(&$sky, $copy = false):?bool {
    if (!$sky->getID() || $copy == true){
      // Create a new sky
      $statement = $this->pdo->prepare("INSERT INTO `sky` (`image_id`, `spot_id`, `layer`) values (?, ?, ?)");
      try {
        $statement->execute([
          $sky->getImageID(),
          $sky->getSpotID(),
          $sky->getLayer(),
        ]);
        $newSky = $this->find($this->pdo->lastInsertId());
        $sky = $newSky ? $newSky : $sky;
      } catch (Exception $e) {
        return false;
      }
    } else {
      // Modify the sky
      $statement = $this->pdo->prepare("UPDATE `sky` SET `image_id`=?, `spot_id`=?, `layer`=? WHERE `id`=?");
      try {
        $statement->execute([
          $sky->getImageID(),
          $sky->getSpotID(),
          $sky->getLayer(),
          $sky->getID(),
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
        "LEFT JOIN `sky` ON `sky`.`spot_id`=`spot`.`id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `sky`.`id` = ?"
      );
      $statement->execute([ $sky->getID() ]);
      return true;
    } catch (Exception $e) {
      return false;
    }
    return false;
  }
}
 
