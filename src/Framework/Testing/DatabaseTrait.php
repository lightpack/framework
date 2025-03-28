<?php

namespace Lightpack\Testing;

use Lightpack\Database\DB;
use Lightpack\Database\Migrations\Migrator;

trait DatabaseTrait
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $db = self::createConnection();

        $migrator = new Migrator($db);
        $migrator->run(getcwd() . '/database/migrations');
    }

    public static function tearDownAfterClass(): void
    {
        $db = self::createConnection();

        $migrator = new Migrator($db);
        $migrator->rollbackAll(getcwd() . '/database/migrations');

        parent::tearDownAfterClass();
    }

    protected function beginTransaction()
    {
        db()->begin();
    }

    protected function rollbackTransaction()
    {
        db()->rollback();
    }

    private static function createConnection(): DB
    {
        return new DB(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s',
                getenv('DB_HOST'),
                getenv('DB_PORT'),
                getenv('DB_NAME')
            ),
            getenv('DB_USER'),
            getenv('DB_PASSWORD')
        );
    }
}
