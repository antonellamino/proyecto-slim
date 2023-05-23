<?php
    namespace App\Models;

    class db{
        private $dbHost = '127.0.0.1';
        private $dbUser = 'root';
        private $dbPass = '';
        private $dbName = 'plataformajuegos';

    //conexion
        function __construct(){
            try{
                $dbConexion = new \PDO("mysql:host=$this->dbHost;dbname=$this->dbName", $this->dbUser, $this->dbPass);
                $dbConexion->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $this->pdo = $dbConexion;
            } catch (\PDOException $err){ //la convencion es con $e
                    die( 'Error: ' . $err->getMessage());
                }
            }

        public function connect(){
            return $this->pdo;
        }    
    }
?>