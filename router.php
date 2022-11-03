<?php
session_start();
require "config/config.php";
require "lib/Slim/Slim.php";
require "lib/GUMP/gump.class.php";
require "lib/geoPHP/geoPHP.inc";
require "php_classes/class.logger.php";
require "php_classes/class.user.php";
require "php_classes/class.data_connecter.php";
require "php_classes/class.u_id.php";
require "php_classes/class.map_util.php";
include "php_classes/class.layer.php";
include "php_classes/class.post.php";
include "php_classes/class.map.php";

include "router_ftns/journal.php";



//set the server timezone
date_default_timezone_set( DEFAULT_TIMEZONE );

// GUMP::add_validator("is_object", function($field, $input, $param = 'something') {
//     return is_object($input[$field]);
// });
// GUMP::set_error_message("is_object", "Is not an object");

\Slim\Slim::registerAutoloader();

// create new Slim instance
$app = new \Slim\Slim();

//route the requests

//  layers
$app->get('/layers/', 'get_all_layers');
$app->get('/layers/:layer_id', 'get_layer');
$app->post('/layers/', 'create_layer');
$app->post('/layers/update/json/:layer_id', 'update_layer_json');
$app->post('/layers/update/title/:layer_id', 'update_layer_title');
$app->post('/layers/update/description/:layer_id', 'update_layer_description');
$app->post('/layers/:layer_id/feature-update/', 'update_layer_feature');
$app->get('/layers/user/:user_id', 'get_layers_by_user');

//  maps
$app->post('/maps/', 'create_map');
$app->get('/maps/:map_id', 'get_map');
$app->get('/maps/user/:user_id', 'get_maps_by_user');
$app->post('/maps/:map_id/add_layer/', 'map_add_layer');
$app->post('/maps/:map_id/remove_layer/', 'map_remove_layer');
$app->post('/maps/:map_id/title/', 'map_update_title');
$app->post('/maps/:map_id/description/', 'map_update_description');

$app->post('/test-update/', 'map_test_update');

//  posts
$app->post('/posts/', 'create_post');
$app->get('/posts/user/:id', 'get_posts_by_user');
$app->post('/posts/:user_id/:post_id', 'get_post');

//  tiles
$app->post('/rip-tile/:x/:y/:z', 'tile_rip_tile');
$app->post('/request-tiles/', 'tile_request_tiles');
$app->post('/request-tiles-string/', 'tile_request_string');
$app->post('/request-tiles-json/', 'tile_request_json');

//  user
$app->post('/login/', 'login');
$app->post('/logoff/', 'logoff');

// u_id
$app->post('/u-id/', 'generate_u_id');

function create_layer(){
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [ 
    'user' => $user, 
    'feature_collection' => $feature_collection,
    'feature_collection' => [
      'features' => $features
    ] 
  ] = json_decode($app->request->getBody(), true);
  $validate_user = Logger::validate_user($user['userId'], $user['username'], $user['key'], $user['permission']);
  $response['validate_user'] = $validate_user;
  if ($validate_user == true){
    $owner = $user['userId'];
    $json_feature_collection = json_encode($feature_collection);
    $layer_title = "title";
    $layer_desc = "description";
    $response['layer_id'] = Layer::create_layer($owner, $json_feature_collection, $layer_title, $layer_desc);
  };
  print json_encode($response);
}

function create_map () {
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [ 
    'user' => $user,
    'map_title'=> $map_title,
    'map_desc'=> $map_desc 
  ] = json_decode($app->request->getBody(), true);
  $user_is_valid = Logger::validate_user($user['userId'], $user['username'], $user['key'], $user['permission']);
  $response['user_validated'] = $user_is_valid;
  $response['user'] = $user;
  $response['title'] = $map_title;
  $response['desc'] = $map_desc;
  //  TODO validate inputs

  $inputs_validated = true;
  $response['inputs_validated'] = $inputs_validated;
  if ($inputs_validated == true && $user_is_valid == true) {
    $response['new_map_id'] = Map::create_map($user['userId'], $map_title, $map_desc);
  }
  print json_encode($response);
}

