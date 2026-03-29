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
    protected $sslCaPath;

    protected function env($key, $default = null)
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        $value = getenv($key);

        if ($value !== false && $value !== '') {
            return $value;
        }

        return $default;
    }

    protected function resolveSslCaPath()
    {
        $sslCa = $this->env('DB_SSL_CA');

        if (!empty($sslCa)) {
            return $sslCa;
        }

        $sslCaContent = $this->env('DB_SSL_CA_CONTENT');

        if (empty($sslCaContent)) {
            return null;
        }

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'aiven-ca.pem';
        file_put_contents($tempFile, $sslCaContent);

        return $tempFile;
    }

    public function __construct($hostname = null, $dbname = null, $username = null, $password = null)
    {
        $hostname = $hostname ?? $this->env('DB_HOST');
        $dbname = $dbname ?? $this->env('DB_NAME');
        $username = $username ?? $this->env('DB_USER');
        $password = $password ?? $this->env('DB_PASSWORD');
        $port = $this->env('DB_PORT', '3306');
        $charset = $this->env('DB_CHARSET', 'utf8mb4');
        $sslCa = $this->resolveSslCaPath();

        $this->dsn = "mysql:host=" . $hostname . ";port=" . $port . ";dbname=" . $dbname . ";charset=" . $charset;
        $this->username = $username;
        $this->password = $password;
        $this->sslCaPath = $sslCa;

        if (!empty($sslCa)) {
            $this->dsn .= ";sslmode=verify-ca;sslrootcert=" . $sslCa;
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
