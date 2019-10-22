<?php 
class User {

    private $id;
    private $username;
    private $email;
    private $permission;   
    private $registered;
    private $last_login;
    private $last_activity;
    private $user_key; 

    public function __construct($id){
        //handle the case of a non user
        if($id == 0){
        $this->id = 0;
        $this->username = "Guest";
        $this->email = "";
        $this->permission = 0;
        $this->registered = 0;
            $this->last_login = 0;
            $this->last_activity = 0;            
            $hData = "lookada" . json_encode(getdate()) . "saltysaltydog" . mt_rand();
        $this->user_key = hash('sha256', $hData);
        } else {
          //get properties from db
          $pdo = Data_Connecter::get_connection();
          $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
          $stmt->bindParam(":id",$id,PDO::PARAM_INT);
          $stmt->execute();
          while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
            $this->id = $obj->id;
            $this->username = $obj->username;
            $this->email = $obj->email;
            $this->permission = $obj->permission;
            $this->registered = $obj->registered;
            $this->last_login = $obj->last_login;
            $this->last_activity = $obj->last_activity;
            $this->user_key = $obj->user_key;
          }
        }
    }

    public function to_array(){
        $arr = array();
        $arr['id'] = $this->id;
        $arr['username'] = $this->username;
        $arr['email'] = $this->email;
        $arr['permission'] = $this->permission;
        $arr['registered'] = $this->registered;
        $arr['last_login'] = $this->last_login;
        $arr['last_activity'] = $this->last_activity;
        $arr['user_key'] = $this->user_key;
        return $arr;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_username() {
        return $this->username;
    }
      
    public function get_email() {
        return $this->email;
    }

    public function set_password($password) {
        //hash the new password
        $password = hash('sha256', $password);
        $xid = $this->get_id();
        $pdo = Data_Connector::get_connection();
        $stmt = $pdo->prepare("UPDATE users SET password = :passwd WHERE id = :xid");
        $stmt->bindParam(":passwd", $password, PDO::PARAM_STR);
        $stmt->bindParam(":xid", $xid, PDO::PARAM_INT);
        $result = $stmt->execute();
        //password is not kept on the object, so we don't need to reset
        return $result;
    }

    public function get_permission() {
        return $this->permission;
    }

    public function set_permission($permission) {
        $xid = $this->get_id();
        $pdo = Data_Connector::get_connection();
        $stmt = $pdo->prepare("UPDATE users SET permission = :newPerm WHERE id = :xid");
        $stmt->bindParam(":newPerm", $permission, PDO::PARAM_INT);
        $stmt->bindParam(":xid", $xid, PDO::PARAM_INT);
        $result = $stmt->execute();
        if ($result == true){
            $this->permission = $permission;
        }
        return $result;
    }
      
    public function get_registered() {
        return $this->registered;
    }
    
    public function get_user_key() {
        return $this->user_key;
    }

    public function updateActivity(){
        $pdo = DataConnector::getConnection();
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = :xid");
        $stmt->bindParam(":xid", $this->id,PDO::PARAM_STR);
        $result = $stmt->execute();
        return $result;
    }

    public function verify_key($key1){
        if($key1 == $this->user_key){
            return true;
        }else{
            return false;
        }
    }
}