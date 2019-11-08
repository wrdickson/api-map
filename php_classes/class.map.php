<?php
Class Map {
  private $id;
  private $title;
  private $description;
  private $owner;
  private $layers;
  private $is_empty;
  private $centroid;
  private $envelope;
  private $layers_json;
  private $envelope_json;
  private $centroid_json;

  public function __construct($id) {
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("SELECT id, title, description, owner, layers, is_empty, ST_AsWKT(envelope) as envelope, ST_AsWKT(centroid) as centroid FROM maps WHERE id = :id");
    $stmt->bindParam(":id", $id);
    $execute = $stmt->execute();
    while ($obj = $stmt->fetch(PDO::FETCH_OBJ)) {
      $this->id = $obj->id;
      $this->title = $obj->title;
      $this->description = $obj->description;
      $this->owner = $obj->owner;
      $this->layers = json_decode($obj->layers);
      $this->is_empty = (bool)$obj->is_empty;
      $this->envelope = $obj->envelope;
      $this->centroid = $obj->centroid;

    }
    $layers = array();
    //  $envelope is a geoJson object that will hold all the layers
    //  from which we will calculate the overall envelope
    $envelopes = array(
      "type" => "FeatureCollection",
      "features" => array()
    );
    //  iterate through the layers
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
    //  in the case of an empty map, this will throw an error
    if ($i != false) {
      $envelope = $i->envelope();
      //  set the property
      $this->envelope_json = json_decode($envelope->out('json'));
      //  calcuate the centroid
      $centroid = $i->centroid();
      //  set the property
      $this->centroid_json = json_decode($centroid->out('json'));
    }
  }

  public function add_layer ($layer_id) {
    $map_id = $this->id;
    $layers = $this->layers;
    array_push($layers, $layer_id);
    $this->layers = $layers;
    return $this->update_to_db();
  }

  public static function create_map($user_id, $map_title, $map_desc){
    $pdo = Data_Connecter::get_connection();
    $layers = "[]";
    $is_empty = "1";
    $stmt = $pdo->prepare("INSERT INTO maps (title, description, is_empty, owner, layers) VALUES (:t, :d, :ie, :o, :l)");
    $stmt->bindParam(":t", $map_title);
    $stmt->bindParam(":d", $map_desc);
    $stmt->bindParam(":ie", $is_empty);
    $stmt->bindParam(":o", $user_id);
    $stmt->bindParam(":l", $layers);
    $execute = $stmt->execute();
    $insert_id = $pdo->lastInsertId();
    $error = $pdo->errorInfo();
    return $insert_id;
  }

  public static function get_maps_by_user($user_id) {
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("SELECT id, title, description, owner, layers, is_empty, ST_AsWKT(envelope) as envelope, ST_AsWKT(centroid) as centroid FROM maps WHERE owner = :ow ORDER BY title ASC");
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
      $map['envelope'] = $obj->envelope;
      $e = geoPHP::load($obj->envelope);
      if ($e) {
        $map['envelope_json'] = json_decode($e->out('json'), true);
      };
      $map['centroid'] = $obj->centroid;
      $c = geoPHP::load($obj->centroid);
      if ($c) {
        $map['centroid_json'] = json_decode($c->out('json'),true);
      }
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
    $this->layers = $final_arr;
    return $this->update_to_db();
  }

  public function to_array() {
    $arr = array();
    $arr['id'] = $this->id;
    $arr['title'] = $this->title;
    $arr['description'] = $this->description;
    $arr['owner'] = $this->owner;
    $arr['layers'] = $this->layers;
    $arr['is_empty'] = $this->is_empty;
    $arr['centroid'] = $this->centroid;
    $arr['envelope'] = $this->envelope;
    $arr['layers_json'] = $this->layers_json;
    $arr['envelope_json'] = $this->envelope_json;
    $arr['centroid_json'] = $this->centroid_json;
    return $arr;
  }

  public function update_description ($new_description){
    $this->description = $new_description;
    return $this->update_to_db();
  }

  public function update_title ($new_title) {
    $this->title = $new_title;
    return $this->update_to_db();
  }

  public function update_to_db () {
    //  the first thing we do is calculate centroid and envelope
    $layers = array();
    //  $envelope is a geoJson object that will hold all the layers
    //  from which we will calculate the overall envelope
    $envelopes = array(
      "type" => "FeatureCollection",
      "features" => array()
    );
    //  iterate through the layers
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
    //  if the map is empty, we cannot calculate centroid and envelope
    if ($i) {
      $envelope = $i->envelope();
      //  set the properties
      $this->envelope = $envelope->out('wkt');
      $this->envelope_json = json_decode($envelope->out('json'));
      //  calcuate the centroid
      $centroid = $i->centroid();
      //  set the properties
      $this->centroid = $centroid->out('wkt');
      $this->centroid_json = json_decode($centroid->out('json'));
    } else {
      $this->envelope = null;
      $this->envelope_json = '';
      $this->centroid = null;
      $this->centroid_json = '';
    };

    //  second, we calculate the is_empty property
    if ( count($this->layers) === 0) {
      $this->is_empty = 1;
    } else {
      $this->is_empty = 0;
    };

    // NOW, we actually perform the db update
    // STRANGELY, this works, even if envelope and centroid are null, as in an empty map
    $layers_json =  json_encode($this->layers); 
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("UPDATE maps SET title = :t, description = :d, owner = :o, layers = :l, is_empty = :e, envelope = ST_GeomFromText(:env), centroid = ST_GeomFromText(:cent) WHERE id = :id");
    $stmt->bindParam(":t", $this->title);
    $stmt->bindParam(":d", $this->description);
    $stmt->bindParam(":o", $this->owner);
    $stmt->bindParam(":l", $layers_json);
    $stmt->bindParam(":e", $this->is_empty);
    $stmt->bindParam(":env", $this->envelope);
    $stmt->bindParam(":cent", $this->centroid);
    $stmt->bindParam(":id", $this->id);
    $execute = $stmt->execute();
    $error = $pdo->errorInfo();
    $this->__construct($this->id);
    return $execute;
  }

}
