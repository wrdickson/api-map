<?php
/*
class.data_connecter.php
*/
class Data_Connecter {
    public static function get_connection(){
        try {
           $pdo = new PDO('mysql:host=' . DB_HOST .';dbname=' . DB_NAME, DB_USER, DB_PASS);
           //  see https://stackoverflow.com/questions/60174/how-can-i-prevent-sql-injection-in-php?rq=1
           $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
           $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            return "Error!: " . $e->getMessage() . "<br/>";
            die();
        }   
    }
}


