<?php

namespace Lightpack\Database\Query;

use Closure;
use Lightpack\Database\DB;
use Lightpack\Pagination\Pagination;

class Query
{
    protected $connection;
    protected $table;
    protected $bindings = [];
    protected $components = [
        'alias' => null,
        'columns' => [],
        'select_raw' => [], // for selectRaw expressions
        'distinct' => false,
        'join' => [],
        'where' => [],
        'having' => [],
        'group' => [],
        'order' => [],
        'lock' => [],
        'limit' => null,
        'offset' => null,
    ];

    public function __construct($table = null, ?DB $connection = null)
    {
        $this->table = $table;
        $this->connection = $connection ?? app('db');
    }

    public function setConnection(DB $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function setTable(string $table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Insert methods do not return a value. On failure, a PDOException exception is thrown.
     */
    public function insert(array $data)
    {
        return $this->executeInsert($data);
    }

    public function insertIgnore(array $data)
    {
        return $this->executeInsert($data, true);
    }

    private function executeInsert(array $data, bool $shouldIgnore = false)
    {
        return $this->executeBulkInsert($data, $shouldIgnore);
    }

    private function executeBulkInsert(array $data, bool $shouldIgnore = false)
    {
        if (empty($data)) {
            return;
        }

        if (! is_array(reset($data))) {
            $data = [$data];
        }

        // Loop data to prepare for parameter binding and validate types
        foreach ($data as $row) {
            foreach ($row as $value) {
                if (!$this->isValidParameterType($value)) {
                    throw new \InvalidArgumentException(
                        'Invalid parameter type. Allowed types are: null, bool, int, float, string, DateTime'
                    );
                }
            }
            $this->bindings = array_merge($this->bindings, array_values($row));
        }

        $columns = array_keys($data[0]);
        $compiler = new Compiler($this);
        $query = $compiler->compileBulkInsert($columns, $data, $shouldIgnore);
        $result = $this->connection->query($query, $this->bindings);

        $this->resetQuery();
        return $result;
    }

    protected function isValidParameterType($value): bool
    {
        return is_null($value)
            || is_bool($value)
            || is_int($value)
            || is_float($value)
            || is_string($value)
            || $value instanceof \DateTime;
    }

    /**
     * Executes an update statement and returns the number of affected rows.
     * Throws exception on failure.
     *
     * @param array $data
     * @return int Number of affected rows
     */
    public function update(array $data): int
    {
        $compiler = new Compiler($this);
        $this->bindings = array_merge(array_values($data), $this->bindings);
        $query = $compiler->compileUpdate(array_keys($data));
        $stmt = $this->connection->query($query, $this->bindings);
        $this->resetQuery();
        return $stmt->rowCount();
    }

    /**
     * Executes a delete statement and returns the number of affected rows.
     * Throws exception on failure.
     *
     * @return int Number of affected rows
     */
    public function delete(): int
    {
        $compiler = new Compiler($this);
        $query = $compiler->compileDelete();
        $stmt = $this->connection->query($query, $this->bindings);
        $this->resetQuery();
        return $stmt->rowCount();
    }

    public function alias(string $alias): static
    {
        $this->components['alias'] = $alias;
        return $this;
    }

    public function select(string ...$columns): static
    {
        $this->components['columns'] = $columns;
        return $this;
    }

    /**
     * Add a raw select expression to the query, with optional bindings.
     *
     * Note: All columns added via selectRaw() are output first (in the order called),
     * followed by all columns added via select() (also in the order called).
     * This is a Lightpack design for explicit and predictable SQL generation.
     *
     * Example:
     *   $query->selectRaw('SUM(score) > ? AS high', [100])->select('name');
     *   // Generates: SELECT SUM(score) > ? AS high, `name` FROM ...
     *
     * @param string $expression Raw select SQL expression (may include parameter placeholders)
     * @param array $bindings Bindings for parameter placeholders in the expression
     * @return static
     */
    public function selectRaw(string $expression, array $bindings = []): static
    {
        $this->components['select_raw'][] = $expression;
        if ($bindings) {
            $this->bindings = array_merge($this->bindings, $bindings);
        }
        return $this;
    }

    /**
     * Add a HAVING clause to the query.
     *
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed $value
     * @param string $joiner
     * @return static
     */
    public function having($column, string $operator = '=', $value = null, string $joiner = 'AND'): static
    {
        // Operators that don't require a value
        $operators = ['IS NULL', 'IS NOT NULL', 'IS TRUE', 'IS NOT TRUE', 'IS FALSE', 'IS NOT FALSE'];

        if (!in_array($operator, $operators)) {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }
            $this->bindings[] = $value;
        }

        $this->components['having'][] = compact('column', 'operator', 'value', 'joiner');
        return $this;
    }

    /**
     * Add an OR HAVING clause to the query.
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed $value
     * @return static
     */
    public function orHaving($column, ?string $operator = null, $value = null): static
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * Add a raw HAVING clause to the query.
     * @param string $having
     * @param array $values
     * @param string $joiner
     * @return static
     */
    public function havingRaw(string $having, array $values = [], string $joiner = 'AND'): static
    {
        $type = 'having_raw';
        $this->components['having'][] = compact('type', 'having', 'values', 'joiner');
        if ($values) {
            $this->bindings = array_merge($this->bindings, $values);
        }
        return $this;
    }

    /**
     * Add a raw OR HAVING clause to the query.
     * @param string $having
     * @param array $values
     * @return static
     */
    public function orHavingRaw(string $having, array $values = []): static
    {
        return $this->havingRaw($having, $values, 'OR');
    }

    /**
     * Lock the fetched rows from update until the transaction is commited.
     */
    public function forUpdate(): static
    {
        $this->components['lock']['for_update'] = true;
        return $this;
    }

    /**
     * Skip any rows that are locked for update by other transactions.
     */
    public function skipLocked(): static
    {
        $this->components['lock']['skip_locked'] = true;
        return $this;
    }

    public function from(string $table, ?string $alias = null): static
    {
        $this->table = $table;
        $this->components['alias'] = $alias;
        return $this;
    }

    public function distinct(): static
    {
        $this->components['distinct'] = true;
        return $this;
    }

    /**
     * This method is used to conditionally build the where clause.
     * 
     * @param string|Closure $column
     * @param string|null $operator
     * @param mixed $value
     */
    public function where($column, string $operator = '=', $value = null, string $joiner = 'AND'): static
    {
        if ($column instanceof Closure) {
            return $this->whereColumnIsAClosure($column, $joiner);
        }

        if ($value instanceof Closure) {
            return $this->whereValueIsAClosure($value, $column, $operator, $joiner);
        }

        // Operators that don't require a value
        $operators = ['IS NULL', 'IS NOT NULL', 'IS TRUE', 'IS NOT TRUE', 'IS FALSE', 'IS NOT FALSE'];

        if (!in_array($operator, $operators)) {
            if ($value === null) {
                $value = $operator;
                $operator = '=';
            }

            $this->bindings[] = $value;
        }

        $this->components['where'][] = compact('column', 'operator', 'value', 'joiner');

        return $this;
    }

    public function whereRaw(string $where, array $values = [], string $joiner = 'AND'): static
    {
        $type = 'where_raw';

        $this->components['where'][] = compact('type', 'where', 'values', 'joiner');

        if ($values) {
            $this->bindings = array_merge($this->bindings, $values);
        }
        return $this;
    }

    public function orWhereRaw(string $where, array $values = []): static
    {
        $this->whereRaw($where, $values, 'OR');
        return $this;
    }

    public function orWhere($column, ?string $operator = null, $value = null): static
    {
        $this->where($column, $operator, $value, 'OR');
        return $this;
    }

    public function whereIn($column, $values = null, string $joiner = 'AND'): static
    {
        if ($values instanceof Closure) {
            $this->where($column, 'IN', $values, $joiner);
            return $this;
        }

        // Handle empty/null values: always false condition
        if (empty($values)) {
            $this->whereRaw('1=0', [], $joiner);
            return $this;
        }

        $operator = 'IN';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orWhereIn($column, $values): static
    {
        $this->whereIn($column, $values, 'OR');
        return $this;
    }

    public function whereNotIn($column, $values, string $joiner = 'AND'): static
    {
        if ($values instanceof Closure) {
            $this->where($column, 'NOT IN', $values, $joiner);
            return $this;
        }

        // Handle empty/null values: always true condition
        if (empty($values)) {
            $this->whereRaw('1=1', [], $joiner);
            return $this;
        }

        $operator = 'NOT IN';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orWhereNotIn($column, $values): static
    {
        $this->whereNotIn($column, $values, 'OR');
        return $this;
    }

    public function whereNull(string $column): static
    {
        $this->where($column, 'IS NULL');
        return $this;
    }

    public function whereNotNull(string $column): static
    {
        $this->where($column, 'IS NOT NULL');
        return $this;
    }

    public function orWhereNull(string $column): static
    {
        $this->orWhere($column, 'IS NULL');
        return $this;
    }

    public function orWhereNotNull(string $column): static
    {
        $this->orWhere($column, 'IS NOT NULL');
        return $this;
    }

    /**
     * Add a full-text search clause using the database's native full-text search.
     *
     * Only supports databases with native full-text search (e.g., MySQL/MariaDB MATCH ... AGAINST).
     * Requires a full-text index on the target columns. 
     * Only supports boolean mode.
     *
     * Example:
     *   $query->search('foo bar', ['title', 'body']);
     *   // WHERE MATCH(title, body) AGAINST ('foo bar' IN BOOLEAN MODE)
     *
     * @param string $term The search term.
     * @param array $columns Columns to search.
     * @return static
     */
    public function search(string $term, array $columns): static
    {
        $mode = 'IN BOOLEAN MODE';
        $columnsSql = implode(', ', array_map(function ($col) {
            return '`' . str_replace('`', '', $col) . '`';
        }, $columns));
        $sql = "MATCH($columnsSql) AGAINST (? $mode)";
        return $this->whereRaw($sql, [$term]);
    }

    /**
     * Add a WHERE clause that compares the DATE part of a column.
     *
     * Example:
     *   $query->whereDate('created_at', '2024-01-15');
     *   $query->whereDate('created_at', '>=', '2024-01-01');
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function whereDate(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return $this->whereRaw("DATE(`$column`) $operator ?", [$value]);
    }

    /**
     * Add an OR WHERE clause that compares the DATE part of a column.
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function orWhereDate(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return $this->orWhereRaw("DATE(`$column`) $operator ?", [$value]);
    }

    /**
     * Add a WHERE clause that compares the YEAR part of a column.
     *
     * Example:
     *   $query->whereYear('created_at', 2024);
     *   $query->whereYear('created_at', '>', 2023);
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function whereYear(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return $this->whereRaw("YEAR(`$column`) $operator ?", [(int)$value]);
    }

    /**
     * Add an OR WHERE clause that compares the YEAR part of a column.
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function orWhereYear(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return $this->orWhereRaw("YEAR(`$column`) $operator ?", [(int)$value]);
    }

    /**
     * Add a WHERE clause that compares the MONTH part of a column.
     *
     * Example:
     *   $query->whereMonth('created_at', 12);
     *   $query->whereMonth('created_at', 'dec');
     *   $query->whereMonth('created_at', 'december');
     *   $query->whereMonth('created_at', '>=', 6);
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function whereMonth(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        // Convert month name to number
        $value = $this->normalizeMonth($value);
        
        return $this->whereRaw("MONTH(`$column`) $operator ?", [(int)$value]);
    }

    /**
     * Add an OR WHERE clause that compares the MONTH part of a column.
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function orWhereMonth(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        // Convert month name to number
        $value = $this->normalizeMonth($value);
        
        return $this->orWhereRaw("MONTH(`$column`) $operator ?", [(int)$value]);
    }

    /**
     * Normalize month value - converts month names to numbers.
     *
     * @param mixed $month Month number (1-12) or name (jan, january, etc.)
     * @return int Month number (1-12)
     */
    protected function normalizeMonth($month): int
    {
        // If already a number, return it
        if (is_numeric($month)) {
            return (int)$month;
        }
        
        // Convert month name to number
        $monthMap = [
            'jan' => 1, 'january' => 1,
            'feb' => 2, 'february' => 2,
            'mar' => 3, 'march' => 3,
            'apr' => 4, 'april' => 4,
            'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'aug' => 8, 'august' => 8,
            'sep' => 9, 'september' => 9,
            'oct' => 10, 'october' => 10,
            'nov' => 11, 'november' => 11,
            'dec' => 12, 'december' => 12,
        ];
        
        $monthLower = strtolower(trim($month));
        
        if (isset($monthMap[$monthLower])) {
            return $monthMap[$monthLower];
        }
        
        // If not found, try to parse as date string
        $timestamp = strtotime($month);
        if ($timestamp !== false) {
            return (int)date('n', $timestamp);
        }
        
        // Default to the value as-is (will be cast to int)
        return (int)$month;
    }

    /**
     * Add a WHERE clause that compares the DAY part of a column.
     *
     * Example:
     *   $query->whereDay('created_at', 25);
     *   $query->whereDay('created_at', '<=', 15);
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function whereDay(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return $this->whereRaw("DAY(`$column`) $operator ?", [(int)$value]);
    }

    /**
     * Add an OR WHERE clause that compares the DAY part of a column.
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function orWhereDay(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return $this->orWhereRaw("DAY(`$column`) $operator ?", [(int)$value]);
    }

    /**
     * Add a WHERE clause that compares the TIME part of a column.
     *
     * Example:
     *   $query->whereTime('created_at', '14:30:00');
     *   $query->whereTime('created_at', '>=', '09:00:00');
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function whereTime(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return $this->whereRaw("TIME(`$column`) $operator ?", [$value]);
    }

    /**
     * Add an OR WHERE clause that compares the TIME part of a column.
     *
     * @param string $column Column name
     * @param string $operator Operator or value if operator is omitted
     * @param mixed $value Value to compare (optional if operator is omitted)
     * @return static
     */
    public function orWhereTime(string $column, string $operator = '=', $value = null): static
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        return $this->orWhereRaw("TIME(`$column`) $operator ?", [$value]);
    }

    /**
     * Filter records from today.
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function today(string $column = 'created_at'): static
    {
        return $this->whereDate($column, date('Y-m-d'));
    }

    /**
     * Filter records from yesterday.
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function yesterday(string $column = 'created_at'): static
    {
        return $this->whereDate($column, date('Y-m-d', strtotime('-1 day')));
    }

    /**
     * Filter records from this week (Monday to Sunday).
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function thisWeek(string $column = 'created_at'): static
    {
        $startOfWeek = date('Y-m-d', strtotime('monday this week'));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        return $this->whereDate($column, '>=', $startOfWeek)
                    ->whereDate($column, '<=', $endOfWeek);
    }

    /**
     * Filter records from last week.
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function lastWeek(string $column = 'created_at'): static
    {
        $startOfLastWeek = date('Y-m-d', strtotime('monday last week'));
        $endOfLastWeek = date('Y-m-d', strtotime('sunday last week'));
        return $this->whereDate($column, '>=', $startOfLastWeek)
                    ->whereDate($column, '<=', $endOfLastWeek);
    }

    /**
     * Filter records from this month.
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function thisMonth(string $column = 'created_at'): static
    {
        return $this->whereYear($column, date('Y'))
                    ->whereMonth($column, date('m'));
    }

    /**
     * Filter records from last month.
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function lastMonth(string $column = 'created_at'): static
    {
        $lastMonth = date('Y-m', strtotime('-1 month'));
        return $this->whereYear($column, date('Y', strtotime($lastMonth)))
                    ->whereMonth($column, date('m', strtotime($lastMonth)));
    }

    /**
     * Filter records from this year.
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function thisYear(string $column = 'created_at'): static
    {
        return $this->whereYear($column, date('Y'));
    }

    /**
     * Filter records from last year.
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function lastYear(string $column = 'created_at'): static
    {
        return $this->whereYear($column, date('Y') - 1);
    }

    /**
     * Filter records from the last N days.
     *
     * @param int $days Number of days
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function lastDays(int $days, string $column = 'created_at'): static
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        return $this->whereDate($column, '>=', $startDate);
    }

    /**
     * Filter records from the last N weeks.
     *
     * @param int $weeks Number of weeks
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function lastWeeks(int $weeks, string $column = 'created_at'): static
    {
        $startDate = date('Y-m-d', strtotime("-{$weeks} weeks"));
        return $this->whereDate($column, '>=', $startDate);
    }

    /**
     * Filter records from the last N months.
     *
     * @param int $months Number of months
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function lastMonths(int $months, string $column = 'created_at'): static
    {
        $startDate = date('Y-m-d', strtotime("-{$months} months"));
        return $this->whereDate($column, '>=', $startDate);
    }

    /**
     * Filter records older than specified time.
     *
     * @param int $value Time value
     * @param string $unit Time unit: 'minutes', 'hours', 'days', 'weeks', 'months', 'years'
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function olderThan(int $value, string $unit, string $column = 'created_at'): static
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$value} {$unit}"));
        return $this->where($column, '<', $date);
    }

    /**
     * Filter records newer than specified time.
     *
     * @param int $value Time value
     * @param string $unit Time unit: 'minutes', 'hours', 'days', 'weeks', 'months', 'years'
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function newerThan(int $value, string $unit, string $column = 'created_at'): static
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$value} {$unit}"));
        return $this->where($column, '>', $date);
    }

    /**
     * Filter records before a specific date.
     *
     * @param string $date Date string
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function before(string $date, string $column = 'created_at'): static
    {
        return $this->whereDate($column, '<', $date);
    }

    /**
     * Filter records after a specific date.
     *
     * @param string $date Date string
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function after(string $date, string $column = 'created_at'): static
    {
        return $this->whereDate($column, '>', $date);
    }

    /**
     * Filter records on weekdays only (Monday-Friday).
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function weekdays(string $column = 'created_at'): static
    {
        return $this->whereRaw("DAYOFWEEK(`$column`) BETWEEN 2 AND 6");
    }

    /**
     * Filter records on weekends only (Saturday-Sunday).
     *
     * @param string $column Column to filter (default: 'created_at')
     * @return static
     */
    public function weekends(string $column = 'created_at'): static
    {
        return $this->whereRaw("DAYOFWEEK(`$column`) IN (1, 7)");
    }

    public function whereBetween(string $column, array $values, string $joiner = 'AND'): static
    {
        if (count($values) !== 2) {
            throw new \Exception('You must provide two values for the between clause');
        }

        $operator = 'BETWEEN';
        $type = 'where_between';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner', 'type');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orWhereBetween($column, $values): static
    {
        $this->whereBetween($column, $values, 'OR');
        return $this;
    }

    public function whereNotBetween($column, $values, string $joiner = 'AND'): static
    {
        $operator = 'NOT BETWEEN';
        $type = 'where_not_between';
        $this->components['where'][] = compact('column', 'operator', 'values', 'joiner', 'type');
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    public function orWhereNotBetween($column, $values): static
    {
        $this->whereNotBetween($column, $values, 'OR');
        return $this;
    }

    public function whereTrue(string $column): static
    {
        $this->where($column, 'IS TRUE');
        return $this;
    }

    public function orWhereTrue(string $column): static
    {
        $this->orWhere($column, 'IS TRUE');
        return $this;
    }

    public function whereFalse(string $column): static
    {
        $this->where($column, 'IS FALSE');
        return $this;
    }

    public function orWhereFalse(string $column): static
    {
        $this->orWhere($column, 'IS FALSE');
        return $this;
    }

    public function whereExists(Closure $callback): static
    {
        $query = new Query();
        $callback($query);
        $this->components['where'][] = ['type' => 'where_exists', 'sub_query' => $query->toSql()];
        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }

    public function whereNotExists(Closure $callback): static
    {
        $query = new Query();
        $callback($query);
        $this->components['where'][] = ['type' => 'where_not_exists', 'sub_query' => $query->toSql()];
        return $this;
    }

    public function join(string $table, string $column1, string $column2, $type = 'INNER')
    {
        $this->components['join'][] = compact('table', 'column1', 'column2', 'type');
        return $this;
    }

    public function leftJoin(string $table, string $column1, string $column2)
    {
        $type = 'LEFT';
        $this->components['join'][] = compact('table', 'column1', 'column2', 'type');
        return $this;
    }

    public function rightJoin(string $table, string $column1, string $column2)
    {
        $type = 'RIGHT';
        $this->components['join'][] = compact('table', 'column1', 'column2', 'type');
        return $this;
    }

    public function groupBy(string ...$columns)
    {
        $this->components['group'] = $columns;
        return $this;
    }

    public function orderBy(string $column, $sort = 'ASC')
    {
        $this->components['order'][] = compact('column', 'sort');
        return $this;
    }

    /**
     * Order results by column in descending order.
     * Convenience method for orderBy($column, 'DESC').
     *
     * Example:
     *   $query->desc(); // ORDER BY id DESC
     *   $query->desc('created_at'); // ORDER BY created_at DESC
     *   $query->desc('price'); // ORDER BY price DESC (highest first)
     *
     * @param string $column Column to order by (default: 'id')
     * @return static
     */
    public function desc(string $column = 'id'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Order results by column in ascending order.
     * Convenience method for orderBy($column, 'ASC').
     *
     * Example:
     *   $query->asc(); // ORDER BY id ASC
     *   $query->asc('created_at'); // ORDER BY created_at ASC
     *   $query->asc('name'); // ORDER BY name ASC (alphabetical)
     *
     * @param string $column Column to order by (default: 'id')
     * @return static
     */
    public function asc(string $column = 'id'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    public function limit(int $limit)
    {
        $this->components['limit'] = $limit;
        return $this;
    }

    public function offset(int $offset)
    {
        $this->components['offset'] = $offset;
        return $this;
    }

    /**
     * This method paginates the results of the query.
     *
     * @param integer|null $limit
     * @param integer|null $page
     * @return \Lightpack\Pagination\Pagination
     */
    public function paginate(?int $limit = null, ?int $page = null)
    {
        // Preserve the columns because calling count() will reset the columns.
        $columns = $this->columns;
        $total = $this->count();
        $this->columns = $columns;
        $page = $page ?? request()->input('page');
        $page = (int) $page;
        $page = $page > 0 ? $page : 1;

        $limit = $limit ?: request()->input('limit', 10);

        $this->components['limit'] = $limit > 0 ? $limit : 10;
        $this->components['offset'] = $limit * ($page - 1);

        if ($total == 0) { // no need to query further
            return new Pagination([], $total);
        }

        $items = $this->fetchAll();

        return new Pagination($items, $total, $limit, $page);
    }

    public function exists(): bool
    {
        $this->components['columns'] = [];

        return $this->select('1')->one() !== null;
    }

    public function notExists(): bool
    {
        return !$this->exists();
    }

    public function count()
    {
        $this->columns = ['COUNT(*) AS total'];

        $query = $this->getCompiledCount();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        $this->columns = []; // so that pagination query can be reused

        return $result->total;
    }

    public function countBy(string $column)
    {
        $this->columns = [$column, 'COUNT(*) AS num'];
        $this->groupBy($column);

        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetchAll(\PDO::FETCH_OBJ);

        return $result;
    }

    public function sum(string $column)
    {
        $this->columns = ["SUM(`$column`) AS sum"];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        return $result->sum;
    }

    public function avg(string $column)
    {
        $this->columns = ["AVG(`$column`) AS avg"];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        return $result->avg;
    }

    public function min(string $column)
    {
        $this->columns = ["MIN(`$column`) AS min"];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        return $result->min;
    }

    public function max(string $column)
    {
        $this->columns = ["MAX(`$column`) AS max"];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);

        return $result->max;
    }

    public function __get(string $key)
    {
        if ($key === 'bindings') {
            return $this->bindings;
        }

        if ($key === 'table') {
            return $this->table;
        }

        return $this->components[$key] ?? null;
    }

    public function __set(string $key, $value)
    {
        if ($key === 'bindings') {
            $this->bindings = $value;
        }

        if ($key === 'table') {
            $this->table = $value;
        }

        if (isset($this->components[$key])) {
            $this->components[$key] = $value;
        }
    }

    protected function fetchAll()
    {
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetchAll(\PDO::FETCH_OBJ);
        $this->resetQuery();
        $this->resetBindings();
        $this->resetWhere();

        return $result;
    }

    public function all()
    {
        return $this->fetchAll();
    }

    protected function fetchOne()
    {
        $this->limit(1);
        $compiler = new Compiler($this);
        $query = $compiler->compileSelect();
        $result = $this->connection->query($query, $this->bindings)->fetch(\PDO::FETCH_OBJ);
        $this->resetQuery();

        if ($result == false) {
            return null;
        }

        return $result;
    }

    public function one()
    {
        return $this->fetchOne();
    }

    public function column(string $column)
    {
        $this->columns = [$column];
        $query = $this->getCompiledSelect();
        $result = $this->connection->query($query, $this->bindings)->fetchColumn();
        $this->resetQuery();
        $this->resetBindings();
        $this->resetWhere();

        return $result;
    }

    public function chunk(int $chunkSize, callable $callback)
    {
        if ($chunkSize <= 0) {
            throw new \InvalidArgumentException('Chunk size must be a positive integer');
        }

        // Clone the current query to preserve all conditions (where, join, etc.)
        $baseQuery = clone $this;

        $page = 0;

        do {
            // Apply pagination to a clone of the base query
            $query = clone $baseQuery;
            $records = $query->limit($chunkSize)->offset($page * $chunkSize)->all();

            // Exit the loop if no records were found
            if (count($records) === 0) {
                break;
            }

            // Process the records and check if we should stop
            if (false === call_user_func($callback, $records)) {
                return;
            }

            $page++;
        } while (true);
    }

    /**
     * Execute the query and return a generator that yields one row at a time.
     * 
     * This is memory-efficient for large result sets as it fetches rows one at a time
     * instead of loading all results into memory at once.
     * 
     * Example:
     *   foreach ($query->where('status', 'active')->cursor() as $user) {
     *       // Process one user at a time
     *       // Memory usage: O(1) instead of O(n)
     *   }
     * 
     * @return \Generator
     */
    public function cursor(): \Generator
    {
        $sql = $this->getCompiledSelect();
        $stmt = $this->connection->query($sql, $this->bindings);
        
        while ($row = $stmt->fetch(\PDO::FETCH_OBJ)) {
            yield $row;
        }
    }

    public function getCompiledSelect()
    {
        $compiler = new Compiler($this);
        return $compiler->compileSelect();
    }

    public function getCompiledCount()
    {
        $compiler = new Compiler($this);
        return $compiler->compileCountQuery();
    }

    public function toSql()
    {
        return $this->getCompiledSelect();
    }

    public function resetQuery()
    {
        $this->components['alias'] = null;
        $this->components['columns'] = [];
        $this->components['select_raw'] = [];
        $this->components['distinct'] = false;
        $this->components['where'] = [];
        $this->components['join'] = [];
        $this->components['group'] = [];
        $this->components['order'] = [];
        $this->components['lock'] = [];
        $this->components['limit'] = null;
        $this->components['offset'] = null;
        $this->bindings = [];
    }

    public function resetWhere()
    {
        $this->components['where'] = [];
    }

    public function resetBindings()
    {
        $this->bindings = [];
    }

    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    protected function whereColumnIsAClosure(Closure $callback, string $joiner)
    {
        $query = new Query();
        call_user_func($callback, $query);
        $compiler = new Compiler($query);
        $subQuery = substr($compiler->compileWhere(), 6); // strip WHERE prefix
        $this->components['where'][] = ['type' => 'where_logical_group', 'sub_query' => $subQuery, 'joiner' => $joiner];
        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }

    protected function whereValueIsAClosure(Closure $callback, string $column, string $operator, string $joiner)
    {
        $query = new Query();
        call_user_func($callback, $query);
        $subQuery = $query->toSql();
        $this->components['where'][] = ['type' => 'where_sub_query', 'sub_query' => $subQuery, 'joiner' => $joiner, 'column' => $column, 'operator' => $operator];
        $this->bindings = array_merge($this->bindings, $query->bindings);
        return $this;
    }

    /**
     * Conditionally add a where clause if the condition is a truthy value.
     * 
     * @param mixed $condition The condition to check
     * @param string|Closure $column Column name or closure
     * @param string|null $operator Operator (optional)
     * @param mixed $compareValue Value to compare against (optional)
     */
    public function whereIf($condition, $column, string $operator = '=', $compareValue = null): static
    {
        if ($condition) {
            return $this->where($column, $operator, $compareValue);
        }

        return $this;
    }

    /**
     * Execute a query callback when condition is a truthy value.
     * 
     * @param mixed $condition The condition to check
     * @param Closure $callback Callback to execute if condition is true
     */
    public function when($condition, Closure $callback): static
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Insert or update records using MySQL's ON DUPLICATE KEY UPDATE.
     *
     * Requirements:
     * - Table must have a single, auto-incrementing primary key.
     * - Columns for upsert must be unique or primary key columns.
     * - $data must be an associative array or array of associative arrays.
     * - $updateColumns should be columns present in $data (optional).
     *
     * @param array $data Row or rows to insert/update.
     * @param array|null $updateColumns Columns to update on duplicate key.
     * @return mixed Query result.
     */
    public function upsert(array $data, ?array $updateColumns = null)
    {
        if (empty($data)) {
            return;
        }

        // If update columns not specified, use all columns
        if ($updateColumns === null) {
            $updateColumns = array_keys(is_array(reset($data)) ? reset($data) : $data);
        }

        // Handle both single and bulk upsert
        if (!is_array(reset($data))) {
            $data = [$data];
        }

        // Validate data types
        foreach ($data as $row) {
            foreach ($row as $value) {
                if (!$this->isValidParameterType($value)) {
                    throw new \InvalidArgumentException(
                        'Invalid parameter type. Allowed types are: null, bool, int, float, string, DateTime'
                    );
                }
            }
            $this->bindings = array_merge($this->bindings, array_values($row));
        }

        // Add update values to bindings
        foreach ($data[0] as $key => $value) {
            if (in_array($key, $updateColumns)) {
                $this->bindings[] = $value;
            }
        }

        $compiler = new Compiler($this);
        $query = $compiler->compileUpsert(array_keys($data[0]), $data, $updateColumns);
        $result = $this->connection->query($query, $this->bindings);

        $this->resetQuery();
        return $result;
    }

    public function increment(string $column, int $amount = 1)
    {
        return $this->incrementOrDecrement($column, $amount);
    }

    public function decrement(string $column, int $amount = 1)
    {
        return $this->incrementOrDecrement($column, -$amount);
    }

    protected function incrementOrDecrement(string $column, int $amount)
    {
        if (empty($this->components['where'])) {
            throw new \RuntimeException('Increment/Decrement operations require a where clause');
        }

        $compiler = new Compiler($this);
        $query = $compiler->compileIncrement($column, $amount);
        $result = $this->connection->query($query, $this->bindings);

        $this->resetQuery();
        return $result;
    }
}
