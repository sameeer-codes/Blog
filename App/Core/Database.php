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
    protected $options = [];

    public function __construct($hostname = null, $dbname = null, $username = null, $password = null)
    {
        $hostname = $hostname ?? $_ENV['DB_HOST'];
        $dbname = $dbname ?? $_ENV['DB_NAME'];
        $username = $username ?? $_ENV['DB_USER'];
        $password = $password ?? $_ENV['DB_PASSWORD'];
        $port = $_ENV['DB_PORT'] ?? '3306';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';
        $sslCa = $_ENV['DB_SSL_CA'] ?? null;

        $this->dsn = "mysql:host=" . $hostname . ";port=" . $port . ";dbname=" . $dbname . ";charset=" . $charset;
        $this->username = $username;
        $this->password = $password;

        if (!empty($sslCa)) {
            $this->options[PDO::MYSQL_ATTR_SSL_CA] = $sslCa;
        }
    }

    public function connect()
    {
        try {

            $this->connection = new PDO($this->dsn, $this->username, $this->password, $this->options);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $this->connection;
        } catch (PDOException $e) {
            error_log('Database Connection Failed' . $e->getMessage());
            sendResponse(500, 'Database connection failed.');
        }
    }

    public function Query($sql, $params = [])
    {

        try {

            $this->statement = $this->connection->prepare($sql);
            if (!empty($params)) {
                foreach ($params as $key => $value) {
                    if (is_int($value)) {
                        $type = PDO::PARAM_INT;
                    } else if ($value === null) {
                        $type = PDO::PARAM_NULL;
                    } else {
                        $type = PDO::PARAM_STR;
                    }
                    $this->statement->bindValue(":$key", $value, $type);
                }
            }

            $this->statement->execute();
            return $this->statement;
        } catch (PDOException $e) {
            error_log("Failed to run the query" . $e->getMessage());
            sendResponse(500, 'Database query failed.');
        }
    }

}
