<?php

namespace Lightpack\Database;

use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Query\Query;
use PDOStatement;

class DB
{
    protected $statement;
    protected $connection;
    protected $queryLogs = [];
    protected $transactionLevel = 0;

    /**
     * Database error codes that should be logged as critical
     */
    private const CRITICAL_ERROR_CODES = [
        1044, // Access denied
        1045, // Access denied for user
        1146, // Table doesn't exist
        1451, // Cannot delete or update a parent row (foreign key constraint)
        2002, // Connection refused
        2003, // Can't connect to MySQL server
        2006, // MySQL server has gone away
    ];

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
     * @throws \PDOException with sanitized error messages
     */
    public function query(string $sql, array $params = null): PDOStatement
    {
        $this->logQuery($sql, $params);

        try {
            $this->statement = $this->connection->prepare($sql);
            $this->statement->execute($params ?? []);
            return $this->statement;
        } catch (\PDOException $e) {
            // Log the detailed error for debugging
            $this->logError($e, $sql, $params);
            
            throw $e;
        }
    }

    /**
     * Takes a classname as string and returns a Lucid Model
     * instance thereby making the class database connection 
     * aware.
     *
     * @param string $model
     * @return Model
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
     * @codeCoverageIgnore
     */
    public function printQueryLogs(): void
    {
        pp($this->queryLogs);
    }

    public function clearQueryLogs(): void
    {
        $this->queryLogs = [];
    }

    /**
     * Initiates a transaction or increments the nesting level if already in one.
     * 
     * This method supports nested transactions through a counter mechanism:
     * - First call starts an actual database transaction
     * - Subsequent calls only increment an internal counter
     * - Only the outermost transaction interacts with the database
     *
     * @throws PDOException If the driver does not support transactions
     * @return boolean True on success
     */
    public function begin(): bool
    {
        if (!$this->inTransaction()) {
            $this->transactionLevel = 1;
            return $this->connection->beginTransaction();
        }
        
        // For nested transactions, just increment the level
        $this->transactionLevel++;
        return true;
    }

    /**
     * Commits the current transaction or decrements the nesting level.
     * 
     * For nested transactions:
     * - Inner commits only decrement the nesting counter
     * - Only the outermost commit actually commits to the database
     * - Maintains transaction isolation in testing environments
     *
     * @throws PDOException If there is no active transaction
     * @return boolean True on success
     */
    public function commit(): bool
    {
        if ($this->transactionLevel === 0) {
            throw new \PDOException('No active transaction to commit');
        }

        // For nested transactions, just decrement the level
        if ($this->transactionLevel > 1) {
            $this->transactionLevel--;
            return true;
        }

        // For the outermost transaction
        $this->transactionLevel = 0;
        return $this->connection->commit();
    }

    /**
     * Rolls back the current transaction or decrements the nesting level.
     * 
     * For nested transactions:
     * - Inner rollbacks only decrement the nesting counter
     * - Only the outermost rollback actually rolls back the database
     * - Particularly useful in testing where the test framework manages
     *   the outer transaction for isolation
     *
     * @throws PDOException If there is no active transaction
     * @return boolean True on success
     */
    public function rollback(): bool
    {
        if ($this->transactionLevel === 0) {
            throw new \PDOException('No active transaction to rollback');
        }

        // For nested transactions, just decrement the level
        if ($this->transactionLevel > 1) {
            $this->transactionLevel--;
            return true;
        }

        // For the outermost transaction
        $this->transactionLevel = 0;
        return $this->connection->rollBack();
    }

    /**
     * Returns current transaction nesting level.
     * Useful for debugging transaction issues.
     *
     * @return int
     */
    public function getTransactionLevel(): int 
    {
        return $this->transactionLevel;
    }

    /**
     * Quotes a string for use as a database identifier (table names, column names, etc.)
     * 
     * @param string $identifier The identifier to quote
     * @return string The quoted identifier
     */
    public function quoteIdentifier(string $identifier): string 
    {
        // Split identifier into parts (for handling table.column format)
        $parts = explode('.', $identifier);
        
        // Quote each part separately
        $parts = array_map(function($part) {
            return '`' . str_replace('`', '``', trim($part)) . '`';
        }, $parts);
        
        // Join the parts back together
        return implode('.', $parts);
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

    /**
     * Enable foreign key checks.
     *
     * @return void
     */
    public function enableForeignKeyChecks(): void
    {
        $this->query('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Disable foreign key checks.
     *
     * @return void
     */
    public function disableForeignKeyChecks(): void
    {
        $this->query('SET FOREIGN_KEY_CHECKS=0;');
    }

    protected function logQuery($sql, $params)
    {
        if (!get_env('APP_DEBUG')) {
            return;
        }

        $this->queryLogs['queries'][] = $sql;
        $this->queryLogs['bindings'][] = $params ?? [];
    }

    /**
     * Log database errors with appropriate severity
     */
    protected function logError(\PDOException $e, string $sql, ?array $params): void
    {
        $errorCode = (int) $e->getCode();
        $context = [
            'error_code' => $errorCode,
            'sql' => $sql,
            'params' => $params,
            'trace' => $e->getTraceAsString(),
        ];

        if (in_array($errorCode, self::CRITICAL_ERROR_CODES)) {
            app('logger')->critical($e->getMessage(), $context);
        } else {
            app('logger')->error($e->getMessage(), $context);
        }
    }

    /**
     * Checks if inside a transaction.
     *
     * @return boolean TRUE if a transaction is currently active, FALSE otherwise.
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }
}
