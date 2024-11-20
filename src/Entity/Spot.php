<?php
namespace tour\Entity;
require_once(__DIR__."/../Autoloader.php");
@session_start();

use \tour\Entity\User;

class Spot implements \JsonSerializable
{
  private ?int $id = null;
  private ?string $title = null;
  private ?int $tourID = null;
  private ?float $lat = null;
  private ?float $lng = null;
  private ?string $creationDate = null;
  private ?string $modificationDate = null;

  public function __construct() {
  }

  public function getID():?int {
    return $this->id;
  }

  public function getTitle():?string {
    return $this->title;
  }

  public function setTitle(string $title):Spot {
    $this->title = $title;
    return $this;
  }

  public function getTourID():?string {
    return $this->tourID;
  }

  public function setTourID(string $tourID):Spot {
    $this->tourID = $tourID;
    return $this;
  }

  public function getLat():?string {
    return $this->lat;
  }

  public function setLat(string $lat):Spot {
    $this->lat = $lat;
    return $this;
  }

  public function getLng():?string {
    return $this->lng;
  }

  public function setLng(string $lng):Spot {
    $this->lng = $lng;
    return $this;
  }

  public function getCreationDate():?string {
    return $this->creationDate;
  }

  public function setCreationDate(?string $date):Spot {
    $this->creationDate = $date;
    return $this;
  }

  public function getModificationDate():?string {
    return $this->modificationDate;
  }

  public function setModificationDate(?string $date):Spot {
    $this->modificationDate = $date;
    return $this;
  }

  public static function withRow(array $row) {
    $instance = new self();
    $instance->fill($row);
    return $instance;
  }

  private function fill(array $row) {
    $this->id = $row["id"];
    $this->title = $row["title"];
    $this->tourID = $row["tour_id"];
    $this->lat = $row["lat"];
    $this->lng = $row["lng"];
    $this->creationDate = $row["creation_date"];
    $this->modificationDate = $row["modification_date"];
  }

  private function getNewID():string {
    $str = "";
    $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    for($i=0; $i<10; $i++){
        $str .= $chars[random_int(0, 61)];
    }
    return $str;
  }

  public function jsonSerialize(): mixed {
    return [
    "id" => $this->id,
    "title" => $this->title,
    "tour_id" => $this->tourID,
    "lat" => $this->lat,
    "lng" => $this->lng,
    "creation_date" => $this->creationDate,
    "modification_date" => $this->modificationDate,
    ];    
  }
}
