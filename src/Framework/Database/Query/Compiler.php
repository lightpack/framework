<?php

namespace Lightpack\Database\Query;

class Compiler
{
    private $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function compileSelect()
    {
        $sql[] = $this->select();
        $sql[] = $this->columns();
        $sql[] = $this->from();
        $sql[] = $this->join();
        $sql[] = $this->where();
        $sql[] = $this->groupBy();
        $sql[] = $this->having();
        $sql[] = $this->orderBy();
        $sql[] = $this->limit();
        $sql[] = $this->offset();
        $sql[] = $this->lock();

        $sql = array_filter($sql, function ($v) {
            return empty($v) === false;
        });
        return trim(implode(' ', $sql));
    }

    public function compileCountQuery()
    {
        $sql[] = $this->select();
        $sql[] = $this->columns();
        $sql[] = $this->from();
        $sql[] = $this->join();
        $sql[] = $this->where();
        $sql[] = $this->groupBy();
        $sql[] = $this->having(); // Insert HAVING after GROUP BY

        $sql = array_filter($sql, function ($v) {
            return empty($v) === false;
        });
        return trim(implode(' ', $sql));
    }

    public function compileInsert(array $columns, bool $shouldIgnore = false)
    {
        $parameters = $this->parameterize(count($columns));
        $parameters = count($columns) === 1 ? "($parameters)" : $parameters;
        $columns = implode(', ', $columns);
        $ignore = $shouldIgnore ? ' IGNORE ' : ' ';

        return "INSERT" . $ignore . "INTO {$this->query->table} ($columns) VALUES $parameters";
    }

    // public function compileInsertIgnore(array $columns)
    // {
    //     $parameters = $this->parameterize(count($columns));
    //     $parameters = count($columns) === 1 ? "($parameters)" : $parameters;
    //     $columns = implode(', ', $columns);
    //     return "INSERT IGNORE INTO {$this->query->table} ($columns) VALUES $parameters";
    // }

    public function compileBulkInsert(array $columns, array $values, bool $shouldIgnore = false)
    {
        foreach ($values as $value) {
            if (count($value) == 1) {
                $parameters[] = '(' . $this->parameterize(count($value)) . ')';
            } else {
                $parameters[] = $this->parameterize(count($value));
            }
        }

        $quotedColumns = array_map(function ($col) {
            return $this->query->getConnection()->quoteIdentifier($col);
        }, $columns);

        $columns = implode(', ', $quotedColumns);
        $values = implode(', ', $parameters);
        $ignore = $shouldIgnore ? ' IGNORE ' : ' ';
        $table = $this->query->getConnection()->quoteIdentifier($this->query->table);

        return "INSERT" . $ignore . "INTO {$table} ($columns) VALUES $values";
    }

    public function compileUpsert(array $columns, array $values, array $updateColumns)
    {
        // Compile the INSERT part
        foreach ($values as $value) {
            if (count($value) == 1) {
                $parameters[] = '(' . $this->parameterize(count($value)) . ')';
            } else {
                $parameters[] = $this->parameterize(count($value));
            }
        }

        $quotedColumns = array_map(function ($col) {
            return $this->query->getConnection()->quoteIdentifier($col);
        }, $columns);

        $columns = implode(', ', $quotedColumns);
        $values = implode(', ', $parameters);
        $table = $this->query->getConnection()->quoteIdentifier($this->query->table);

        // Compile the ON DUPLICATE KEY UPDATE part
        $updates = array_map(function ($col) {
            $quotedCol = $this->query->getConnection()->quoteIdentifier($col);
            return "{$quotedCol} = ?";
        }, $updateColumns);

        $updateClause = implode(', ', $updates);

        return "INSERT INTO {$table} ($columns) VALUES $values ON DUPLICATE KEY UPDATE $updateClause";
    }

    public function compileUpdate(array $columns)
    {
        $where = $this->where();
        $table = $this->query->getConnection()->quoteIdentifier($this->query->table);

        foreach ($columns as $column) {
            $quotedColumn = $this->query->getConnection()->quoteIdentifier($column);
            $columnValuePairs[] = $quotedColumn . ' = ?';
        }

        $columnValuePairs = implode(', ', $columnValuePairs);

        return "UPDATE {$table} SET {$columnValuePairs} {$where}";
    }

    public function compileIncrement(string $column, int $amount)
    {
        $where = $this->where();
        $table = $this->query->getConnection()->quoteIdentifier($this->query->table);
        $quotedColumn = $this->query->getConnection()->quoteIdentifier($column);

        // For positive amount, use + operator, for negative use - operator
        $operator = $amount >= 0 ? '+' : '-';
        $amount = abs($amount); // Use absolute value since operator is already determined

        return "UPDATE {$table} SET {$quotedColumn} = {$quotedColumn} {$operator} {$amount} {$where}";
    }

