<?php
namespace tour\Entity;
require_once(__DIR__."/../Autoloader.php");
require_once(__DIR__."/../config.php");
@session_start();

class POI implements \JsonSerializable
{
  private ?int $id = null;
  private ?string $icon = null;
  private ?string $title = null;
  private ?string $text = null;
  private ?int $imageID = null;
  private ?int $spotID = null;
  private ?int $x = null;
  private ?int $y = null;
  private ?int $layer = null;
  private ?int $template = null;
  private ?string $creationDate = null;
  private ?string $modificationDate = null;

  public function __construct() {
  }

  public function getID():?int {
    return $this->id;
  }

  public function getIcon():?string {
    return $this->icon;
  }

  public function setIcon(string $icon):POI {
    if (in_array($icon, POI_ICONS)){
      $this->icon = $icon;
    }
    return $this;
  }

  public function getTitle():?string {
    return $this->title;
  }

  public function setTitle(string $title):POI {
    $this->title = $title;
    return $this;
  }

  public function getText():?string {
    return $this->text;
  }

  public function setText(string $text):POI {
    $this->text = $text;
    return $this;
  }

  public function getImageID():?int {
    return $this->imageID;
  }

  public function setImageID(?int $imageID):POI {
    $this->imageID = $imageID;
    return $this;
  }

  public function getSpotID():?int {
    return $this->spotID;
  }

  public function setSpotID(int $spotID):POI {
    $this->spotID = $spotID;
    return $this;
  }

  public function getX():?int {
    return $this->x;
  }

  public function setX(int $x):POI {
    $this->x = $x;
    return $this;
  }

  public function getY():?int {
    return $this->y;
  }

  public function setY(int $y):POI {
    $this->y = $y;
    return $this;
  }

  public function getLayer():?int {
    return $this->layer;
  }

  public function setLayer(int $layer):POI {
    $this->layer = $layer;
    return $this;
  }

  public function getTemplate():?int {
    return $this->template;
  }

  public function setTemplate(int $template):POI {
    $this->template = $template;
    return $this;
  }

  public function getCreationDate():?string {
    return $this->creationDate;
  }

  public function setCreationDate(?string $date):POI {
    $this->creationDate = $date;
    return $this;
  }

  public function getModificationDate():?string {
    return $this->modificationDate;
  }

  public static function withRow(array $row) {
    $instance = new self();
    $instance->fill($row);
    return $instance;
  }

  private function fill(array $row) {
    $this->id = $row["id"];
    $this->icon = $row["icon"];
    $this->title = $row["title"];
    $this->text = $row["text"];
    $this->imageID = $row["image_id"];
    $this->spotID = $row["spot_id"];
    $this->x = $row["x"];
    $this->y = $row["y"];
    $this->layer = $row["layer"];
    $this->template = $row["template"];
    $this->creationDate = $row["creation_date"];
    $this->modificationDate = $row["modification_date"];
  }

  public function jsonSerialize(): mixed {
    return [
    "id" => $this->id,
    "icon" => $this->icon,
    "title" => $this->title,
    "text" => $this->text,
    "image_id" => $this->imageID,
    "spot_id" => $this->spotID,
    "x" => $this->x,
    "y" => $this->y,
    "layer" => $this->layer,
    "template" => $this->template,
    "creation_date" => $this->creationDate,
    "modification_date" => $this->modificationDate,
    ];    
  }
}
