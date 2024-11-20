<?php
namespace tour\Entity;
require_once(__DIR__."/../Autoloader.php");
@session_start();

use \tour\Entity\DB;
use \tour\Entity\User;

class SpotHasSpot implements \JsonSerializable
{
  private ?int $id = null;
  private ?int $spot1 = null;
  private ?int $spot2 = null;
  private ?int $spot1x = null;
  private ?int $spot1y = null;
  private ?int $spot2x = null;
  private ?int $spot2y = null;
  private ?int $spot1t = null;
  private ?int $spot2t= null;

  public function getID():?int {
    return $this->id;
  }

  public function getSpot1():?int {
    return $this->spot1;
  }

  public function setSpot1(int $spot1):SpotHasSpot {
    $this->spot1 = $spot1;
    return $this;
  }

  public function getSpot1x():?int {
    return $this->spot1x;
  }

  public function setSpot1x(int $spot1x):SpotHasSpot {
    $this->spot1x = $spot1x;
    return $this;
  }

  public function getSpot1y():?int {
    return $this->spot1y;
  }

  public function setSpot1y(int $spot1y):SpotHasSpot {
    $this->spot1y = $spot1y;
    return $this;
  }

  public function getSpot2():?int {
    return $this->spot2;
  }

  public function setSpot2(int $spot2):SpotHasSpot {
    $this->spot2 = $spot2;
    return $this;
  }

  public function getSpot2x():?int {
    return $this->spot2x;
  }

  public function setSpot2x(int $spot2x):SpotHasSpot {
    $this->spot2x = $spot2x;
    return $this;
  }

  public function getSpot2y():?int {
    return $this->spot2y;
  }

  public function setSpot2y(int $spot2y):SpotHasSpot {
    $this->spot2y = $spot2y;
    return $this;
  }

  public function getSpot1t():?int {
    return $this->spot1t;
  }

  public function setSpot1t(int $spot1t):SpotHasSpot {
    $this->spot1t = $spot1t;
    return $this;
  }

  public function getSpot2t():?int {
    return $this->spot2t;
  }

  public function setSpot2t(int $spot2t):SpotHasSpot {
    $this->spot2t = $spot2t;
    return $this;
  }

  public static function withRow(array $row) {
    $instance = new self();
    $instance->fill($row);
    return $instance;
  }

  private function fill(array $row) {
    $this->id = $row["id"];
    $this->spot1 = $row["spot1"];
    $this->spot2 = $row["spot2"];
    $this->spot1x = $row["spot1x"];
    $this->spot1y = $row["spot1y"];
    $this->spot2x = $row["spot2x"];
    $this->spot2y = $row["spot2y"];
    $this->spot1t = $row["spot1t"];
    $this->spot2t = $row["spot2t"];
  }

  public function jsonSerialize(): mixed {
    return [
    "id" => $this->id,
    "spot1" => $this->spot1,
    "spot2" => $this->spot2,
    "spot1x" => $this->spot1x,
    "spot1y" => $this->spot1y,
    "spot2x" => $this->spot2x,
    "spot2y" => $this->spot2y,
    "spot1t" => $this->spot1t,
    "spot2t" => $this->spot2t,
    ];
  }

  public function getObjectVars():array {
    return get_object_vars($this);
  }
}