function create_post(){
  //  TODO verify user and  validate data . .
  $app = \Slim\Slim::getInstance();
  //  destructure params array
  [
    'user' => $user,
    'post_title' => $post_title,
    'post_content' => $post_content
  ] = json_decode($app->request->getBody(), true);
  print Post::create_post($user['userId'], $post_title, $post_content);
}

function generate_u_id(){
  //  TODO validate user
  print U_Id::generate_u_id();
}

function get_all_layers(){
  print json_encode( Layer::get_all_layers() );
}

function get_layer ($layer_id) {
  $layer = new Layer($layer_id);
  print json_encode($layer->to_array());
}

function get_layers_by_user ($user_id) {
  print json_encode(Layer::get_layers_by_user($user_id));
}

function get_map($map_id){
  $map = new Map($map_id);
  print json_encode($map->to_array());
}

function get_maps_by_user($user_id){
  $response = array();
  $response['maps'] = Map::get_maps_by_user($user_id);
  print json_encode($response);
}

function get_post($user_id, $post_id){
  $app = \Slim\Slim::getInstance();
  [
    'user' => $user
  ] = json_decode($app->request->getBody(), true);
  $response = array();
  $response['user'] = $user;
  //  TODO validate user
  $response['postId'] = $post_id;
  $post = new Post($post_id);
  $response['post'] = $post->to_array();

  print json_encode($response);

}

function get_posts_by_user( $user_id ){
  print json_encode(Post::get_posts_by_user_id($user_id));
}

function login (){
  $app = \Slim\Slim::getInstance();
  $response = array();
  $params = json_decode($app->request->getBody(), true);
  $response['params'] = $params;
  $response['prePostSession'] = $_SESSION;
  $response['login'] = Logger::check_login( $params['username'], $params['password'] );
  $response['postPostSession'] = $_SESSION;
  print json_encode($response);
}

function logoff(){
    $app = \Slim\Slim::getInstance();
    $response = array();
    $params = json_decode($app->request->getBody(), true);
    $response['params'] = $params;
    $response['logoff'] = Logger::logoff( $params['userId'], $params['key'] );
    print json_encode($response);
}

function map_add_layer($map_id){
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [ 
    'user' => $user, 
    'new_layer_id' => $new_layer_id
  ] = json_decode($app->request->getBody(), true);
  $response['validate_user'] = Logger::validate_user($user['userId'], $user['username'], $user['key'], $user['permission']);
  $map = new Map($map_id);
  $response['execute'] = $map->add_layer((int)$new_layer_id);
  $map = new Map($map_id);
  $response['updated_map'] = $map->to_array();
  print json_encode($response);
}

function map_remove_layer($map_id){
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [ 
    'user' => $user, 
    'layer_id' => $layer_id
  ] = json_decode($app->request->getBody(), true);
  $response['validate_user'] = Logger::validate_user($user['userId'], $user['username'], $user['key'], $user['permission']);
  $map = new Map($map_id);
  $response['execute'] = $map->remove_layer((int)$layer_id);
  $map = new Map($map_id);
  $response['updated_map'] = $map->to_array();
  print json_encode($response);
}

function map_test_update () {
  $response = array();
  
  $map = new Map(1);
  $response['map'] = $map->to_array();
  $response['update_title'] = $map->update_title("humungus");
  $map = new Map(1);
  $response['after'] = $map->to_array();
  $map = new Map(1);
  $response['updated'] = $map->to_array();
  //$response['update'] = $map->update_to_db();
  /*
  $str = '{
    "type" : "Feature",
    "properties" : {
      "title" : "Bounds",
      "desc" : "Description"
    },
    "geometry" : {
      "type" : "Polygon",
      "coordinates" : [
        [
          [ -125.947266, 24.44715 ],
          [ -125.947266, 51.508742 ],
          [ -81.738281, 51.508742 ],
          [ -81.738281, 24.44715 ],
          [ -125.947266, 24.44715 ]
        ]
      ]
    }
  }';
  $bounds = json_decode($str, true);
  $i = geoPHP::load($str);
  $bounds_wkt = $i->out('wkt');
  $response['bounds_wkt'] = $bounds_wkt;
  $response['bounds'] = $bounds;

	$pdo = Data_Connecter::get_connection();
    //this returns maps whose minimum bounding rectangle is entirely within the viewport params
	$stmt = $pdo->prepare("SELECT id FROM maps WHERE MBRContains(ST_GeomFromText(:g1), centroid)");
    //this returns maps whose centroid is within the viewport parms
    //$stmt = $pdo->prepare("SELECT map_id, map_name, map_desc, map_area, AsText(map_envelope) AS map_envelope, AsText(map_centroid) AS map_centroid, map_json, map_owner FROM maps WHERE MBRContains(GeomFromText(:g1), map_centroid)");
	$stmt->bindParam(":g1", $bounds_wkt);
  $execute = $stmt->execute();
  $response['execute'] = $execute;
  $response['error'] = $pdo->errorInfo();
  $maps = array();
  while ($obj = $stmt->fetch(PDO::FETCH_OBJ)) {
    $map = array();
    $map['id'] = $obj->id;
    array_push($maps, $map);
  };
  $response['maps'] = $maps;
  */


  print json_encode($response);
}

