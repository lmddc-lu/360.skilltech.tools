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
  private ?string $password = null;
  private int $share = 0;
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
    $this->password = $row["password"];
    $this->share = $row["share"];
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

  public function getPassword():?string {
    return $this->password;
  }

  public function setPassword(?string $password):Tour {
    if ($password === null){
      $this->password = null;
    } else {
      $this->password = password_hash($password, PASSWORD_DEFAULT);
    }
    return $this;
  }

  public function verifyPassword(?string $password):Bool {
    if ($this->password === null){
      return true;
    }
    return password_verify($password, $this->password);
  }

  public function getShare():int {
    return $this->share;
  }

  public function setShare(int $share):Tour {
    $this->share = $share;
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

    if ($edges == []){
      /*
       * This block is executed if the tour does not have links between 360° images yet. We will add a exception to allow
       * people to create a tour using just one image.
       */
      $spotId = null;
      // We search for the starting point
      $result = $spotRepo->find($this->startID);
      if (!$result){
        // There is no starting point set, we try to get the first spot
        $result = $spotRepo->findBy(["tour_id" => $this->id]);
      }
      if ($result){
        // We add an edge to the list that will have one unique spot
        $newEdge = new SpotHasSpot();
        $newEdge->setSpot1($result->getID());
        $newEdge->setSpot2(0);
        $newEdge->setSpot1x(0);
        $newEdge->setSpot2x(0);
        $newEdge->setSpot1y(0);
        $newEdge->setSpot2y(0);
        $newEdge->setSpot1t(0);
        $newEdge->setSpot2t(0);

        $edges[] = $newEdge;
      }
    }

    foreach ($edges as $edge){
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
        if($spot[$i] === null){
          break;
        }
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
        // We do not add the related spot if it is null (no link between the spots)
        if($spot[1] !== null){
          $spots[$id]["spots"][] = [
            // The ID of the spot on the other side of the edge
            "id" => (string)$spot[($i+1)%2]->getID(),
            "x" => $x[$i],
            "y" => $y[$i],
            "t" => $t[$i],
          ];
        }
      }
    }
    $tourArray = $this->jsonSerialize();
    $tourArray["spots"] = $spots;
    $tourArray["build_date"] = date("Y-m-d H:i:s");
    // Avoid leaking unnecessary data
    unset($tourArray["id"]);
    unset($tourArray["user_id"]);
    unset($tourArray["password"]);
    unset($tourArray["share"]);
    return json_encode($tourArray);
  }

  private function getNewID():string {
    $str = "";
    $chars = "23456789ABCDEFGHJKMNPQRSTUVWXYZ";
    for($i=0; $i<10; $i++){
        $str .= $chars[random_int(0, 30)];
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
    "password" => $this->password,
    "share" => $this->share,
    "creation_date" => $this->creationDate,
    "modification_date" => $this->modificationDate,
    ];
  }
}
