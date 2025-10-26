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
                get_env('DB_HOST'),
                get_env('DB_PORT'),
                get_env('DB_NAME')
            ),
            get_env('DB_USER'),
            get_env('DB_PSWD')
        );
    }
}
