<?php
Class Map {
  private $id;
  private $title;
  private $description;
  private $owner;
  private $layers;
  private $layers_json;

  public function __construct($id) {
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("SELECT * FROM maps WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $execute = $stmt->execute();
    while ($obj = $stmt->fetch(PDO::FETCH_OBJ)) {
      $this->id = $obj->id;
      $this->title = $obj->title;
      $this->description = $obj->description;
      $this->owner = $obj->owner;
      $this->layers = json_decode($obj->layers);
    }
    $layers = array();
    foreach ($this->layers as $layer){
      $iLayer = new Layer($layer);
      array_push($layers, $iLayer->to_array());
    };
    $this->layers_json = $layers;
  }

  public static function get_maps_by_user($user_id) {
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("SELECT * FROM maps WHERE owner = :ow ORDER BY title ASC");
    $stmt->bindParam(":ow", $user_id);
    $execute = $stmt->execute();
    $maps = array();
    while ($obj = $stmt->fetch(PDO::FETCH_OBJ)) {
      $map = array();
      $map['id'] = $obj->id;
      $map['title'] = $obj->title;
      $map['description'] = $obj->description;
      $map['owner'] = $obj->owner;
      $map['layers'] = json_decode($obj->layers);
      array_push($maps, $map);
    }
    return $maps;
  }

  public function to_array() {
    $arr = array();
    $arr['id'] = $this->id;
    $arr['title'] = $this->title;
    $arr['description'] = $this->description;
    $arr['owner'] = $this->owner;
    $arr['layers'] = $this->layers;
    $arr['layers_json'] = $this->layers_json;
    return $arr;
  }

}

