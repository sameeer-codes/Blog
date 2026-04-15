<?php

namespace App\Core\Schema;

use App\Core\Database;

class SchemaManager
{
    private Database $database;
    private array $tables;

    public function __construct(Database $database, array $tables = [])
    {
        $this->database = $database;
        $this->tables = $tables;
    }

    public function ensureAll(): void
    {
        foreach ($this->tables as $table) {
            if (!$table instanceof TableDefinition) {
                continue;
            }

            $this->ensureTable($table);
        }
    }

    public function ensureTable(TableDefinition $table): void
    {
        if ($this->tableExists($table->getName())) {
            return;
        }

        $this->database->Query($table->getCreateSql());
        error_log('Created missing table: ' . $table->getName());
    }

    public function tableExists(string $tableName): bool
    {
        $sql = "SELECT COUNT(*) AS total
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                AND table_name = :table_name";

        $result = $this->database->Query($sql, [
            'table_name' => $tableName,
        ])->fetch();

        return (int) ($result['total'] ?? 0) > 0;
    }
}
