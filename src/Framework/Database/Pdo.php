<?php

namespace Lightpack\Database;

use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Query\Query;
use PDOStatement;

class Pdo
{
    protected $statement;
    protected $connection;
    protected $queryLogs = [];

    public function __construct(
        string $dsn,
        string $username = null,
        string $password = null,
        array $options = null
    ) {
        try {
            $this->connection = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new \Exception('Database connection failed: \'' . $e->getMessage() . '\'');
        }

        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    /**
     * Returns an instance of query builder.
     *
     * @param string $table The table name to query against.
     * @return Query
     */
    public function table(string $table): Query
    {
        return new Query($table, $this);
    }

    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object.
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    public function query(string $sql, array $params = null): PDOStatement
    {
        $this->logQuery($sql, $params);

        if ($params) {
            $this->statement = $this->connection->prepare($sql);
            $this->statement->execute($params);
        } else {
            $this->statement = $this->connection->query($sql);
        }

        return $this->statement;
    }

    /**
     * Takes a classname as string and returns a Lucid Model
     * instance thereby making the class database connection 
     * aware.
     *
     * @param string $model
     * @return void
     */
    public function model(string $model): Model
    {
        $modelInstance = new $model;
        $modelInstance->setConnection($this);

        return $modelInstance;
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Returns an array of logged queries.
     *
     * @return array
     */
    public function getQueryLogs(): array
    {
        return $this->queryLogs;
    }

    /**
     * Prints all the logged queries.
     *
     * @return void
     */
    public function printQueryLogs(): void
    {
        pp($this->queryLogs);
    }

    /**
     * Initiates a transaction.
     *
     * @throws PDOException — If there is already a transaction started 
     *                        or the driver does not support transactions.
     * @return boolean
     */
    public function begin(): bool
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commits the current active transaction.
     *
     * @throws PDOException — if there is no active transaction.
     * @return boolean
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }

    /**
     * Rollsback a transaction.
     * 
     * Make sure to put this method call in a try-catch block
     * when executing transactions.
     *
     * @throws PDOException — if there is no active transaction.
     * @return boolean
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }

    /**
     * Returns the PDO connection instance.
     *
     * @return \PDO
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    /**
     * Return the PDO connection driver.
     *
     * @return string|null
     */
    public function getDriver(): ?string
    {
        return $this->connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    protected function logQuery($sql, $params)
    {
        if (false === get_env('APP_DEBUG', false)) {
            return;
        }

        $this->queryLogs['queries'][] = $sql;
        $this->queryLogs['bindings'][] = $params ?? [];
    }
}
