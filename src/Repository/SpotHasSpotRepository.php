<?php
namespace tour\Repository;
require_once(__DIR__."/../Autoloader.php");

use \tour\Entity\DB;
use \tour\Entity\Spot;
use \tour\Entity\SpotHasSpot;

class SpotHasSpotRepository
{
  private $pdo = null;

  public function __construct() {
    $db = new DB();
    $this->pdo = $db->getConnection();
  }

  public function find($id, $userID=null) {
    $values = [$id];
    $join = "";
    $condition = "";
    if ($userID){
      $join = " LEFT JOIN `spot` ON `spot`.`id` = `spot_has_spot`.`spot1`".
              " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`";
      $condition = " AND `tour`.`user_id` = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare(
      "SELECT ".
      " `spot_has_spot`.`id`,".
      " `spot_has_spot`.`spot1`,".
      " `spot_has_spot`.`spot2`,".
      " `spot_has_spot`.`spot1x`,".
      " `spot_has_spot`.`spot2x`,".
      " `spot_has_spot`.`spot1y`,".
      " `spot_has_spot`.`spot2y`,".
      " `spot_has_spot`.`spot1t`,".
      " `spot_has_spot`.`spot2t`".
      " FROM spot_has_spot $join WHERE spot_has_spot.id=? $condition"
    );
    $statement->execute($values);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return SpotHasSpot::withRow($row);
    }
    return null;
  }

  public function delete($id):bool {

    // Updates the tour modification date
    try {
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `spot` ON `spot`.`tour_id`=`tour`.`id` ".
        "LEFT JOIN `spot_has_spot` ON `spot_has_spot`.`spot1`=`spot`.`id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `spot_has_spot`.`id` = ?"
      );
      $statement->execute([ $id ]);
    } catch (Exception $e) {
      return false;
    }

    // Deletes the SpotHasSpot
    $statement = $this->pdo->prepare("DELETE FROM `spot_has_spot` WHERE id=?");
    return $statement->execute([$id]);
  }

  public function deleteAllByTourID($tourID):bool {
    $statement = $this->pdo->prepare(
      "DELETE `spot_has_spot` ".
      "FROM `spot_has_spot` ".
      "LEFT JOIN `spot` ON `spot`.`id` = `spot_has_spot`.`spot1` OR `spot`.`id` = `spot_has_spot`.`spot2` ".
      "WHERE `spot`.`tour_id`=?"
    );
    return $statement->execute([$tourID]);
  }

  public function findAllBy($params, $userID=null):array {
    $join = "";
    if ($userID){
      $join = " LEFT JOIN `spot` ON `spot`.`id` = `spot_has_spot`.`spot1`".
              " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`";
    }
    // We need to name all columns because of the join
    $req = "SELECT".
      " `spot_has_spot`.`id`,".
      " `spot_has_spot`.`spot1`,".
      " `spot_has_spot`.`spot2`,".
      " `spot_has_spot`.`spot1x`,".
      " `spot_has_spot`.`spot2x`,".
      " `spot_has_spot`.`spot1y`,".
      " `spot_has_spot`.`spot2y`,".
      " `spot_has_spot`.`spot1t`,".
      " `spot_has_spot`.`spot2t`".
      "  FROM spot_has_spot $join WHERE";
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

    if ($userID){
      $req .= " AND `tour`.`user_id` = ?";
      $values[] = $userID;
    }

    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $results = $statement->fetchAll();
    $spotHasSpots = [];
    foreach ($results as $row){
      $spotHasSpots[] = SpotHasSpot::withRow($row);
    }
    return $spotHasSpots;
  }

  public function findOneBy($params):?SpotHasSpot {
    $req = "SELECT * FROM spot_has_spot WHERE";
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
      return SpotHasSpot::withRow($row);
    }
    return null;
  }

  public function findAllByTourID($tourID, $userID=null):array {

    // We need to name all columns because of the join
    $req = "SELECT".
      " `spot_has_spot`.`id`,".
      " `spot_has_spot`.`spot1`,".
      " `spot_has_spot`.`spot2`,".
      " `spot_has_spot`.`spot1x`,".
      " `spot_has_spot`.`spot2x`,".
      " `spot_has_spot`.`spot1y`,".
      " `spot_has_spot`.`spot2y`,".
      " `spot_has_spot`.`spot1t`,".
      " `spot_has_spot`.`spot2t`".
      "  FROM spot_has_spot".
      " LEFT JOIN `spot` ON `spot`.`id` = `spot_has_spot`.`spot1`".
      " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`".
      " WHERE `tour`.`id` = ?";

    $values = [$tourID];
    if ($userID){
      $req .= " AND `tour`.`user_id` = ?";
      $values[] = $userID;
    }

    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $results = $statement->fetchAll();
    $spotHasSpots = [];
    foreach ($results as $row){
      $spotHasSpots[] = SpotHasSpot::withRow($row);
    }
    return $spotHasSpots;
  }

  public function persist(&$spotHasSpot, $copy = false):?bool {
    if (!$spotHasSpot->getID() || $copy == true){
      // Create a new spot
      $statement = $this->pdo->prepare("INSERT INTO `spot_has_spot` (`spot1`, `spot2`, `spot1x`, `spot1y`, `spot2x`, `spot2y`, `spot1t`, `spot2t`) ".
        "values (?, ?, ?, ?, ?, ?, ?, ?)");
      try {
        $statement->execute([
          $spotHasSpot->getSpot1(),
          $spotHasSpot->getSpot2(),
          $spotHasSpot->getSpot1X(),
          $spotHasSpot->getSpot1Y(),
          $spotHasSpot->getSpot2X(),
          $spotHasSpot->getSpot2Y(),
          $spotHasSpot->getSpot1t(),
          $spotHasSpot->getSpot2t(),
        ]);
        $newSpotHasSpot = $this->find($this->pdo->lastInsertId());
        $spotHasSpot = $newSpotHasSpot ? $newSpotHasSpot : $spotHasSpot;
      } catch (Exception $e) {
        return false;
      }
    } else {
      // Modify the spot
      $statement = $this->pdo->prepare("UPDATE `spot_has_spot` SET `spot1`=?, `spot2`=?, `spot1x`=?, `spot1y`=?, `spot2x`=?, `spot2y`=?, `spot1t`=?, `spot2t`=? ".
        "WHERE `id`=?");
      try {
        $statement->execute([
          $spotHasSpot->getSpot1(),
          $spotHasSpot->getSpot2(),
          $spotHasSpot->getSpot1X(),
          $spotHasSpot->getSpot1Y(),
          $spotHasSpot->getSpot2X(),
          $spotHasSpot->getSpot2Y(),
          $spotHasSpot->getSpot1t(),
          $spotHasSpot->getSpot2t(),
          $spotHasSpot->getID(),
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
        "LEFT JOIN `spot_has_spot` ON `spot_has_spot`.`spot1`=`spot`.`id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `spot_has_spot`.`id` = ?"
      );
      $statement->execute([ $spotHasSpot->getID() ]);
      return true;
    } catch (Exception $e) {
      return false;
    }
    return false;
  }
}
