<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class WildcardValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // ========================================
    // Basic Wildcard Tests
    // ========================================

    public function testBasicWildcardValidation(): void
    {
        $data = [
            'emails' => [
                'john@example.com',
                'jane@example.com',
                'invalid-email'
            ]
        ];

        $this->validator
            ->field('emails.*')
            ->required()
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testBasicWildcardValidationPasses(): void
    {
        $data = [
            'emails' => [
                'john@example.com',
                'jane@example.com',
                'bob@example.com'
            ]
        ];

        $this->validator
            ->field('emails.*')
            ->required()
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // Nested Wildcard Tests
    // ========================================

    public function testNestedWildcardValidation(): void
    {
        $data = [
            'users' => [
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'email' => 'jane@example.com'],
                ['name' => 'Bob', 'email' => 'invalid']
            ]
        ];

        $this->validator
            ->field('users.*.name')
            ->required()
            ->string()
            ->min(2)
            ->field('users.*.email')
            ->required()
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testNestedWildcardValidationPasses(): void
    {
        $data = [
            'users' => [
                ['name' => 'John', 'email' => 'john@example.com'],
                ['name' => 'Jane', 'email' => 'jane@example.com'],
                ['name' => 'Bob', 'email' => 'bob@example.com']
            ]
        ];

        $this->validator
            ->field('users.*.name')
            ->required()
            ->string()
            ->min(2)
            ->field('users.*.email')
            ->required()
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // Deep Nested Wildcard Tests
    // ========================================

    public function testDeeplyNestedWildcardValidation(): void
    {
        $data = [
            'companies' => [
                [
                    'name' => 'Acme Corp',
                    'employees' => [
                        ['name' => 'John', 'email' => 'john@acme.com'],
                        ['name' => 'Jane', 'email' => 'jane@acme.com']
                    ]
                ],
                [
                    'name' => 'Tech Inc',
                    'employees' => [
                        ['name' => 'Bob', 'email' => 'invalid'],
                        ['name' => 'Alice', 'email' => 'alice@tech.com']
                    ]
                ]
            ]
        ];

        $this->validator
            ->field('companies.*.name')
            ->required()
            ->string()
            ->field('companies.*.employees.*.name')
            ->required()
            ->string()
            ->field('companies.*.employees.*.email')
            ->required()
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testDeeplyNestedWildcardValidationPasses(): void
    {
        $data = [
            'companies' => [
                [
                    'name' => 'Acme Corp',
                    'employees' => [
                        ['name' => 'John', 'email' => 'john@acme.com'],
                        ['name' => 'Jane', 'email' => 'jane@acme.com']
                    ]
                ],
                [
                    'name' => 'Tech Inc',
                    'employees' => [
                        ['name' => 'Bob', 'email' => 'bob@tech.com'],
                        ['name' => 'Alice', 'email' => 'alice@tech.com']
                    ]
                ]
            ]
        ];

        $this->validator
            ->field('companies.*.name')
            ->required()
            ->string()
            ->field('companies.*.employees.*.name')
            ->required()
            ->string()
            ->field('companies.*.employees.*.email')
            ->required()
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // Wildcard with Type Validation
    // ========================================

    public function testWildcardWithNumericValidation(): void
    {
        $data = [
            'prices' => ['100', '200', 'invalid', '400']
        ];

        $this->validator
            ->field('prices.*')
            ->required()
            ->numeric()
            ->min(50);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testWildcardWithIntValidation(): void
    {
        $data = [
            'quantities' => ['10', '20', '30']
        ];

        $this->validator
            ->field('quantities.*')
            ->required()
            ->int()
            ->min(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // Wildcard with Array Validation
    // ========================================

    public function testWildcardWithArrayValidation(): void
    {
        $data = [
            'users' => [
                ['name' => 'John', 'roles' => ['admin', 'user']],
                ['name' => 'Jane', 'roles' => ['user']],
                ['name' => 'Bob', 'roles' => []]
            ]
        ];

        $this->validator
            ->field('users.*.roles')
            ->required()
            ->array(1);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // Wildcard with In/NotIn Validation
    // ========================================

    public function testWildcardWithInValidation(): void
    {
        $data = [
            'statuses' => ['active', 'pending', 'invalid', 'active']
        ];

        $this->validator
            ->field('statuses.*')
            ->required()
            ->in(['active', 'pending', 'inactive']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testWildcardWithInValidationPasses(): void
    {
        $data = [
            'statuses' => ['active', 'pending', 'inactive', 'active']
        ];

        $this->validator
            ->field('statuses.*')
            ->required()
            ->in(['active', 'pending', 'inactive']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // Empty Array Handling
    // ========================================

    public function testWildcardWithEmptyArray(): void
    {
        $data = [
            'emails' => []
        ];

        $this->validator
            ->field('emails.*')
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('must be an array', $this->validator->getError('emails.*'));
    }

    public function testWildcardWithRequiredArray(): void
    {
        $data = [
            'emails' => []
        ];

        $this->validator
            ->field('emails')
            ->required()
            ->array(1)
            ->field('emails.*')
            ->required()
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Array itself must have at least 1 item
        $this->assertTrue($result->fails());
    }

    // ========================================
    // Non-Array Value Handling
    // ========================================

    public function testWildcardWithNonArrayValue(): void
    {
        $data = [
            'emails' => 'not-an-array'
        ];

        $this->validator
            ->field('emails.*')
            ->required()
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('must be an array', $this->validator->getError('emails.*'));
    }

    // ========================================
    // Mixed Validation Scenarios
    // ========================================

    public function testWildcardWithMixedRules(): void
    {
        $data = [
            'products' => [
                ['name' => 'Product 1', 'price' => '100', 'stock' => '50'],
                ['name' => 'P', 'price' => '50', 'stock' => 'invalid'],
                ['name' => 'Product 3', 'price' => '200', 'stock' => '30']
            ]
        ];

        $this->validator
            ->field('products.*.name')
            ->required()
            ->string()
            ->min(3)
            ->field('products.*.price')
            ->required()
            ->numeric()
            ->min(100)
            ->field('products.*.stock')
            ->required()
            ->int()
            ->min(1);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testWildcardWithCustomValidation(): void
    {
        $data = [
            'codes' => ['CODE-001', 'CODE-002', 'INVALID', 'CODE-003']
        ];

        $this->validator
            ->field('codes.*')
            ->required()
            ->custom(function($value) {
                return (bool) preg_match('/^CODE-\\d{3}$/', $value);
            }, 'Invalid code format');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // Real-World Scenarios
    // ========================================

    public function testOrderItemsValidation(): void
    {
        $data = [
            'order' => [
                'items' => [
                    ['product_id' => '1', 'quantity' => '2', 'price' => '99.99'],
                    ['product_id' => '2', 'quantity' => '1', 'price' => '149.99'],
                    ['product_id' => '3', 'quantity' => '0', 'price' => '29.99']
                ]
            ]
        ];

        $this->validator
            ->field('order.items.*.product_id')
            ->required()
            ->int()
            ->field('order.items.*.quantity')
            ->required()
            ->int()
            ->min(1)
            ->field('order.items.*.price')
            ->required()
            ->numeric()
            ->min(0);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testContactFormWithMultiplePhones(): void
    {
        $data = [
            'contact' => [
                'phones' => [
                    ['type' => 'mobile', 'number' => '1234567890'],
                    ['type' => 'home', 'number' => '0987654321'],
                    ['type' => 'invalid', 'number' => '123']
                ]
            ]
        ];

        $this->validator
            ->field('contact.phones.*.type')
            ->required()
            ->in(['mobile', 'home', 'work'])
            ->field('contact.phones.*.number')
            ->required()
            ->numeric()
            ->length(10);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }
}
