<?php
namespace tour\Entity;
require_once(__DIR__."/../Autoloader.php");
use \tour\Repository\ImageRepository;
@session_start();

class Image implements \JsonSerializable
{
  private ?int $id = null;
  private ?int $userID = null;
  private ?string $srcFilename = null;
  private ?string $filename = null;
  private ?string $filetype = null;
  private ?string $title = null;
  private ?int $filesize = null;
  private ?int $width = null;
  private ?int $height = null;
  private ?string $creationDate = null;
  private ?string $modificationDate = null;

  public function __construct() {
  }

  public function getID():?int {
    return $this->id;
  }

  public function getUserID():?int {
    return $this->userID;
  }

  public function setUserID(int $userID):Image {
    $this->userID = $userID;
    return $this;
  }

  public function getSrcFilename():?string {
    return $this->srcFilename;
  }

  public function setSrcFilename(?string $srcFilename):Image {
    $this->srcFilename = $srcFilename;
    return $this;
  }

  public function getFilename():?string {
    return $this->filename;
  }

  public function setFilename($force = null):?Image {
    // The filename is generated automatically
    if ($this->filename == null || $force) {

      $repo = new ImageRepository();
      $result = null;
      // We try to get a unused id 5 times
      for ($i=0; $i<5; $i++){
        $newId = $this->getNewID();
        $result = $repo->findOneBy(["filename" => $newId]);
        if (!$result){
          $this->filename = $newId;
          break;
        }
      }
      if ($result){
        // TODO: log error
      }
    }
    return $this;
  }

  public function getFiletype():?string {
    return $this->filetype;
  }

  public function setFiletype(?string $filetype):Image {
    $this->filetype = $filetype;
    return $this;
  }

  public function getFilesize():?int {
    return $this->filesize;
  }

  public function setFilesize(?int $filesize):Image {
    $this->filesize = $filesize;
    return $this;
  }

  public function getWidth():?int {
    return $this->width;
  }

  public function setWidth(?int $width):Image {
    $this->width = $width;
    return $this;
  }

  public function getHeight():?int {
    return $this->height;
  }

  public function setHeight(?int $height):Image {
    $this->height = $height;
    return $this;
  }

  public function getTitle():?string {
    return $this->title;
  }

  public function setTitle(string $title):Image {
    $this->title = $title;
    return $this;
  }

  public function setCreationDate(?string $date):Image {
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
    $this->userID = $row["user_id"];
    $this->srcFilename = $row["src_filename"];
    $this->filename = $row["filename"];
    $this->filetype = $row["filetype"];
    $this->filesize = $row["filesize"];
    $this->title = $row["title"];
    $this->width = $row["width"];
    $this->height = $row["height"];
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
    "user_id" => $this->userID,
    "src_filename" => $this->srcFilename,
    "filename" => $this->filename,
    "filetype" => $this->filetype,
    "filesize" => $this->filesize,
    "title" => $this->title,
    "width" => $this->width,
    "height" => $this->height,
    "creation_date" => $this->creationDate,
    "modification_date" => $this->modificationDate,
    ];    
  }
}
