<?php

namespace App\Models\Uploads;

use App\Core\Database;

class UploadsModal
{
    protected $connection;
    protected $requestParams;

    public function __construct(Database $database)
    {
        $this->connection = $database;
        $this->connection->connect();
    }

    public function addMedia()
    {

    }
}