<?php
namespace tour\Repository;
require_once(__DIR__."/../Autoloader.php");

use \tour\Entity\DB;
use \tour\Entity\Spot;

class SpotRepository
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
      $join = " LEFT JOIN tour ON tour.id = spot.tour_id";
      $condition = " AND user_id = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare(
      "SELECT ".
      " `spot`.`id`,".
      " `spot`.`title`,".
      " `spot`.`tour_id`,".
      " `spot`.`lat`,".
      " `spot`.`lng`,".
      " `spot`.`creation_date`,".
      " `spot`.`modification_date`".
      " FROM spot $join WHERE spot.id=? $condition"
    );
    $statement->execute($values);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return Spot::withRow($row);
    }
    return null;
  }

  public function delete($id):bool {
    // Updates the tour modification date
    try {
      $statement = $this->pdo->prepare(
        "UPDATE `tour` ".
        "LEFT JOIN `spot` ON `spot`.`tour_id`=`tour`.`id` ".
        "SET `tour`.`modification_date`=NOW() WHERE `spot`.`id` = ?"
      );
      $statement->execute([ $id ]);
    } catch (Exception $e) {
      return false;
    }
    // Deletes the Spot
    $statement = $this->pdo->prepare("DELETE FROM `spot` WHERE id=?");
    return $statement->execute([$id]);
  }

  public function findAllByUserID($userID):array {
    $statement = $this->pdo->prepare("SELECT * FROM spot WHERE user_id=?");
    $statement->execute([$userID]);
    $results = $statement->fetchAll();
    $skies = [];
    foreach ($results as $row){
      $skies[] = Spot::withRow($row);
    }
    return $skies;
  }

  public function findAllBy($params, $userID=null):array {
    $join = $userID ? " LEFT JOIN tour ON tour.id = spot.tour_id" : "";
    // We need to name all columns because of the join
    $req = "SELECT".
    " `spot`.`id`,".
    " `spot`.`title`,".
    " `spot`.`tour_id`,".
    " `spot`.`lat`,".
    " `spot`.`lng`,".
    " `spot`.`creation_date`,".
    " `spot`.`modification_date`".
    "  FROM spot $join WHERE";
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
      $req.=" AND tour.user_id = ?";
      $values[] = $userID;
    }
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    $results = $statement->fetchAll();
    $skies = [];
    foreach ($results as $row){
      $skies[] = Spot::withRow($row);
    }
    return $skies;
  }

  public function findBy($params):?Spot {
    $req = "SELECT * FROM spot WHERE";
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
      return Spot::withRow($row);
    }
    return null;
  }

  public function getAllByTourID($tour, $userID=null):array {
    $joinUser = $userID ? " LEFT JOIN `tour` ON `tour`.`id` = `spot`.`tour_id`" : "";
    // We need to name all columns because of the join
    $req = "SELECT".
    " `spot`.`id`,".
    " `spot`.`title`,".
    " `spot`.`tour_id`,".
    " `spot`.`lat`,".
    " `spot`.`lng`,".
    "  COUNT(`poi`.`id`) as poi_nb,".
    "  ROW_NUMBER() OVER (Order by id) AS position,".
    " `spot`.`creation_date`,".
    " `spot`.`modification_date`".
    "  FROM   `spot` $joinUser ".
    "  LEFT JOIN `poi` ON `poi`.`spot_id`=`spot`.`id`".
    "  WHERE `spot`.`tour_id`=? ";
    $values = [$tour];
    if ($userID){
      $req.=" AND `tour`.`user_id` = ?";
      $values[] = $userID;
    }
    $req.="  GROUP BY `spot`.`id`";
    $statement = $this->pdo->prepare($req);
    $statement->execute($values);
    return $statement->fetchAll(\PDO::FETCH_NAMED);
  }

  public function persist(&$spot, $copy = false):?bool {
    if (!$spot->getID() || $copy == true){
      // Create a new spot
      $statement = $this->pdo->prepare("INSERT INTO `spot` (`title`, `tour_id`, `lat`, `lng`, `creation_date`, `modification_date`) ".
        "values (?, ?, ?, ?, COALESCE(?, DEFAULT(creation_date)), COALESCE(?, DEFAULT(modification_date)))");
      try {
        $statement->execute([
          $spot->getTitle(),
          $spot->getTourID(),
          $spot->getLat(),
          $spot->getLng(),
          $spot->getCreationDate(),
          $spot->getModificationDate(),
        ]);
        $newSpot = $this->find($this->pdo->lastInsertId());
        $spot = $newSpot ? $newSpot : $spot;
      } catch (Exception $e) {
        return false;
      }
    } else {
      // Modify the spot
      $statement = $this->pdo->prepare("UPDATE `spot` SET `title`=?, `tour_id`=?, `lat`=?, `lng`=? WHERE `id`=?");
      try {
        $statement->execute([
          $spot->getTitle(),
          $spot->getTourID(),
          $spot->getLat(),
          $spot->getLng(),
          $spot->getID(),
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
        "SET `tour`.`modification_date`=NOW() WHERE `spot`.`id` = ?"
      );
      $statement->execute([ $spot->getID() ]);
      return true;
    } catch (Exception $e) {
      return false;
    }
    return false;
  }
}
