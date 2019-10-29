<?php
Class Map {
  private $id;
  private $title;
  private $description;
  private $owner;
  private $layers;
  private $layers_json;
  private $envelope_json;
  private $centroid_json;

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
    $envelopes = array(
      "type" => "FeatureCollection",
      "features" => array()
    );
    
    foreach ($this->layers as $layer){
      //  we need to calculate the bounding box for each layer
      //  to generate an overall bounding box
      //  from which we can find the center
      $iLayer = new Layer($layer);
      $layer_arr = $iLayer->to_array();
      $w = geoPHP::load($layer_arr['layer_envelope'], 'wkt');
      $env = $w->envelope();
      $envJson = $env->out('json');
      array_push($envelopes['features'], $envJson);

      array_push($layers, $iLayer->to_array());
    };
    $this->layers_json = $layers;
    //  now we calculate the envelope and centroid from $envelopes array
    $i = geoPHP::load(json_encode($envelopes), 'json');
    $envelope = $i->envelope();
    
    $this->envelope_json = json_decode($envelope->out('json'));
    $centroid = $i->centroid();
    $this->centroid_json = json_decode($centroid->out('json'));
  }

  public function reconstruct(){
    $id = $this->id;
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
    $envelopes = array(
      "type" => "FeatureCollection",
      "features" => array()
    );
    
    foreach ($this->layers as $layer){
      //  we need to calculate the bounding box for each layer
      //  to generate an overall bounding box
      //  from which we can find the center
      $iLayer = new Layer($layer);
      $layer_arr = $iLayer->to_array();
      $w = geoPHP::load($layer_arr['layer_envelope'], 'wkt');
      $env = $w->envelope();
      $envJson = $env->out('json');
      array_push($envelopes['features'], $envJson);

      array_push($layers, $iLayer->to_array());
    };
    $this->layers_json = $layers;
    //  now we calculate the envelope and centroid from $envelopes array
    $i = geoPHP::load(json_encode($envelopes), 'json');
    $envelope = $i->envelope();
    
    $this->envelope_json = json_decode($envelope->out('json'));
    $centroid = $i->centroid();
    $this->centroid_json = json_decode($centroid->out('json'));
  }

  public function add_layer ($layer_id) {
    $map_id = $this->id;
    $layers = $this->layers;
    array_push($layers, $layer_id);
    $json = json_encode($layers);
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("UPDATE maps SET layers = :js WHERE id = :id");
    $stmt->bindParam(":id", $map_id);
    $stmt->bindParam(":js", $json);
    $execute = $stmt->execute();
    return $execute;
  }

  public static function create_map($user_id, $map_title, $map_desc){
    $pdo = Data_Connecter::get_connection();
    $layers = "[]";
    $stmt = $pdo->prepare("INSERT INTO maps (title, description, owner, layers) VALUES (:t, :d, :o, :l)");
    $stmt->bindParam(":t", $map_title);
    $stmt->bindParam(":d", $map_desc);
    $stmt->bindParam(":o", $user_id);
    $stmt->bindParam(":l", $layers);
    $execute = $stmt->execute();
    $insert_id = $pdo->lastInsertId();
    $error = $pdo->errorInfo();
    return $insert_id;
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

  public function remove_layer ($layer_id) {
    $map_id = $this->id;
    $layers = $this->layers;
    $single_arr = array();
    array_push($single_arr, $layer_id);
    //  new_arr is an associative array (sometimes(!?!)
    $new_arr = array_diff($this->layers, $single_arr);
    $final_arr = array();
    foreach($new_arr as $arr){
      array_push($final_arr, $arr);
    }
    $json = json_encode($final_arr);
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("UPDATE maps SET layers = :js WHERE id = :id");
    $stmt->bindParam(":id", $map_id);
    $stmt->bindParam(":js", $json);
    $execute = $stmt->execute();
    return $execute;
  }

  public function to_array() {
    $arr = array();
    $arr['id'] = $this->id;
    $arr['title'] = $this->title;
    $arr['description'] = $this->description;
    $arr['owner'] = $this->owner;
    $arr['layers'] = $this->layers;
    $arr['layers_json'] = $this->layers_json;
    $arr['envelope_json'] = $this->envelope_json;
    $arr['centroid_json'] = $this->centroid_json;
    return $arr;
  }

}

