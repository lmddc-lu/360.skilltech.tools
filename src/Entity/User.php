<?php
namespace tour\Entity;
require_once(__DIR__."/../Autoloader.php");

use \tour\Entity\DB;
use \tour\Repository\TourRepository;
@session_start();
class User implements \JsonSerializable
{
  private ?int $id = null;
  private ?int $onboarding = null;
  private ?string $email = null;
  private ?string $directory = null;

  public function __construct() {
  }

  public function getId():?int {
    return $this->id;
  }

  public function getEmail():?string {
    return $this->email;
  }

  public function getOnboarding():?int {
    return $this->onboarding;
  }

  public function setOnboarding(?int $onboarding):User {
    $this->onboarding = $onboarding;
    return $this;
  }

  public function getDirectory():?string {
    return $this->directory;
  }

  public function getTours(){
    $repo = new TourRepository();
    return $repo->findAllByUserID($this->id);
  }

  public static function withRow(array $row) {
    $instance = new self();
    $instance->fill($row);
    return $instance;
  }

  private function fill(array $row) {
    $this->id = $row["id"];
    $this->onboarding = $row["onboarding"];
    $this->email = $row["email"];
    $this->directory = $row["directory"];
  }

  private function getNewId():string {
    $str = "";
    $chars = "123456789abcdefghjkmnpqrstuvwxyz";
    for($i=0; $i<12; $i++){
        $str .= $chars[random_int(0, 31)];
    }
    return $str;
  }

  public function jsonSerialize(): mixed {
    return [
      'email' => $this->email,
      'directory' => $this->directory,
      'onboarding' => $this->onboarding
    ];
  }
}
