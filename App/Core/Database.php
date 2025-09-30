<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    protected $connection;
    protected $dsn;
    protected $username;
    protected $password;
    protected $statement;

    public function __construct($hostname = HOST, $dbname = DB_NAME, $username = USER_NAME, $password = DB_PASSWORD)
    {
        $this->dsn = "mysql:host=" . $hostname . ";dbname=" . $dbname;
        $this->username = $username;
        $this->password = $password;
    }

    public function connect()
    {
        try {

            $this->connection = new PDO($this->dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $this->connection;
        } catch (PDOException $e) {
            error_log('Database Connection Failed' . $e->getMessage());
            sendResponse('error', 500, 'Database Connection Failed');
        }
    }

    public function Query($sql, $params = [])
    {

        try {

            $this->statement = $this->connection->prepare($sql);
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    $this->statement->bindValue(":$key", $value);
                }
            }

            $this->statement->execute();
            return $this->statement;

        } catch (PDOException $e) {
            error_log("Failed to run the query" . $e->getMessage());
            sendResponse("error", 500, 'Failed to Run Query');
        }
    }

}