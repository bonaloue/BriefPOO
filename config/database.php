<?php

class Database {
    private $host="localhost";
    private $dbname="briefpoo_db";
    private $username="root";
    private $password="";

    public $connexion;

    public function connect(){
        try{
            $this->connexion=new PDO(
                "mysql:host=" .$this->host . ";dbname=" .$this->dbname,
                $this->username,
                $this->password
            );
            $this->connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(PDOException $e){
            echo "Erreur de connexion : " .$e->getMessage();
        }
        return $this->connexion;
    } 
}

?>