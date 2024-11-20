<?php
namespace tour\Repository;

use \tour\Entity\DB;
use \tour\Entity\User;
use \tour\Entity\Tour;

class UserRepository
{
  private $pdo = null;

  public function __construct() {
    $db = new DB();
    $this->pdo = $db->getConnection();
  }

  public static function find($id) {
    $statement = $this->pdo->prepare("SELECT * FROM user WHERE id=?");
    $statement->execute([$id]);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return User::withRow($row);
    }
    return null;
  }

  public function findByEmail($email) {
    $statement = $this->pdo->prepare("SELECT * FROM user WHERE email=?");
    $statement->execute([$email]);
    $row = $statement->fetch(\PDO::FETCH_ASSOC);
    if ($row){
      return User::withRow($row);
    }
    return null;
  }

  public function create($userInfo):?User {
    $directory = $this->getNewId();
    $email = $userInfo->email;
    $statement = $this->pdo->prepare(
      "INSERT INTO user (email, directory) ".
      "VALUES (?, ?)"
    );
    if ($statement->execute([$email, $directory])) return $this->findByEmail($email);
    return null;
  }

  private function getNewId():string {
    $str = "";
    $chars = "123456789abcdefghjkmnpqrstuvwxyz";
    for($i=0; $i<12; $i++){
        $str .= $chars[random_int(0, 31)];
    }
    return $str;
  }

  public function persist(&$user):?bool {
    if ($user->getID()){
      // Modify the User
      $statement = $this->pdo->prepare(
        "UPDATE `user` ".
        "SET `email`=?, `directory`=?, `onboarding`=? ".
        "WHERE `id`=?"
      );
      try {
        $statement->execute([
          $user->getEmail(),
          $user->getDirectory(),
          $user->getOnboarding(),
          $user->getID(),
        ]);
        return true;
      } catch (Exception $e) {
        return false;
      }
    }
    return false;
  }
}
 