function map_update_description ($map_id) {
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [ 
    'user' => $user, 
    'new_description' => $new_description
  ] = json_decode($app->request->getBody(), true);
  $response['validate_user'] = Logger::validate_user($user['userId'], $user['username'], $user['key'], $user['permission']);
  //  TODO validate input
  $map =  new Map($map_id);
  $response['execute'] = $map->update_description($new_description);
  $new_map = new Map($map_id);
  $response['updated_map'] = $new_map->to_array();
  print json_encode($response);
}

function map_update_title ($map_id) {
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [ 
    'user' => $user, 
    'new_title' => $new_title
  ] = json_decode($app->request->getBody(), true);
  $response['validate_user'] = Logger::validate_user($user['userId'], $user['username'], $user['key'], $user['permission']);
  //  TODO validate input
  $map =  new Map($map_id);
  $response['execute'] = $map->update_title($new_title);
  $new_map = new Map($map_id);
  $response['updated_map'] = $new_map->to_array();
  print json_encode($response);
}

function tile_request_tiles () {
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [ 
    'neLat' => $ne_lat, 
    'neLng' => $ne_lng,
    'swLat' => $sw_lat,
    'swLng' => $sw_lng,
    'minZoom' => $min_zoom,
    'maxZoom' => $max_zoom
  ] = json_decode($app->request->getBody(), true);

  $tiles = array();
  //  iterate through the z (zoom) values, which we already have
  for ($z = $min_zoom; $z <= $max_zoom; $z++){
    //  now we iterate through the y values, which we need to generate
    $i_ne_y = floor((1 - log(tan(deg2rad($ne_lat)) + 1 / cos(deg2rad($ne_lat))) / pi()) /2 * pow(2, $z));
    $i_sw_y = floor((1 - log(tan(deg2rad($sw_lat)) + 1 / cos(deg2rad($sw_lat))) / pi()) /2 * pow(2, $z));
    for ($y = $i_ne_y; $y <= $i_sw_y; $y++){
      //  now we iterate through the x, which we need to generate
      $i_ne_x = floor((($ne_lng + 180) / 360) * pow(2, $z));
      $i_sw_x = floor((($sw_lng + 180) / 360) * pow(2, $z));
      for ($x = $i_sw_x; $x <= $i_ne_x; $x++){
        $i_tile = array();
        $i_tile['z'] = $z;
        $i_tile['y'] = $y;
        $i_tile['x'] = $x;
        array_push($tiles, $i_tile);
      }
    };
  };
  $response['length'] = sizeof($tiles);
  $response['maxZoom'] = $max_zoom;
  $response['minZoom'] = $min_zoom;
  $response['ne_lat'] = $ne_lat;
  $response['ne_lng'] = $ne_lng;
  $response['sw_lat'] = $sw_lat;
  $response['sw_lng'] = $sw_lng;
  $response['tiles'] = $tiles;
  
  print json_encode($response);
}

