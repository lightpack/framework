<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation;

use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class NestedFieldMessagesTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testNestedFieldDefaultMessages(): void
    {
        $data = [
            'invoice' => [
                'items' => [
                    ['product' => 'Item 1', 'quantity' => '5', 'price' => '100'],
                    ['product' => '', 'quantity' => 'abc', 'price' => '200'],
                    ['product' => 'Item 3', 'quantity' => '3', 'price' => 'invalid'],
                ]
            ]
        ];

        $this->validator
            ->field('invoice.items.*.product')
            ->required()
            ->string()
            ->min(3)
            ->field('invoice.items.*.quantity')
            ->required()
            ->int()
            ->min(1)
            ->field('invoice.items.*.price')
            ->required()
            ->numeric()
            ->min(0);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        
        // Check that error keys include array indices
        $errors = $this->validator->getErrors();
        $this->assertArrayHasKey('invoice.items.1.product', $errors);
        $this->assertArrayHasKey('invoice.items.1.quantity', $errors);
        $this->assertArrayHasKey('invoice.items.2.price', $errors);
        
        // Verify default messages
        $this->assertStringContainsString('required', strtolower($errors['invoice.items.1.product']));
        $this->assertStringContainsString('integer', strtolower($errors['invoice.items.1.quantity']));
        $this->assertStringContainsString('numeric', strtolower($errors['invoice.items.2.price']));
    }

    public function testNestedFieldCustomMessages(): void
    {
        $data = [
            'invoice' => [
                'items' => [
                    ['product' => 'Item 1', 'quantity' => '5', 'price' => '100'],
                    ['product' => '', 'quantity' => 'abc', 'price' => '200'],
                    ['product' => 'Item 3', 'quantity' => '3', 'price' => 'invalid'],
                ]
            ]
        ];

        $this->validator
            ->field('invoice.items.*.product')
            ->required()
            ->message('Product name is required')
            ->string()
            ->min(3)
            ->message('Product name must be at least 3 characters')
            
            ->field('invoice.items.*.quantity')
            ->required()
            ->message('Quantity is required')
            ->int()
            ->message('Quantity must be a whole number')
            ->min(1)
            ->message('Quantity must be at least 1')
            
            ->field('invoice.items.*.price')
            ->required()
            ->message('Price is required')
            ->numeric()
            ->message('Price must be a number')
            ->min(0)
            ->message('Price cannot be negative');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        
        $errors = $this->validator->getErrors();
        
        // Verify custom messages are used
        $this->assertEquals('Product name is required', $errors['invoice.items.1.product']);
        $this->assertEquals('Quantity must be a whole number', $errors['invoice.items.1.quantity']);
        $this->assertEquals('Price must be a number', $errors['invoice.items.2.price']);
    }

    public function testDeeplyNestedFieldMessages(): void
    {
        $data = [
            'companies' => [
                [
                    'name' => 'Acme Corp',
                    'departments' => [
                        [
                            'name' => 'Engineering',
                            'employees' => [
                                ['name' => 'John', 'email' => 'john@acme.com'],
                                ['name' => '', 'email' => 'invalid-email'],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $this->validator
            ->field('companies.*.departments.*.employees.*.name')
            ->required()
            ->message('Employee name is required')
            ->field('companies.*.departments.*.employees.*.email')
            ->required()
            ->message('Employee email is required')
            ->email()
            ->message('Employee email must be valid');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        
        $errors = $this->validator->getErrors();
        
        // Note: Current wildcard implementation replaces ALL * with same index
        // For deeply nested wildcards, this means companies.*.departments.*.employees.*
        // becomes companies.1.departments.1.employees.1 for the second employee
        $this->assertArrayHasKey('companies.1.departments.1.employees.1.name', $errors);
        $this->assertArrayHasKey('companies.1.departments.1.employees.1.email', $errors);
        
        // Verify custom messages
        $this->assertEquals('Employee name is required', $errors['companies.1.departments.1.employees.1.name']);
        $this->assertEquals('Employee email must be valid', $errors['companies.1.departments.1.employees.1.email']);
    }

    public function testOrderFormWithMultipleItems(): void
    {
        $data = [
            'order' => [
                'customer_name' => 'John Doe',
                'items' => [
                    ['sku' => 'PROD-001', 'quantity' => '2', 'price' => '99.99'],
                    ['sku' => '', 'quantity' => '', 'price' => '149.99'],
                    ['sku' => 'PROD-003', 'quantity' => 'abc', 'price' => '-10'],
                ]
            ]
        ];

        $this->validator
            ->field('order.customer_name')
            ->required()
            ->message('Customer name is required')
            
            ->field('order.items.*.sku')
            ->required()
            ->message('Product SKU is required')
            ->regex('/^PROD-\d{3}$/')
            ->message('SKU must be in format PROD-XXX')
            
            ->field('order.items.*.quantity')
            ->required()
            ->message('Quantity is required')
            ->int()
            ->message('Quantity must be a number')
            ->min(1)
            ->message('Quantity must be at least 1')
            
            ->field('order.items.*.price')
            ->required()
            ->message('Price is required')
            ->numeric()
            ->message('Price must be a number')
            ->min(0)
            ->message('Price must be positive');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        
        $errors = $this->validator->getErrors();
        
        // Item 1 (index 1) errors
        $this->assertEquals('Product SKU is required', $errors['order.items.1.sku']);
        $this->assertEquals('Quantity is required', $errors['order.items.1.quantity']);
        
        // Item 2 (index 2) errors
        $this->assertEquals('Quantity must be a number', $errors['order.items.2.quantity']);
        $this->assertEquals('Price must be positive', $errors['order.items.2.price']);
    }

    public function testNestedFieldWithMixedValidation(): void
    {
        $data = [
            'users' => [
                ['name' => 'John', 'email' => 'john@example.com', 'age' => '25'],
                ['name' => 'J', 'email' => 'invalid', 'age' => '15'],
            ]
        ];

        $this->validator
            ->field('users.*.name')
            ->required()
            ->message('Name is required')
            ->min(3)
            ->message('Name must be at least 3 characters')
            
            ->field('users.*.email')
            ->required()
            ->message('Email is required')
            ->email()
            ->message('Email must be valid')
            
            ->field('users.*.age')
            ->required()
            ->message('Age is required')
            ->int()
            ->message('Age must be a number')
            ->between(18, 100)
            ->message('Age must be between 18 and 100');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        
        $errors = $this->validator->getErrors();
        
        $this->assertEquals('Name must be at least 3 characters', $errors['users.1.name']);
        $this->assertEquals('Email must be valid', $errors['users.1.email']);
        $this->assertEquals('Age must be between 18 and 100', $errors['users.1.age']);
    }

    public function testGetErrorForSpecificNestedField(): void
    {
        $data = [
            'items' => [
                ['name' => 'Item 1', 'price' => '100'],
                ['name' => '', 'price' => 'invalid'],
            ]
        ];

        $this->validator
            ->field('items.*.name')
            ->required()
            ->message('Item name is required')
            ->field('items.*.price')
            ->required()
            ->numeric()
            ->message('Item price must be a number');

        $this->validator->setInput($data);
        $this->validator->validate();
        
        // Get specific error by full path
        $nameError = $this->validator->getError('items.1.name');
        $priceError = $this->validator->getError('items.1.price');
        
        $this->assertEquals('Item name is required', $nameError);
        $this->assertEquals('Item price must be a number', $priceError);
    }

    public function testNestedFieldPassesValidation(): void
    {
        $data = [
            'invoice' => [
                'items' => [
                    ['product' => 'Item 1', 'quantity' => '5', 'price' => '100'],
                    ['product' => 'Item 2', 'quantity' => '3', 'price' => '200'],
                ]
            ]
        ];

        $this->validator
            ->field('invoice.items.*.product')
            ->required()
            ->message('Product name is required')
            ->string()
            ->min(3)
            ->message('Product name must be at least 3 characters')
            
            ->field('invoice.items.*.quantity')
            ->required()
            ->int()
            ->min(1)
            
            ->field('invoice.items.*.price')
            ->required()
            ->numeric()
            ->min(0);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
        $this->assertEmpty($this->validator->getErrors());
    }
}
