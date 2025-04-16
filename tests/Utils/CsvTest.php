<?php

namespace Lightpack\Tests\Utils;

use Lightpack\Utils\Csv;

class CsvTest extends \PHPUnit\Framework\TestCase
{
    private string $testFile;
    private Csv $csv;

    protected function setUp(): void
    {
        $this->testFile = __DIR__ . '/test.csv';
        $this->csv = new Csv();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFile)) {
            unlink($this->testFile);
        }
    }

    private function cleanDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        chmod($dir, 0777); // Reset permissions
        
        $files = new \DirectoryIterator($dir);
        foreach ($files as $file) {
            if ($file->isDot()) continue;
            if ($file->isDir()) {
                $this->cleanDir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    public function testReadWithHeaders()
    {
        $data = "name,age,active\nJohn,25,true\nJane,30,false\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv->read($this->testFile));

        $this->assertCount(2, $rows);
        $this->assertEquals([
            'name' => 'John',
            'age' => '25',
            'active' => 'true'
        ], $rows[0]);
    }

    public function testReadWithoutHeaders()
    {
        $data = "John,25,true\nJane,30,false\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv->read($this->testFile, false));

        $this->assertCount(2, $rows);
        $this->assertEquals(['John', '25', 'true'], $rows[0]);
    }

    public function testReadWithTypeCasting()
    {
        $data = "name,age,active,balance,joined\n";
        $data .= "John,25,true,100.50,2023-01-01\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv->casts([
            'age' => 'int',
            'active' => 'bool',
            'balance' => 'float',
            'joined' => 'date'
        ])->read($this->testFile));

        $row = $rows[0];
        $this->assertIsInt($row['age']);
        $this->assertIsBool($row['active']);
        $this->assertIsFloat($row['balance']);
        $this->assertIsInt($row['joined']); // timestamp
        $this->assertEquals(25, $row['age']);
        $this->assertTrue($row['active']);
        $this->assertEquals(100.50, $row['balance']);
    }

    public function testWriteWithHeaders()
    {
        $data = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30],
        ];

        $this->csv->write($this->testFile, $data, ['name', 'age']);

        $content = file_get_contents($this->testFile);
        $expected = "name,age\nJohn,25\nJane,30\n";
        $this->assertEquals($expected, $content);
    }

    public function testWriteWithGenerator()
    {
        $generator = function() {
            yield ['name' => 'John', 'age' => 25];
            yield ['name' => 'Jane', 'age' => 30];
        };

        $this->csv->write($this->testFile, $generator(), ['name', 'age']);

        $content = file_get_contents($this->testFile);
        $expected = "name,age\nJohn,25\nJane,30\n";
        $this->assertEquals($expected, $content);
    }

    public function testCustomDelimiter()
    {
        $data = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30],
        ];

        $this->csv->setDelimiter(';')->write($this->testFile, $data, ['name', 'age']);

        $content = file_get_contents($this->testFile);
        $expected = "name;age\nJohn;25\nJane;30\n";
        $this->assertEquals($expected, $content);

        // Test reading with custom delimiter
        $rows = iterator_to_array($this->csv->setDelimiter(';')->read($this->testFile));
        $this->assertEquals('John', $rows[0]['name']);
    }

    public function testReadNonExistentFile()
    {
        $this->expectException(\RuntimeException::class);
        iterator_to_array($this->csv->read('nonexistent.csv'));
    }

    public function testWriteToNonExistentDirectory()
    {
        $data = [['name' => 'John']];
        $file = __DIR__ . '/nonexistent/test.csv';
        
        $this->csv->write($file, $data);
        $this->assertTrue(file_exists($file));
        
        // Cleanup
        unlink($file);
        rmdir(dirname($file));
    }

    public function testReadWriteLargeFile()
    {
        // Generate 1000 rows
        $generator = function() {
            for ($i = 0; $i < 1000; $i++) {
                yield ['id' => $i, 'data' => str_repeat('x', 100)];
            }
        };

        // Write large file
        $this->csv->write($this->testFile, $generator(), ['id', 'data']);

        // Read and verify
        $count = 0;
        foreach ($this->csv->casts(['id' => 'int'])->read($this->testFile) as $row) {
            $this->assertEquals($count, $row['id']);
            $count++;
        }
        $this->assertEquals(1000, $count);
    }

    public function testMapColumns()
    {
        $data = "user_id,user_name\n1,john\n2,jane\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv->map([
            'user_id' => 'id',
            'user_name' => 'name'
        ])->read($this->testFile));

        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('name', $rows[0]);
        $this->assertArrayNotHasKey('user_id', $rows[0]);
        $this->assertArrayNotHasKey('user_name', $rows[0]);
    }

    public function testMapWithCallable()
    {
        $data = "name,age\njohn,25\njane,30\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv->map([
            'name' => fn($v) => strtoupper($v),
            'age' => fn($v) => (int)$v + 1
        ])->read($this->testFile));

        $this->assertEquals('JOHN', $rows[0]['name']);
        $this->assertEquals(26, $rows[0]['age']);
        $this->assertEquals('JANE', $rows[1]['name']);
        $this->assertEquals(31, $rows[1]['age']);
    }

    public function testExcludeColumns()
    {
        $data = "id,name,password,token\n1,john,secret,abc123\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv->except(['password', 'token'])->read($this->testFile));

        $this->assertArrayHasKey('id', $rows[0]);
        $this->assertArrayHasKey('name', $rows[0]);
        $this->assertArrayNotHasKey('password', $rows[0]);
        $this->assertArrayNotHasKey('token', $rows[0]);
    }

    public function testChainMapExceptAndCasts()
    {
        $data = "user_id,user_name,age,password\n1,john,25,secret\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv
            ->map([
                'user_id' => 'id',
                'user_name' => 'name'
            ])
            ->except(['password'])
            ->casts(['id' => 'int', 'age' => 'int'])
            ->read($this->testFile));

        $row = $rows[0];
        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayNotHasKey('password', $row);
        $this->assertIsInt($row['id']);
        $this->assertIsInt($row['age']);
        $this->assertEquals('john', $row['name']);
    }

    public function testWriteWithTransformations()
    {
        // Original data with transformed column names
        $data = [
            ['id' => 1, 'name' => 'JOHN', 'age' => 25],
            ['id' => 2, 'name' => 'JANE', 'age' => 30]
        ];

        // Write with reverse transformations
        $this->csv->map([
            'user_id' => 'id',
            'user_name' => 'name'
        ])->write($this->testFile, $data, ['user_id', 'user_name', 'age']);

        // Check the raw CSV content
        $content = file_get_contents($this->testFile);
        $expected = "user_id,user_name,age\n1,JOHN,25\n2,JANE,30\n";
        $this->assertEquals($expected, $content);

        // Read it back with transformations
        $rows = iterator_to_array($this->csv->map([
            'user_id' => 'id',
            'user_name' => 'name'
        ])->read($this->testFile));

        // Verify the transformations worked both ways
        $this->assertEquals('id', array_key_first($rows[0]));
        $this->assertEquals(1, $rows[0]['id']);
        $this->assertEquals('JOHN', $rows[0]['name']);
    }

    public function testWriteWithCasts()
    {
        $data = [
            ['id' => 1, 'active' => true, 'price' => 10.5],
            ['id' => 2, 'active' => false, 'price' => 20.75]
        ];

        // Write with type casting
        $this->csv->casts([
            'id' => 'int',
            'active' => 'bool',
            'price' => 'float'
        ])->write($this->testFile, $data, ['id', 'active', 'price']);

        // Check raw CSV content
        $content = file_get_contents($this->testFile);
        $expected = "id,active,price\n1,true,10.5\n2,false,20.75\n";
        $this->assertEquals($expected, $content);

        // Read it back with same casts
        $rows = iterator_to_array($this->csv->casts([
            'id' => 'int',
            'active' => 'bool',
            'price' => 'float'
        ])->read($this->testFile));

        // Verify types are preserved
        $this->assertIsInt($rows[0]['id']);
        $this->assertIsBool($rows[0]['active']);
        $this->assertIsFloat($rows[0]['price']);
    }

    public function testWriteWithExcludes()
    {
        $data = [
            ['id' => 1, 'name' => 'John', 'password' => 'secret'],
            ['id' => 2, 'name' => 'Jane', 'password' => 'secret']
        ];

        // Write with exclusions
        $this->csv->except(['password'])
            ->write($this->testFile, $data, ['id', 'name']);

        // Check raw CSV content
        $content = file_get_contents($this->testFile);
        $expected = "id,name\n1,John\n2,Jane\n";
        $this->assertEquals($expected, $content);
    }

    public function testChainedTransformationsInWrite()
    {
        $data = [
            ['id' => 1, 'name' => 'JOHN', 'age' => 25, 'password' => 'secret'],
            ['id' => 2, 'name' => 'JANE', 'age' => 30, 'password' => 'secret']
        ];

        // Apply all transformations
        $this->csv->map([
            'user_id' => 'id',
            'user_name' => 'name'
        ])
        ->except(['password'])
        ->casts([
            'age' => 'int'
        ])
        ->write($this->testFile, $data, ['user_id', 'user_name', 'age']);

        // Verify the output
        $content = file_get_contents($this->testFile);
        $expected = "user_id,user_name,age\n1,JOHN,25\n2,JANE,30\n";
        $this->assertEquals($expected, $content);
    }

    public function testReadWithLimit()
    {
        $data = "id,name\n1,john\n2,jane\n3,bob\n4,alice\n";
        file_put_contents($this->testFile, $data);

        // Read only first 2 rows
        $rows = iterator_to_array($this->csv->limit(2)->read($this->testFile));
        
        $this->assertCount(2, $rows);
        $this->assertEquals('1', $rows[0]['id']);
        $this->assertEquals('2', $rows[1]['id']);

        // Read with zero limit (no rows)
        $rows = iterator_to_array($this->csv->limit(0)->read($this->testFile));
        $this->assertCount(0, $rows);
    }

    public function testLimitWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->csv->limit(-1);
    }

    public function testMaxEnforcement()
    {
        $data = "id,name\n1,john\n2,jane\n3,bob\n4,alice\n";
        file_put_contents($this->testFile, $data);

        // Should pass (4 rows, max 5)
        $rows = iterator_to_array($this->csv->max(5)->read($this->testFile));
        $this->assertCount(4, $rows);

        // Should fail (4 rows, max 3)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CSV contains 4 rows. Maximum 3 rows allowed');
        iterator_to_array($this->csv->max(3)->read($this->testFile));
    }

    public function testMaxWithInvalidValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->csv->max(-1);
    }

    public function testMaxWithHeader()
    {
        $data = "id,name\n1,john\n2,jane\n";
        file_put_contents($this->testFile, $data);

        // Should pass (2 rows + header, max 3)
        $rows = iterator_to_array($this->csv->max(3)->read($this->testFile, true));
        $this->assertCount(2, $rows);

        // Should fail (2 rows, max 1)
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('CSV contains 2 rows. Maximum 1 rows allowed');
        iterator_to_array($this->csv->max(1)->read($this->testFile, true));
    }

    public function testValidateSkipInvalid()
    {
        $data = "id,age,email\n1,25,john@test\n2,invalid,bob@test\n3,30,invalid\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv
            ->validate(function($row) {
                $errors = [];
                if (!is_numeric($row['age'])) $errors[] = 'Invalid age';
                if (!str_contains($row['email'], '@test')) $errors[] = 'Invalid email';
                return $errors;
            })
            ->read($this->testFile));

        // Should skip invalid rows
        $this->assertCount(1, $rows);
        $this->assertEquals('1', $rows[0]['id']);
    }

    public function testValidateCollectErrors()
    {
        $data = "id,age,email\n1,25,john@test\n2,invalid,bob@test\n3,30,invalid\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv
            ->validate(function($row) {
                $errors = [];
                if (!is_numeric($row['age'])) $errors[] = 'Invalid age';
                if (!str_contains($row['email'], '@test')) $errors[] = 'Invalid email';
                return $errors;
            }, 'collect')
            ->read($this->testFile));

        // Should include all rows and collect errors
        $this->assertCount(3, $rows);
        $this->assertCount(2, $this->csv->getErrors());
        
        // Row 2 has invalid age
        $this->assertStringContainsString('Row 2: Invalid age', $this->csv->getErrors()[0]);
        
        // Row 3 has invalid email
        $this->assertStringContainsString('Row 3: Invalid email', $this->csv->getErrors()[1]);
    }

    public function testValidateFailOnInvalid()
    {
        $data = "id,age,email\n1,25,john@test\n2,invalid,invalid\n3,30,bob@test\n";
        file_put_contents($this->testFile, $data);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Row 2: Invalid age, Invalid email');

        iterator_to_array($this->csv
            ->validate(function($row) {
                $errors = [];
                if (!is_numeric($row['age'])) $errors[] = 'Invalid age';
                if (!str_contains($row['email'], '@test')) $errors[] = 'Invalid email';
                return $errors;
            }, 'fail')
            ->read($this->testFile));
    }

    public function testValidateWithStringError()
    {
        $data = "id,age,email\n1,25,john@test\n2,-5,bob@test\n3,30,invalid\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv
            ->validate(function($row) {
                if ($row['age'] < 0) {
                    return 'Age cannot be negative';
                }
                if (!str_contains($row['email'], '@test')) {
                    return 'Invalid email domain';
                }
                return true;
            }, 'collect')
            ->read($this->testFile));

        $this->assertCount(3, $rows);
        $this->assertCount(2, $this->csv->getErrors());
        $this->assertStringContainsString('Age cannot be negative', $this->csv->getErrors()[0]);
        $this->assertStringContainsString('Invalid email domain', $this->csv->getErrors()[1]);
    }

    public function testValidateWithBooleanResult()
    {
        $data = "id,age\n1,25\n2,-5\n3,30\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv
            ->validate(fn($row) => $row['age'] >= 0, 'collect')
            ->read($this->testFile));

        $this->assertCount(3, $rows);
        $this->assertCount(1, $this->csv->getErrors());
        $this->assertStringContainsString('Row 2: Failed validation', $this->csv->getErrors()[0]);
    }

    public function testValidateWithLightpackValidator()
    {
        $data = "id,age,email,salary\n1,17,invalid,1000\n2,25,john@test.com,2000\n3,30,bob@test.com,-500\n";
        file_put_contents($this->testFile, $data);

        $rows = iterator_to_array($this->csv
            ->validate(function($row) {
                $validator = new \Lightpack\Validation\Validator();
                
                $validator
                    ->field('age')->required()->numeric()->custom(fn($val) => $val >= 18, 'Must be 18 or older')
                    ->field('email')->required()->email()
                    ->field('salary')->required()->numeric()->custom(fn($val) => $val >= 0, 'Cannot be negative');

                $validator->setInput($row);
                $result = $validator->validate();
                
                if ($result->passes()) {
                    return true;
                }

                return $result->getErrors();
            }, 'collect')
            ->read($this->testFile));

        $this->assertCount(3, $rows);
        $this->assertCount(2, $this->csv->getErrors());
        
        // Row 1: Age < 18 and invalid email
        $this->assertStringContainsString('Must be 18 or older', $this->csv->getErrors()[0]);
        $this->assertStringContainsString('Must be a valid email address', $this->csv->getErrors()[0]);
        
        // Row 3: Negative salary
        $this->assertStringContainsString('Cannot be negative', $this->csv->getErrors()[1]);
    }

    public function testWriteWithColumnOrder()
    {
        $data = [
            [
                'id' => 1,
                'name' => 'JOHN',
                'age' => 25,
                'email' => 'john@test.com'
            ]
        ];

        // Specify a different order in headers
        $this->csv->map([
            'User ID' => 'id',
            'Full Name' => 'name',
            'Email Address' => 'email',
            'Age' => 'age'
        ])->write($this->testFile, $data, [
            'Email Address',  // Should appear first
            'Age',           // Should appear second
            'Full Name',     // Should appear third
            'User ID'        // Should appear last
        ]);

        // Verify the order in the CSV
        $content = file_get_contents($this->testFile);
        $expected = "\"Email Address\",Age,\"Full Name\",\"User ID\"\njohn@test.com,25,JOHN,1\n";
        $this->assertEquals($expected, $content);

        // Verify reading preserves the order
        $rows = iterator_to_array($this->csv->map([
            'User ID' => 'id',
            'Full Name' => 'name',
            'Email Address' => 'email',
            'Age' => 'age'
        ])->read($this->testFile));

        $row = $rows[0];
        $keys = array_keys($row);
        
        // Check that the keys are in the same order as in the CSV
        $this->assertEquals('email', $keys[0]);
        $this->assertEquals('age', $keys[1]);
        $this->assertEquals('name', $keys[2]);
        $this->assertEquals('id', $keys[3]);
    }

    public function testStreamChunksMethod()
    {
        $this->csv->map([
            'Name' => 'name',
            'Email' => 'email'
        ]);

        ob_start();
        $this->csv->stream([
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ], ['Name', 'Email']);
        $output = ob_get_clean();

        $expected = "Name,Email\nJohn,john@example.com\nJane,jane@example.com\n";
        $this->assertEquals($expected, $output);
    }

    public function testMultipleStreamCalls()
    {
        $headers = ['Name', 'Email'];
        $this->csv->map([
            'Name' => 'name',
            'Email' => 'email'
        ]);

        // First call with headers
        ob_start();
        $this->csv->stream([
            ['name' => 'John', 'email' => 'john@example.com'],
        ], $headers);
        
        // Second call with headers (should ignore headers)
        $this->csv->stream([
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ], $headers);
        
        // Third call with headers (should ignore headers)
        $this->csv->stream([
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ], $headers);
        
        $output = ob_get_clean();

        // Headers should only appear once at the beginning
        $expected = "Name,Email\nJohn,john@example.com\nJane,jane@example.com\nBob,bob@example.com\n";
        $this->assertEquals($expected, $output);
        
        // Verify line count
        $lineCount = substr_count($output, "\n");
        $this->assertEquals(4, $lineCount); // 1 header + 3 data rows
    }

    public function testHeaderCachingForConsistentOrdering()
    {
        // Create a new instance to reset state
        $this->csv = new Csv();
        
        $headers = ['First', 'Last'];
        
        // First call with headers
        ob_start();
        $this->csv->stream([
            ['First' => 'John', 'Last' => 'Doe', 'Extra' => 'Value'],
        ], $headers);
        
        // Second call without headers but with different column order
        $this->csv->stream([
            ['Last' => 'Smith', 'First' => 'Jane', 'Extra' => 'Value2'],
        ]);
        
        $output = ob_get_clean();
        
        // Verify the second row maintains the same column order as defined by headers
        $lines = explode("\n", trim($output));
        $this->assertEquals('First,Last', $lines[0]);
        $this->assertEquals('John,Doe', $lines[1]);
        $this->assertEquals('Jane,Smith', $lines[2]); // Order should match headers, not input
    }

    public function testFileAppendModeForStreaming()
    {
        // Test that multiple stream calls correctly append data
        // This test specifically verifies the 'a' (append) mode for php://output
        
        // Create a fresh instance
        $csv = new Csv();
        
        // Set up column mappings
        $csv->map([
            'Name' => 'name',
            'Email' => 'email'
        ]);
        
        // Start output buffering
        ob_start();
        
        // First call - should write headers and first row
        $csv->stream([
            ['name' => 'John', 'email' => 'john@example.com'],
        ], ['Name', 'Email']);
        
        // Second call - should only write data, no headers
        $csv->stream([
            ['name' => 'Jane', 'email' => 'jane@example.com'],
        ]);
        
        // Get the complete output
        $output = ob_get_clean();
        
        // Expected output should have headers only once
        $expected = "Name,Email\nJohn,john@example.com\nJane,jane@example.com\n";
        
        // Verify output has the correct format
        $this->assertEquals($expected, $output);
        
        // Verify headers appear exactly once
        $headerCount = substr_count($output, "Name,Email");
        $this->assertEquals(1, $headerCount, "Headers should appear exactly once");
        
        // Verify we have the correct number of lines
        $lineCount = substr_count($output, "\n");
        $this->assertEquals(3, $lineCount, "Output should have 3 lines (header + 2 data rows)");
    }
}
