<?php
class Logger {

    public static function checkDuplicateUsername($testUsername){
        $pdo = Data_Connecter::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_name = :username");
        $stmt->bindParam(":username", $testUsername, PDO::PARAM_STR);
        $stmt->execute();
        //  TODO rowCount() is suposedly unreliable . . . see php docs
        if($stmt->rowCount() > 0){
            return true;
        }else{
            return false;
        }
    }
    
    public static function check_login($username, $password){  
        //check username/password pair
        $returnArr = array();
        $pwd = hash('sha256', $password);
        
        //  temp
        $returnArr['uname'] = $username;
        //  $returnArr['pwd'] = $pwd;
        $pdo = Data_Connecter::get_connection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND password = :pwd");
        $stmt->bindParam(":username", $username, PDO::PARAM_STR);
        $stmt->bindParam(":pwd", $pwd, PDO::PARAM_STR);
        $stmt->execute();
        //  TODO rowCount() is unreliable on a SELECT statement!  see php5 manual  http://php.net/manual/en/pdostatement.rowcount.php
        $returnArr['pass'] = $stmt->rowCount();
        
        if($returnArr['pass'] > 0){
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            if($result->id != null){
                    $returnArr['id'] = (int)$result->id;
                }else{
                    $returnArr['id'] = 0;
                    }
        }else{
            $returnArr['id'] = 0;
            //set session variables
            $_SESSION['mUserId'] = 0;
            $_SESSION['mUserKey'] = 0;
            $_SESSION['mUsername'] = 'Guest';
            $_SESSION['mUserPerm'] = 0;
        }
        $stmt = null;
        
        if($returnArr['pass'] == 1 AND $returnArr['id'] > 0){
            //generate a key
            $returnArr['user_key'] = Logger::generateKey();
            //insert it into the db
            $returnArr['keyInsertSuccess'] = Logger::updateUserKey($returnArr['user_key'], $returnArr['id']);
            //log the login to db
            $returnArr['lastLoginUpdate'] = Logger::updateUserLastLogin($returnArr['id']);
            $returnArr['updateActivity'] = Logger::updateUserLastActivity($returnArr['id']);
            $iUser = new User($returnArr['id']);
            $returnArr['username'] = $iUser->get_username();
            $returnArr['permission'] = (int)$iUser->get_permission();
            //set session variables
            $_SESSION['mUserId'] = $returnArr['id'];
            $_SESSION['mUserKey'] = $returnArr['user_key'];
            $_SESSION['mUsername'] = $returnArr['username'];
            $_SESSION['mUserPerm'] = $iUser->get_permission();
        }else{
            $returnArr['user_key'] = "";
        }
        return $returnArr;
    }
    
    public static function createUser($password, $username, $email, $permission){
        $response = array();
        $response['password'] = $password;
        $response['username'] = $username;
        $response['email']= $email;
        $response['permission'] = $permission;
        $pwd = hash('sha256', $password);
        $pdo = Data_Connecter::getConnection();
        $stmt = $pdo->prepare("INSERT INTO users (password, username, email, registered, permission) VALUES(:pwd, :name, :email, NOW(), :perm)");
        $stmt->bindParam(":pwd", $pwd, PDO::PARAM_STR);
        $stmt->bindParam(":name", $username, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":perm", $permission, PDO::PARAM_INT);
        $response['success'] = $stmt->execute();
        return $response;
    }

    private static function generateKey(){
        $rnd1 = mt_rand();
        $rnd2 = mt_rand();
        $salt = "zB*r7_3kd)eJg";
        $randstr = "";
        for($i=0; $i < 12; $i++){
           $randnum = mt_rand(0,61);
           if($randnum < 10){
              $randstr .= chr($randnum+48);
           }else if($randnum < 36){
              $randstr .= chr($randnum+55);
           }else{
              $randstr .= chr($randnum+61);
           }
        }
        $hData = $rnd1 . $salt . $rnd2 . $randstr;
        $key = hash('sha256', $hData);
        return $key;
    }
    
