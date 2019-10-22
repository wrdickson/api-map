<?php
Class U_Id{
  public static function generate_u_id(){
    $pdo = Data_Connecter::get_connection();
    $stmt = $pdo->prepare("INSERT INTO u_id ( title ) VALUES ( 1 )");
    $execute = $stmt->execute();
    $u_id = $pdo->lastInsertId();
    $stmt2 = $pdo->prepare("DELETE FROM u_id WHERE 1");
    $execute2 = $stmt2->execute();
    $salt = "seq-1";
    return md5($u_id . $salt);
  }

}