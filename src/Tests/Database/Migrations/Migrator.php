<?php

namespace Lightpack\Database\Migrations;

use Lightpack\Database\Pdo;
use Lightpack\File\File;

class Migrator
{
    /**
     * @var \Lightpack\Database\Pdo
     */
    private $connection;

    public function __construct(Pdo $connection)
    {
        $this->connection = $connection;
    }

    public function run(string $path)
    {
        $migrationFiles = (new File)->traverse($path);

        krsort($migrationFiles);

        foreach ($migrationFiles as $file) {
            $sql = file_get_contents($file->getPathname());
            $this->connection->query($sql);
        }
    }

    public function rollback(int $steps = null)
    {
        // @todo
    }
}
