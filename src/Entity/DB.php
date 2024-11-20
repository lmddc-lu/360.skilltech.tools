<?php
namespace tour\Entity;
require_once(__DIR__ . "/../config.php");


class DB
{
  private static $connection = null;

  public function __construct() {
    if (self::$connection == null){
      self::$connection = new \PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
    }
  }

  public function getConnection(){
    return self::$connection;
  }

}
