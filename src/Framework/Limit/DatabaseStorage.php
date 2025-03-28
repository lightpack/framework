<?php

namespace Lightpack\Limit;

use Lightpack\Database\DB;
use Lightpack\Container\Container;

class DatabaseStorage implements Storage 
{
    private $table;
    private $db;
    
    public function __construct() 
    {
        /** @var DB */
        $db = Container::getInstance()->get('db');

        $this->table = config('limit.table', 'limits');
        $this->db = $db->table($this->table);
    }
    
    public function exists(string $key): bool 
    {
        $row = $this->db->where('id', $key)
            ->where('reset_at', '>', date('Y-m-d H:i:s'))
            ->one();
            
        return $row !== null;
    }
    
    public function create(string $key, int $minutes): void 
    {
        $this->db->insert([
            'id' => $key,
            'hits' => 1,
            'reset_at' => date('Y-m-d H:i:s', time() + ($minutes * 60)),
        ]);
    }
    
    public function increment(string $key): void 
    {
        $row = $this->db->where('id', $key)->one();
        
        if ($row) {
            $this->db->where('id', $key)->update([
                'hits' => $row->hits + 1,
            ]);
        }
    }
    
    public function getHits(string $key): int 
    {
        $row = $this->db->where('id', $key)
            ->where('reset_at', '>', date('Y-m-d H:i:s'))
            ->one();
            
        return $row ? $row->hits : 0;
    }
    
    public function delete(string $key): void 
    {
        $this->db->where('id', $key)->delete();
    }
    
    public function deleteExpired(): void 
    {
        $this->db->where('reset_at', '<', date('Y-m-d H:i:s'))->delete();
    }
}