    public function compileDelete()
    {
        $where = $this->where();
        $table = $this->query->getConnection()->quoteIdentifier($this->query->table);
        return "DELETE FROM {$table} {$where}";
    }

    private function select(): string
    {
        return $this->query->distinct ? 'SELECT DISTINCT' : 'SELECT';
    }

    private function columns(): string
    {
        // Merge select_raw and columns, preserving order: select_raw first, then columns
        $raws = $this->query->select_raw ?? [];
        $columns = $this->query->columns ?? [];

        $allColumns = [];
        // Add all select_raw expressions as-is
        foreach ($raws as $raw) {
            $allColumns[] = $raw;
        }
        // Add normal columns, with wrapping/aggregate handling
        foreach ($columns as $column) {
            if (strpos($column, 'COUNT') !== false || strpos($column, 'SUM') !== false || strpos($column, 'AVG') !== false || strpos($column, 'MIN') !== false || strpos($column, 'MAX') !== false) {
                $allColumns[] = $column;
            } else {
                $allColumns[] = $this->wrapColumn($column);
            }
        }
        if (empty($allColumns)) {
            return '*';
        }
        return implode(', ', $allColumns);
    }

    private function from(): string
    {
        $table = $this->query->table . ($this->query->alias ? ' AS ' . $this->query->alias : '');

        return 'FROM ' . $this->wrapTable($table);
    }

    private function join()
    {
        if (!$this->query->join) {
            return '';
        }

        $joins = [];

        foreach ($this->query->join as $join) {
            $joins[] = strtoupper($join['type']) . ' JOIN ' . $this->wrapTable($join['table']) . ' ON ' . $this->wrapColumn($join['column1']) . ' = ' . $this->wrapColumn($join['column2']);
        }

        return implode(' ', $joins);
    }

    public function compileWhere()
    {
        return $this->where();
    }

    private function where(): string
    {
        if (!$this->query->where) {
            return '';
        }

        // $wheres[] = 'WHERE 1=1';
        $wheres = [];

        foreach ($this->query->where as $where) {
            $parameters = $this->parameterize(1);

            // Workaround for where exists queries
            if (isset($where['type']) && $where['type'] === 'where_exists') {
                $wheres[] = 'AND EXISTS' . ' ' . '(' . $where['sub_query'] . ')';
                continue;
            }

            // Workaround for where not exists queries
            if (isset($where['type']) && $where['type'] === 'where_not_exists') {
                $wheres[] = 'NOT EXISTS' . ' ' . '(' . $where['sub_query'] . ')';
                continue;
            }

            // Workaround for where group logical params
            if (isset($where['type']) && $where['type'] === 'where_logical_group') {
                $wheres[] = $where['joiner'] . ' (' . $where['sub_query'] . ')';
                continue;
            }

            // Workaround for where sub query
            if (isset($where['type']) && $where['type'] === 'where_sub_query') {
                $wheres[] = $where['joiner'] . ' ' . $this->wrap($where['column']) . ' ' . $where['operator'] . ' ' . '(' . $where['sub_query'] . ')';
                continue;
            }

            // Workaround for raw where queries
            if (isset($where['type']) && $where['type'] === 'where_raw') {
                $wheres[] = strtoupper($where['joiner']) . ' ' . $where['where'];
                continue;
            }

            // Workaround for where between queries
            if (isset($where['type']) && ($where['type'] === 'where_between' || $where['type'] === 'where_not_between')) {
                $parameters = $this->parameterize(2);
                $wheres[] = strtoupper($where['joiner']) . ' ' . $this->wrap($where['column']) . ' ' . $where['operator'] . ' ' . '?' . ' AND ' . '?';
                continue;
            }


            // Set parameters for multiple values
            if (isset($where['values'])) {
                $parameters = $this->parameterize(count($where['values']));
            }

            // Workaround for IN/NOT IN conditions
            if (($where['operator'] === 'IN' || $where['operator'] === 'NOT IN') && count($where['values']) === 1) {
                $parameters = '(' . $parameters . ')';
            }

            if (!isset($where['value']) && !isset($where['values'])) {
                $parameters = '';
            }

            // Finally prepare where clause
            $whereStatement = strtoupper($where['joiner']) . ' ' . $this->wrapColumn($where['column']);

            if (isset($where['operator'])) {
                $whereStatement .= ' ' . trim($where['operator']);
            }

            if ($parameters) {
                $whereStatement .= ' ' . $parameters;
            }

            $wheres[] = $whereStatement;
        }

        $wheres = trim(implode(' ', $wheres));

        if (strpos($wheres, 'AND') === 0) {
            $wheres = trim(substr($wheres, 3));
        }

        return $wheres ? 'WHERE ' . $wheres : null;
    }

