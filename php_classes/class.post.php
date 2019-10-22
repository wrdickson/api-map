<?php
Class Post{
  private $id;
  private $post_date;
  private $post_user;
  private $post_title;
  private $post_content;

  public function __construct($id){
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");
    $stmt->bindParam(":id",$id);
    $execute = $stmt->execute();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $this->id = $obj->id;
      $this->post_date = $obj->post_date;
      $this->post_user = $obj->post_user;
      $this->post_title = $obj->post_title;
      $this->post_content = $obj->post_content;
    }
  }

  public static function create_post($post_user_id, $post_title, $post_content){
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("INSERT INTO posts ( post_date, post_user, post_title, post_content ) VALUES ( NOW(), :u, :t, :c )");
    $stmt->bindParam(":u", $post_user_id);
    $stmt->bindParam(":t", $post_title);
    $stmt->bindParam(":c", $post_content);
    $execute = $stmt->execute();
    $id = $pdo->lastInsertId();
    return $id;
  }

  public static function get_posts_by_user_id( $user_id ){
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE post_user = :u ORDER BY post_date DESC");
    $stmt->bindParam(":u",$user_id);
    $execute = $stmt->execute();
    $posts = array();
    while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
      $post = array();
      $post['id'] = $obj->id;
      $post['post_date'] = $obj->post_date;
      $post['post_user'] = $obj->post_user;
      $post['post_title'] = $obj->post_title;
      $post['post_content'] = $obj->post_content;
      array_push($posts, $post);
    }
    return $posts;
  }

  public function to_array(){
    $post = array();
    $post['id'] = $this->id;
    $post['post_date'] = $this->post_date;
    $post['post_user'] = $this->post_user;
    $post['post_title'] = $this->post_title;
    $post['post_content'] = $this->post_content;
    return $post;
  }

}