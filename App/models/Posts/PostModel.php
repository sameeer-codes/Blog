<?php

namespace App\Models\Posts;

use App\Core\Database;

class PostModel
{
    protected $connection;

    public function __construct(Database $database)
    {
        $this->connection = $database;
    }
}