    private function groupBy()
    {
        if (!$this->query->group) {
            return '';
        }

        $columns = array_map(function ($column) {
            return $this->wrapColumn($column);
        }, $this->query->group);

        return 'GROUP BY ' . implode(', ', $columns);
    }

    /**
     * Compile the HAVING clause for the query.
     */
    private function having()
    {
        if (!$this->query->having) {
            return '';
        }

        $havings = [];

        foreach ($this->query->having as $having) {
            // Raw HAVING
            if (isset($having['type']) && $having['type'] === 'having_raw') {
                $havings[] = strtoupper($having['joiner']) . ' ' . $having['having'];
                continue;
            }

            // Standard HAVING
            $statement = strtoupper($having['joiner']) . ' ' . $this->wrapColumn($having['column']);
            if (isset($having['operator'])) {
                $statement .= ' ' . trim($having['operator']);
            }
            if (array_key_exists('value', $having) && $having['value'] !== null) {
                if (
                    isset($having['operator']) &&
                    in_array(strtoupper($having['operator']), ['IN', 'NOT IN']) &&
                    is_array($having['value'])
                ) {
                    $statement .= ' ' . $this->parameterize(count($having['value']));
                } else {
                    $statement .= ' ?';
                }
            }
            $havings[] = $statement;
        }

        $havings = trim(implode(' ', $havings));
        if (strpos($havings, 'AND') === 0) {
            $havings = trim(substr($havings, 3));
        }
        return $havings ? 'HAVING ' . $havings : '';
    }

    private function orderBy()
    {
        if (!$this->query->order) {
            return '';
        }

        $orders = [];

        foreach ($this->query->order as $order) {
            $column = $this->wrapColumn($order['column']);
            $sort = $order['sort'];

            $orders[] = $column . ' ' . $sort;
        }

        return 'ORDER BY ' . implode(', ', $orders);
    }

    private function limit()
    {
        if (!$this->query->limit) {
            return '';
        }

        return 'LIMIT ' . $this->query->limit;
    }

    private function offset()
    {
        if (!$this->query->offset) {
            return '';
        }

        return 'OFFSET ' . $this->query->offset;
    }

    private function lock()
    {
        if (!$this->query->lock) {
            return '';
        }

        $fragment = '';

        if ($this->query->lock['for_update'] ?? false) {
            $fragment .= 'FOR UPDATE';
        }

        if ($this->query->lock['skip_locked'] ?? false) {
            $fragment .= ' SKIP LOCKED';
        }

        return $fragment;
    }

    private function parameterize(int $count)
    {
        $parameters = array_fill(0, $count, '?');
        $parameters = implode(', ', $parameters);

        if ($count > 1) {
            $parameters = '(' . $parameters . ')';
        }

        return $parameters;
    }

    private function wrapTable(string $table): string
    {
        if (strpos(strtolower($table), ' as ') !== false) {
            $parts = explode(' ', $table);
            return $this->wrap($parts[0]) . ' AS ' . $this->wrap($parts[2]);
        }
        return $this->wrap($table);
    }

    private function wrapColumn(string $column): string
    {
        if (is_numeric($column)) {
            return $column;
        }
        // Do not wrap aggregate or function expressions (e.g., COUNT(*), SUM(price), etc.)
        if (preg_match('/^[A-Z_]+\s*\(.*\)$/i', trim($column))) {
            return $column;
        }
        // Do not wrap raw expressions (if user already provided backticks or quotes)
        if (preg_match('/[`\(]/', $column)) {
            return $column;
        }

        if (strpos(strtolower($column), ' as ') !== false) {
            $parts = explode(' ', $column);
            foreach ($parts as &$part) {
                $part = $this->wrap($part);
            }
            return $parts[0] . ' AS ' . $parts[2];
        }
        return $this->wrap($column);
    }

    private function wrap(string $value): string
    {
        if ('*' === $value) {
            return $value;
        }
        $segments = explode('.', $value);
        if (count($segments) == 2) {
            return $this->wrap($segments[0]) . '.' . $this->wrap($segments[1]);
        }
        return '`' . str_replace('`', '``', $value) . '`';
    }
}
