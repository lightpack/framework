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
        $sql[] = $this->orderBy();
        $sql[] = $this->limit();
        $sql[] = $this->offset();

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

        $columns = implode(', ', $columns);
        $values = implode(', ', $parameters);
        $ignore = $shouldIgnore ? ' IGNORE ' : ' ';

        return "INSERT" . $ignore . "INTO {$this->query->table} ($columns) VALUES $values";
    }

    public function compileUpdate(array $columns)
    {
        $where = $this->where();

        foreach ($columns as $column) {
            $columnValuePairs[] = $column . ' = ?';
        }

        $columnValuePairs = implode(', ', $columnValuePairs);

        return "UPDATE {$this->query->table} SET {$columnValuePairs} {$where}";
    }

    public function compileDelete()
    {
        $where = $this->where();
        return "DELETE FROM {$this->query->table} {$where}";
    }

    private function select(): string
    {
        return $this->query->distinct ? 'SELECT DISTINCT' : 'SELECT';
    }

    private function columns(): string
    {
        if (!$this->query->columns) {
            return '*';
        }

        $columns = array_map(function ($column) {
            if(strpos($column, 'COUNT') === 0) {
                return $column;
            }

            return $this->wrapColumn($column);
        }, $this->query->columns);

        return implode(', ', $columns);
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
        $parts = explode(' AS ', $table);
        $wrappedParts = [];
        
        if(count($parts) == 2) {
            foreach ($parts as $part) {
                $wrappedParts[] = '`' . str_replace('`', '``', $part) . '`';
            }
        
            return implode(' AS ', $wrappedParts);
        }

        return '`' . str_replace('`', '``', $table) . '`';
    }

    private function wrapColumn(string $column): string
{
    $parts = explode(' as ', strtolower($column));

    foreach ($parts as &$part) {
        $part = trim($part);
        if ($part !== '*') {
            $segments = explode('.', $part);
            foreach ($segments as &$segment) {
                if($segment == '*') {
                    continue;
                }
                $segment = '`' . str_replace('`', '``', $segment) . '`';
            }
            $part = implode('.', $segments);
        }
    }

    return implode(' AS ', $parts);
}

    private function wrap(string $key): string
    {
        $parts = explode(' ', $key);
        $name = $parts[0];
        $alias = isset($parts[1]) ? $parts[1] : null;

        $wrappedName = $this->wrapKey($name);

        if ($alias !== null) {
            $wrappedAlias = $this->wrapKey($alias);
            $wrappedName .= ' AS ' . $wrappedAlias;
        }

        return $wrappedName;
    }

    private function wrapKey(string $key): string
    {
        return '`' . str_replace('`', '``', $key) . '`';
    }
}