    public static function getAllUsers() {
        //note: we do NOT return password- it is set only
        $pdo = Data_Connecter::get_connection();
        $stmt = $pdo->prepare("SELECT * FROM users"); 
        $stmt->bindParam(":id",$id, PDO::PARAM_INT);
        $stmt->execute();
        $pArr = array();
        while($obj = $stmt->fetch(PDO::FETCH_OBJ)){
            $iPerson = array();
            $iPerson['id'] = $obj->id;
            $iPerson['username'] = $obj->username;
            $iPerson['email'] = $obj->email;
            $iPerson['permission'] = $obj->permission;
            $iPerson['registered'] = $obj->registered;
            $iPerson['last_login'] = $obj->last_login;
            $iPerson['last_activity'] = $obj->last_activity;
            array_push($pArr, $iPerson);
        }
        return json_encode($pArr);
    }
    
    public static function logoff($id, $key){
        $response = array();
        
        //first, reset the session variables
        $_SESSION['mUserId'] = 0;
        $_SESSION['mUserKey'] = 0;
        $_SESSION['mUsername'] = "Guest";
        $_SESSION['mUserPerm'] = 0;
        
        $iUser = new User( $id);
        $keyPassed = $iUser->verify_key($key);
        //$response['user'] = $iUser->dumpArray();
        $response['keyPassed'] = $keyPassed; 
        
        $newKey = Logger::generateKey();
        if($keyPassed == true){
            $response['keychangesuccess'] = Logger::updateUserKey($newKey, $id);
            $returnArr['updateActivity'] = Logger::updateUserLastActivity($id);
        } else {
            $response['keychangesuccess'] = false;
        }
        return $response;
    }
    
    public static function updateUser($id, $username, $email, $perm) {
        $pdo = Data_Connecter::getConnection();
        $stmt = $pdo->prepare("UPDATE users SET user_name = :username, user_perm = :perm, user_email = :email WHERE id = :id"); 
        $stmt->bindParam(":id",$id, PDO::PARAM_INT);
        $stmt->bindParam(":username",$username, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":perm", $perm, PDO::PARAM_INT);
        $stmt->execute();
        //TODO  rowCount() is not reliable . . .
        $success = $stmt->rowCount();
        $stmt = null;
        if($success == 1){
            return true;
        }else{
            return false;
        }
    }
    
    public static function updateUserPassword($id, $pwd) {
        $password = hash('sha256', $pwd);
        $pdo = Data_Connecter::getConnection();
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id"); 
        $stmt->bindParam(":id",$id, PDO::PARAM_INT);
        $stmt->bindParam(":password",$password, PDO::PARAM_STR);
        return $stmt->execute();
    }

    private static function updateUserKey($key, $id){
        $pdo2 = Data_Connecter::get_connection();
        $stmt = $pdo2->prepare("UPDATE users SET user_key = :key WHERE id = :id"); 
        $stmt->bindParam(":key", $key, PDO::PARAM_STR);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        //TODO  rowCount() is not reliable . . .
        $keyInsertSuccess = $stmt->rowCount();
        $stmt = null;
        return $keyInsertSuccess;
    }
    
    private static function updateUserLastActivity($id){
        $pdo = Data_Connecter::get_connection();
        $stmt = $pdo->prepare("UPDATE users SET last_activity = NOW() WHERE id = :id"); 
        $stmt->bindParam(":id",$id, PDO::PARAM_INT);
        $stmt->execute();
        //TODO  rowCount() is not reliable . . .
        $updateActivity = $stmt->rowCount();
        $stmt = null;
        return $updateActivity;
    }
    
    private static function updateUserLastLogin($id){
        $pdo = Data_Connecter::get_connection();
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id"); 
        $stmt->bindParam(":id",$id, PDO::PARAM_INT);
        $stmt->execute();
        //TODO  rowCount() is not reliable . . .
        $loginDateSuccess = $stmt->rowCount();
        $stmt = null;
        return $loginDateSuccess;
    }
    
    public static function validate_user( $id, $username, $key, $permission ){
        $valid = true;
        $user = new User($id); 
        //  validate against db data
        if ($user->get_username() != $username){
          $valid = false;
        };
        if($key != $user->get_user_key()){
          $valid = false;
        }
        if($user->get_permission() != $permission){
          $valid = false;
        }
        //  now validate against session data
        if($_SESSION['mUserId'] != $id || $_SESSION['mUsername'] != $username || $_SESSION['mUserKey'] != $key || $_SESSION['mUserPerm'] != $permission){
          $valid = false;
        }
        return $valid;
    }
}
?>
