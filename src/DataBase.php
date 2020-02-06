<?php

namespace Otter\ORM;

class DataBase {

    protected $host;
    protected $database;
    protected $user;
    protected $password;

    public function configure(string $host, string $database, string $user, string $password) {
        $this->host = $host;
        $this->database = $database;
        $this->user = $user;
        $this->password = $password;
    }

    public function connect(): ?\PDO {
        try
        {
            $user    = $this->user;
            $pass    = $this->password;
            $host    = $this->host;
            $dbname  = $this->database;

            $options = array( "CharacterSet" => 'UTF-8' );
            $dns = "sqlsrv:server=$host;Database=$dbname";

            $conn = new \PDO( $dns, $user, $pass, $options);
            $conn->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_UTF8); //sql server
            return $conn;
        } catch(PDOException $ex) {
            echo "Connection Error: $ex";
            return null;
        }
    }

}

