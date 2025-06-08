<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Compilers\IndexKey;

class IndexKeyTest extends TestCase
{
    public function testCompositeIndexNameIsShortenedWhenTooLong()
    {
        $columns = [];
        // Create 10 long column names
        for ($i = 0; $i < 10; $i++) {
            $columns[] = 'very_long_column_name_' . $i;
        }
        $indexType = 'UNIQUE';
        $indexKey = new IndexKey();
        $sql = $indexKey->compile($columns, $indexType);
        // Extract the index name from the SQL
        $matches = [];
        preg_match('/UNIQUE ([^ ]+) /', $sql, $matches);
        $indexName = $matches[1] ?? '';
        $this->assertStringStartsWith('idx_', $indexName, 'Index name should be hash-based when too long');
        $this->assertLessThanOrEqual(64, strlen($indexName), 'Index name should not exceed DB limits');
    }

    public function testCompositeIndexNameIsNotShortenedWhenShort()
    {
        $columns = ['foo', 'bar'];
        $indexType = 'UNIQUE';
        $indexKey = new IndexKey();
        $sql = $indexKey->compile($columns, $indexType);
        $this->assertStringContainsString('foo_bar_unique', $sql);
    }

    public function testCustomIndexNameIsAlwaysUsed()
    {
        $columns = ['foo', 'bar'];
        $indexType = 'UNIQUE';
        $customName = 'my_custom_index';
        $indexKey = new IndexKey();
        $sql = $indexKey->compile($columns, $indexType, $customName);
        $this->assertStringContainsString($customName, $sql);
    }
}
