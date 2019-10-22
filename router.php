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
$app->post('/layers/:layer_id/feature-update/', 'update_layer_feature');

//  maps
$app->get('/maps/:map_id', 'get_map');
$app->get('/maps/user/:user_id', 'get_maps_by_user');

//  posts
$app->post('/posts/', 'create_post');
$app->get('/posts/user/:id', 'get_posts_by_user');
$app->post('/posts/:user_id/:post_id', 'get_post');

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

function get_layer( $layer_id ){
  $layer = new Layer($layer_id);
  print json_encode($layer->to_array());
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
  $response['updated_layer'] = $layer->to_array();
  print json_encode($response);
}


$app->run();
