<?php
/*
class.data_connecter.php
*/
class Data_Connecter {
    public static function get_connection(){
        try {
           $pdo = new PDO('mysql:host=' . DB_HOST .';dbname=' . DB_NAME, DB_USER, DB_PASS);
            return $pdo;
        } catch (PDOException $e) {
            return "Error!: " . $e->getMessage() . "<br/>";
            die();
        }   
    }
}


