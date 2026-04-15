<?php

namespace App\Core\Schema;

class TableDefinition
{
    private string $name;
    private string $createSql;

    public function __construct(string $name, string $createSql)
    {
        $this->name = $name;
        $this->createSql = $createSql;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreateSql(): string
    {
        return $this->createSql;
    }
}
