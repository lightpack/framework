<?php

namespace Lightpack\Database;

use Lightpack\Database\Lucid\Model;
use Lightpack\Database\Query\Query;
use PDOStatement;

/**
 * Database connection and query manager.
 * 
 * This class provides a clean interface for database operations including
 * query building, execution, and transaction management.
 * 
 * Transaction Management: this class implements a counter-based approach to 
 * nested transactions:
 * 
 * Transaction Nesting:
 *    - First begin() starts a real database transaction
 *    - Subsequent begin() calls increment an internal counter
 *    - Only the outermost commit()/rollback() affects the database
 * 
 * Key Design Principles:
 *    - Clean Separation: Each transaction unit is truly atomic
 *    - Simple & Reliable: No complex savepoint management
 *    - All operations in a transaction unit succeed or fail together
 *    - No partial commits or rollbacks (by design)
 *    - Clear, predictable behavior in all scenarios
 */
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
     * Returns current transaction nesting level.
     * 
     * Useful for:
     * - Debugging transaction state
     * - Testing nested transaction behavior
     * - Understanding current transaction context
     * 
     * Values:
     * - Level 0: No active transaction
     * - Level 1: In a top-level transaction
     * - Level > 1: In a nested transaction
     *
     * @return int Current transaction nesting level
     */
    public function getTransactionLevel(): int 
    {
        return $this->transactionLevel;
    }

    /**
     * Initiates a transaction or increments nesting level.
     * 
     * Behavior:
     * - First call: Starts real database transaction
     * - Subsequent calls: Increments nesting counter
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
        
        $this->transactionLevel++;
        return true;
    }

    /**
     * Commits the current transaction or decrements nesting level.
     * 
     * Behavior:
     * - No transaction active: Throws PDOException
     * - In nested transaction: Decrements counter only
     * - In top-level transaction: Commits all changes
     * 
     * @throws PDOException If there is no active transaction
     * @return boolean True on success
     */
    public function commit(): bool
    {
        if ($this->transactionLevel === 0) {
            throw new \PDOException('No active transaction to commit');
        }

        if ($this->transactionLevel > 1) {
            $this->transactionLevel--;
            return true;
        }

        $this->transactionLevel = 0;
        return $this->connection->commit();
    }

    /**
     * Rolls back the current transaction or decrements nesting level.
     * 
     * Behavior:
     * - No transaction active: Throws PDOException
     * - In nested transaction: Decrements counter only
     * - In top-level transaction: Rolls back all changes
     *
     * @throws PDOException If there is no active transaction
     * @return boolean True on success
     */
    public function rollback(): bool
    {
        if ($this->transactionLevel === 0) {
            throw new \PDOException('No active transaction to rollback');
        }

        if ($this->transactionLevel > 1) {
            $this->transactionLevel--;
            return true;
        }

        $this->transactionLevel = 0;
        return $this->connection->rollBack();
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

    /**
     * Execute a closure within a transaction.
     * 
     * Uses our counter-based transaction implementation to provide a clean,
     * closure-based API for transaction handling. The closure may optionally 
     * return a value which will be available after successful commit.
     * 
     * Example:
     * ```php
     * $result = $db->transaction(function() {
     *     $user->save();
     *     $profile->save();
     *     return $user;  // Optional
     * });
     * ```
     * 
     * @param callable $callback Closure containing transaction operations
     * @return mixed|null Value returned by closure after successful commit
     * @throws \Exception Rethrows any exception after rollback
     */
    public function transaction(callable $callback)
    {
        $this->begin();
        
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch(\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