function tile_request_json () {
  $response = array();
  $app = \Slim\Slim::getInstance();
  [ 
    'neLat' => $ne_lat, 
    'neLng' => $ne_lng,
    'swLat' => $sw_lat,
    'swLng' => $sw_lng,
    'minZoom' => $min_zoom,
    'maxZoom' => $max_zoom
  ] = json_decode($app->request->getBody(), true);
  $response['params'] = json_decode($app->request->getBody(), true);
  $response['a'] = 'apple';
  $response['b'] = 'orange';
  print json_encode($response);
}

function tile_request_string () {
  print "this is a response from the server";
}

function tile_rip_tile($x, $y, $z) {
  $response = array();
  $response['x'] = $x;
  $response ['y'] = $y;
  $response['z'] = $z;
  //  http://services.arcgisonline.com/ArcGIS/rest/services/USA_Topo_Maps/MapServer/tile/12/1571/801.jpg
  $url = "http://services.arcgisonline.com/ArcGIS/rest/services/USA_Topo_Maps/MapServer/tile/" . $z . "/" . $y . "/" . $x . ".jpg";
  $response['image'] = $imageData = base64_encode(file_get_contents($url));
  $response['f1_exists'] = is_dir("tile_servers/f1");
  $response ['z_exists'] = is_dir("tile_servers/f1/" . $z);
  //  check if z directory exists
  if(! is_dir("tile_servers/f1/" . $z)){
    mkdir("tile_servers/f1/" . $z, 0700);
  };
  //  check if x directory exists
  if(! is_dir("tile_servers/f1/" . $z . "/" . $y)){
    mkdir("tile_servers/f1/" . $z . "/" . $y, 0700);
  };
  // check if the file exists
  $img_exists = file_exists("tile_servers/f1/" . $z . "/" . $y . "/" . $x . ".jpg");
  $response['y_img_exists'] = $img_exists;
  if(!$img_exists){
    //Get the file
    $content = file_get_contents($url);
    //Store in the filesystem.
    $fp = fopen("tile_servers/f1/" . $z . "/" . $y . "/" . $x . ".jpg", "w");
    fwrite($fp, $content);
    fclose($fp);
  }
  print json_encode($response);
}

function update_layer_feature( $layer_id ){
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [ 
    'user' => $user, 
    'feature' => $feature
  ] = json_decode($app->request->getBody(), true);
  $response['layer_id'] = $layer_id;
  $response['feature'] = $feature;
  $response['user'] = $user;
  $layer = new Layer($layer_id);
  $response['layer_before'] = $layer->to_array();
  $response['update_feature'] = $layer->update_feature( $feature );
  $new_layer = new Layer($layer_id);
  $response['new_layer'] = $new_layer->to_array();
  print json_encode($response);
}

function update_layer_description ($layer_id) {
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [
      'user' => $user,
      'new_description' => $new_description
  ] = json_decode($app->request->getBody(), true);
  $response['validate_user'] = Logger::validate_user($user['userId'], $user['username'], $user['key'], $user['permission']);
  $layer = new Layer($layer_id);
  $response['orig_layer'] = $layer->to_array();
  $response['execute'] = $layer->set_desc($new_description);
  $response['updated_layer'] = $layer->to_array();
  print json_encode($response);
}

function update_layer_json( $layer_id ){
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [ 
    'user' => $user, 
    'json' => $json
    
  ] = json_decode($app->request->getBody(), true); 
  $layer = new Layer($layer_id);
  $response['update'] = $layer->set_json( $json );
  //  reinstantiate the layer to get the new calculated centroid and envelope
  $uLayer = new Layer($layer_id);
  $response['updated_layer'] = $uLayer->to_array();
  print json_encode($response);
}

function update_layer_title ($layer_id) {
  $app = \Slim\Slim::getInstance();
  $response = array();
  //  destructure params
  [
      'user' => $user,
      'new_title' => $new_title
  ] = json_decode($app->request->getBody(), true);
  $response['validate_user'] = Logger::validate_user($user['userId'], $user['username'], $user['key'], $user['permission']);
  $layer = new Layer($layer_id);
  $response['orig_layer'] = $layer->to_array();
  $response['execute'] = $layer->set_title($new_title);
  $response['updated_layer'] = $layer->to_array();
  print json_encode($response);
}


$app->run();
