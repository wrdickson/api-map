<?php
Class Layer {
  private $id;
  private $layer_envelope;
  private $layer_centroid;
  private $layer_centroid_json;
  private $layer_owner;
  // layer_json is a geoJson feature collection stored in db as a json string
  // but we keep it on the object as an array
  private $layer_json;
  private $layer_title;
  private $layer_desc;
  private $date_added;
  private $date_modified;

  public function __construct($layer_id){
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("SELECT id, ST_AsWKT(layer_envelope) AS layer_envelope, ST_AsWkt(layer_centroid) AS layer_centroid, layer_owner, layer_json, layer_title, layer_desc, date_added, date_modified FROM map_layers WHERE id = :i");
    $stmt->bindParam(":i", $layer_id);
    $execute = $stmt->execute();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->layer_envelope = $obj->layer_envelope;
      $env = geoPHP::load($obj->layer_envelope);

      $this->layer_centroid = $obj->layer_centroid;
      $geo = geoPHP::load($obj->layer_centroid);
      $this->layer_centroid_json = json_decode($geo->out('json'), true);
      $this->layer_owner = $obj->layer_owner;
      //  here's where we convert the geoJson feature collection to a php array
      $this->layer_json = json_decode($obj->layer_json);
      $this->layer_title = $obj->layer_title;
      $this->layer_desc = $obj->layer_desc;
      $this->date_added = $obj->date_added;
      $this->date_modified = $obj->date_modified;      
    }
  }

  public static function create_layer($owner, $json, $title, $desc){
    $o = intval($owner);
    //calculate centroid
    $wkt_centroid = Map_Utility::calculate_centroid_json_2_wkt($json);
    //calculate envelope
    $wkt_envelope = Map_Utility::calculate_envelope_json_2_wkt($json);
    //insert
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("INSERT INTO map_layers (layer_envelope, layer_centroid, layer_owner, layer_json, layer_title, layer_desc, date_added, date_modified) VALUES ( ST_GeomFromText(:e), ST_GeomFromText(:c), :o, :j, :t, :d, NOW(), NOW() )");
    $stmt->bindParam(":e", $wkt_envelope);
    $stmt->bindParam(":c" ,$wkt_centroid);
    $stmt->bindParam(":o", $o);
    $stmt->bindParam(":j", $json);
    $stmt->bindParam(":t", $title);
    $stmt->bindParam(":d", $desc);
    $execute = $stmt->execute();
    $insert_id = $pdo->lastInsertId();
    $error = $pdo->errorInfo();
    return $insert_id;		
  }

  public static function get_all_layers(){
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("SELECT id, ST_AsWKT(layer_envelope) AS layer_envelope, ST_AsWkt(layer_centroid) AS layer_centroid, layer_owner, layer_json, layer_title, layer_desc, date_added, date_modified FROM map_layers");
    $execute = $stmt->execute();
    $layers = array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $layer = array();
      $layer['id'] = $obj->id;
      $layer['layer_envelope'] = $obj->layer_envelope;
      $env = geoPHP::load($obj->layer_envelope);
      $layer['layer_envelope_array'] = $env;
      $layer['layer_envelope_json'] = json_decode($env->out('json'));
      $layer['layer_centroid'] = $obj->layer_centroid;
      $geo = geoPHP::load($obj->layer_centroid);
      $layer['layer_centroid_json'] = json_decode($geo->out('json'), true);
      $layer['layer_owner'] = $obj->layer_owner;
      //  here's where we convert the geoJson feature collection to a php array
      $layer['layer_json'] = json_decode($obj->layer_json);
      $layer['layer_title'] = $obj->layer_title;
      $layer['layer_desc'] = $obj->layer_desc;
      $layer['date_added'] = $obj->date_added;
      $layer['date_modified'] = $obj->date_modified; 
      array_push($layers, $layer);     
    }
    return $layers;
  }

  public static function get_layers_by_user ($user_id) {
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("SELECT id, ST_AsWKT(layer_envelope) AS layer_envelope, ST_AsWkt(layer_centroid) AS layer_centroid, layer_owner, layer_json, layer_title, layer_desc, date_added, date_modified FROM map_layers WHERE layer_owner = :o");
    $stmt->bindParam(":o", $user_id);
    $execute = $stmt->execute();
    $layers = array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $layer = array();
      $layer['id'] = $obj->id;
      $layer['layer_envelope'] = $obj->layer_envelope;
      $env = geoPHP::load($obj->layer_envelope);
      $layer['layer_envelope_array'] = $env;
      $layer['layer_envelope_json'] = json_decode($env->out('json'));
      $layer['layer_centroid'] = $obj->layer_centroid;
      $geo = geoPHP::load($obj->layer_centroid);
      $layer['layer_centroid_json'] = json_decode($geo->out('json'), true);
      $layer['layer_owner'] = $obj->layer_owner;
      //  here's where we convert the geoJson feature collection to a php array
      $layer['layer_json'] = json_decode($obj->layer_json);
      $layer['layer_title'] = $obj->layer_title;
      $layer['layer_desc'] = $obj->layer_desc;
      $layer['date_added'] = $obj->date_added;
      $layer['date_modified'] = $obj->date_modified; 
      array_push($layers, $layer);     
    }
    return $layers;
  }

  public function to_array(){
    $arr = array();
    $arr['id'] = $this->id;
    $arr['layer_envelope'] = $this->layer_envelope;
    $arr['layer_centroid'] = $this->layer_centroid;
    $arr['layer_centroid_json'] = $this->layer_centroid_json;
    $arr['layer_owner'] = $this->layer_owner;
    $arr['layer_json'] = $this->layer_json;
    $arr['layer_title'] = $this->layer_title;
    $arr['layer_desc'] = $this->layer_desc;
    $arr['date_added'] = $this->date_added;
    $arr['date_modified'] = $this->date_added;
    return $arr;
  }

  public function set_desc($desc){
    $this->layer_desc = $desc;
    return $this->update_to_db();
  }

  public function set_json($json){
    $this->layer_json = $json;
    return $this->update_to_db();
  }

  public function update_feature( $feature ){
    function replace_by_mtoid( &$iFeature, $key, $feature ){
      if($iFeature->properties->mtoid == $feature['properties']['mtoid']){
        $iFeature = $feature;
      } 
    }
    array_walk( $this->layer_json->features, 'replace_by_mtoid', $feature);
    
    return $this->update_to_db();
  }

  private function update_to_db(){
    //calculate centroid
    $wkt_centroid = Map_Utility::calculate_centroid_json_2_wkt(json_encode($this->layer_json));
    //calculate envelope
    $wkt_envelope = Map_Utility::calculate_envelope_json_2_wkt(json_encode($this->layer_json));
    //insert
    $jso = json_encode($this->layer_json);
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("UPDATE map_layers SET layer_envelope = ST_GeomFromText(:en), layer_centroid = ST_GeomFromText(:ce), layer_owner= :ow, layer_json = :js, layer_title= :ti, layer_desc = :de, date_modified = NOW() WHERE id = :id");
    $stmt->bindParam(":en", $wkt_envelope);
    $stmt->bindParam(":ce" ,$wkt_centroid);
    $stmt->bindParam(":ow", $this->layer_owner);
    $stmt->bindParam(":js", $jso);
    $stmt->bindParam(":ti", $this->layer_title);
    $stmt->bindParam(":de", $this->layer_desc);
    $stmt->bindParam(":id", $this->id);
    $execute = $stmt->execute(); 
    $error = $pdo->errorInfo();
    return $execute;
  }

}