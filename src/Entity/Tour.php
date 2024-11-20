<?php
namespace tour\Entity;
require_once(__DIR__."/../Autoloader.php");
@session_start();

use \tour\Entity\DB;
use \tour\Entity\User;
use \tour\Repository\POIRepository;
use \tour\Repository\SpotHasSpotRepository;
use \tour\Repository\SpotRepository;
use \tour\Repository\SkyRepository;
use \tour\Repository\TourRepository;

class Tour implements \JsonSerializable
{
  private ?int $id = null;
  private ?string $title = null;
  private ?string $description = null;
  private ?string $filename = null;
  private ?string $author = null;
  private ?string $license = null;
  private ?int $startID = null;
  private ?int $thumbID = null;
  private ?int $userID = null;
  private ?string $creationDate = null;
  private ?string $modificationDate = null;


  public static function withRow(array $row) {
    $instance = new self();
    $instance->fill($row);
    return $instance;
  }

  private function fill(array $row) {
    $this->id = $row["id"];
    $this->title = $row["title"];
    $this->description = $row["description"];
    $this->filename = $row["filename"];
    $this->author = $row["author"];
    $this->license = $row["license"];
    $this->startID = $row["start_id"];
    $this->thumbID = $row["thumb_id"];
    $this->userID = $row["user_id"];
    $this->creationDate = $row["creation_date"];
    $this->modificationDate = $row["modification_date"];
  }

  public function getId():?int {
    return $this->id;
  }

  public function getTitle():?string {
    return $this->title;
  }

  public function setTitle(?string $title):Tour {
    $this->title = $title;
    return $this;
  }

  public function getDescription():?string {
    return $this->description;
  }

  public function setDescription(?string $description):Tour {
    $this->description = $description;
    return $this;
  }

  public function getFilename():?string {
    return $this->filename;
  }

  public function setFilename($force = null):?Tour {
    // The filename is generated automatically
    if ($this->filename == null || $force) {
      $repo = new TourRepository();
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

  public function getAuthor():?string {
    return $this->author;
  }

  public function setAuthor(?string $author):Tour {
    $this->author = $author;
    return $this;
  }

  public function getLicense():?string {
    return $this->license;
  }

  public function setLicense(?string $license):Tour {
    $this->license = $license;
    return $this;
  }
  public function getStartID():?int {
    return $this->startID;
  }

  public function setStartID(?int $startID):Tour {
    $this->startID = $startID;
    return $this;
  }

  public function getThumbID():?int {
    return $this->thumbID;
  }

  public function setThumbID(?int $thumbID):Tour {
    $this->thumbID = $thumbID;
    return $this;
  }

  public function getUserID():?int {
    return $this->userID;
  }

  public function setUserID(int $userID):Tour {
    $this->userID = $userID;
    return $this;
  }

  public function getCreationDate():?string {
    return $this->creationDate;
  }

  public function setCreationDate(?string $date):Tour {
    $this->creationDate = $date;
    return $this;
  }

  public function getModificationDate():?string {
    return $this->modificationDate;
  }

  public function setModificationDate(?string $date):Tour {
    $this->modificationDate = $date;
    return $this;
  }

  public function getSpotHasSpots():array {
    $repo = new SpotHasSpotRepository();
    return $repo->findAllByTourID($this->id);
  }

  /*
   * Generates the full JSON of the tour
   */
  public function getJSON(){
    $stripPOI = function(array $poi): array{
      unset($poi["id"]);
      unset($poi["image_id"]);
      unset($poi["spot_id"]);
      unset($poi["creation_date"]);
      unset($poi["modification_date"]);
      return $poi;
    };
    $spotRepo = new SpotRepository();
    $skyRepo = new SkyRepository();
    $poiRepo = new POIRepository();
    $edges = $this->getSpotHasSpots();
    $spots = [];
    foreach ($edges as $edge){
      //~ $expEdge = $edge->getObjectVars();
      $spot[0] = $spotRepo->find($edge->getSpot1());
      $spot[1] = $spotRepo->find($edge->getSpot2());
      $x[0] = $edge->getSpot1x();
      $x[1] = $edge->getSpot2x();
      $y[0] = $edge->getSpot1y();
      $y[1] = $edge->getSpot2y();
      $t[0] = $edge->getSpot1t();
      $t[1] = $edge->getSpot2t();
      // Loop over the two spots of the edge
      for ($i=0; $i<=1; $i++){
        $id = (string)$spot[$i]->getID(); // ID of the current spot
        if (!isset($spots[$id])){
          // We create an new entry for this spot, the key
          // will be the id of the spot in string format
          $spots[$id] = [
            "id" => $id,
            "title" => $spot[$i]->getTitle(),
            "spots" => [],
            "skies" => [],
            "pois" => [],
          ];
          // Search all skies connected to this spot
          // This is only executed once for each spot
          $skies = $skyRepo->getAllBySpotID($id);
          foreach ($skies as $sky){
            // We add each sky image filename to the array, the key being the layer
            $spots[$id]["skies"][$sky["layer"]]["filename"] = $sky["filename"];
            $spots[$id]["skies"][$sky["layer"]]["width"] = $sky["width"];
            $spots[$id]["skies"][$sky["layer"]]["height"] = $sky["height"];
          }

          // Search all POIs connected to this spot
          // This is only executed once for each spot
          $pois = $poiRepo->getAllBySpotID($id);
          if ($pois) {
            $spots[$id]["pois"] = array_map($stripPOI, $pois);
          }
        }
        // The ID of the spot on the other side of the edge
        $spots[$id]["spots"][] = [
          "id" => (string)$spot[($i+1)%2]->getID(),
          "x" => $x[$i],
          "y" => $y[$i],
          "t" => $t[$i],
        ];

      }
    }
    $tourArray = $this->jsonSerialize();
    $tourArray["spots"] = $spots;
    $tourArray["build_date"] = date("Y-m-d H:i:s");
    // Avoid leaking unnecessary data
    unset($tourArray["id"]);
    unset($tourArray["user_id"]);
    return json_encode($tourArray);
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
    "description" => $this->description,
    "filename" => $this->filename,
    "author" => $this->author,
    "license" => $this->license,
    "start_id" => $this->startID,
    "thumb_id" => $this->thumbID,
    "user_id" => $this->userID,
    "creation_date" => $this->creationDate,
    "modification_date" => $this->modificationDate,
    ];
  }
}
