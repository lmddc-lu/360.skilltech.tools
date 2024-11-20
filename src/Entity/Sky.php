<?php
namespace tour\Entity;
require_once(__DIR__."/../Autoloader.php");
@session_start();

use \tour\Entity\DB;
use \tour\Entity\User;

class Sky implements \JsonSerializable
{
  private ?int $id = null;
  private ?int $imageID = null;
  private ?int $spotID = null;
  private ?int $layer = null;
  private ?string $creationDate = null;
  private ?string $modificationDate = null;

  public function __construct() {
  }

  public function getID():?int {
    return $this->id;
  }

  public function getImageID():?int {
    return $this->imageID;
  }

  public function setImageID(?int $imageID):Sky {
    $this->imageID = $imageID;
    return $this;
  }

  public function getSpotID():?int {
    return $this->spotID;
  }

  public function setSpotID(?int $spotID):Sky {
    $this->spotID = $spotID;
    return $this;
  }

  public function getLayer():?int {
    return $this->layer;
  }

  public function setLayer(int $layer):Sky {
    $this->layer = $layer;
    return $this;
  }

  public function setCreationDate(?string $date):Sky {
    $this->creationDate = $date;
    return $this;
  }

  public static function withRow(array $row) {
    $instance = new self();
    $instance->fill($row);
    return $instance;
  }

  private function fill(array $row) {
    $this->id = $row["id"];
    $this->imageID = $row["image_id"];
    $this->spotID = $row["spot_id"];
    $this->layer = $row["layer"];
    $this->creationDate = $row["creation_date"];
    $this->modificationDate = $row["modification_date"];
  }

  public function jsonSerialize(): mixed {
    return [
    "id" => $this->id,
    "image_id" => $this->spotID,
    "spot_id" => $this->spotID,
    "layer" => $this->layer,
    "creation_date" => $this->creationDate,
    "modification_date" => $this->modificationDate,
    ];
  }
}
