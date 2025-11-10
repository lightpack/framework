<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class MinRuleTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // ========================================
    // Numeric Value Tests (Form Input Scenario)
    // ========================================

    public function testNumericStringAboveMinimum(): void
    {
        $data = ['price' => '150'];

        $this->validator
            ->field('price')
            ->min(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNumericStringBelowMinimum(): void
    {
        $data = ['price' => '47'];

        $this->validator
            ->field('price')
            ->min(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be less than 100', $this->validator->getError('price'));
    }

    public function testNumericStringExactlyAtMinimum(): void
    {
        $data = ['price' => '100'];

        $this->validator
            ->field('price')
            ->min(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testFloatStringAboveMinimum(): void
    {
        $data = ['price' => '99.99'];

        $this->validator
            ->field('price')
            ->min(50);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testFloatStringBelowMinimum(): void
    {
        $data = ['price' => '49.99'];

        $this->validator
            ->field('price')
            ->min(50);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testIntegerAboveMinimum(): void
    {
        $data = ['quantity' => 150];

        $this->validator
            ->field('quantity')
            ->min(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testIntegerBelowMinimum(): void
    {
        $data = ['quantity' => 47];

        $this->validator
            ->field('quantity')
            ->min(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testNegativeNumbers(): void
    {
        $data = ['temperature' => '-5'];

        $this->validator
            ->field('temperature')
            ->min(-10);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testZeroValue(): void
    {
        $data = ['value' => '0'];

        $this->validator
            ->field('value')
            ->min(0);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // String Length Tests
    // ========================================

    public function testStringLengthAboveMinimum(): void
    {
        $data = ['username' => 'johndoe'];

        $this->validator
            ->field('username')
            ->min(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testStringLengthBelowMinimum(): void
    {
        $data = ['username' => 'joe'];

        $this->validator
            ->field('username')
            ->min(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testStringLengthExactlyAtMinimum(): void
    {
        $data = ['code' => 'ABCDE'];

        $this->validator
            ->field('code')
            ->min(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testMultibyteStringLength(): void
    {
        $data = ['message' => '你好世界'];

        $this->validator
            ->field('message')
            ->min(3);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // Array Count Tests
    // ========================================

    public function testArrayCountAboveMinimum(): void
    {
        $data = ['tags' => ['php', 'javascript', 'python']];

        $this->validator
            ->field('tags')
            ->min(2);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testArrayCountBelowMinimum(): void
    {
        $data = ['tags' => ['php']];

        $this->validator
            ->field('tags')
            ->min(2);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testArrayCountExactlyAtMinimum(): void
    {
        $data = ['items' => ['a', 'b', 'c']];

        $this->validator
            ->field('items')
            ->min(3);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testEmptyArrayWithRequired(): void
    {
        $data = ['tags' => []];

        $this->validator
            ->field('tags')
            ->required()
            ->min(1);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('required', $this->validator->getError('tags'));
    }

    public function testEmptyArrayWithoutRequired(): void
    {
        $data = ['tags' => []];

        $this->validator
            ->field('tags')
            ->min(1);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Optional fields pass validation when empty
        $this->assertTrue($result->passes());
    }

    // ========================================
    // Edge Cases
    // ========================================

    public function testEmptyStringWithRequired(): void
    {
        $data = ['field' => ''];

        $this->validator
            ->field('field')
            ->required()
            ->min(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('required', $this->validator->getError('field'));
    }

    public function testEmptyStringWithoutRequired(): void
    {
        $data = ['field' => ''];

        $this->validator
            ->field('field')
            ->min(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Optional fields pass validation when empty
        $this->assertTrue($result->passes());
    }

    public function testNullValueWithRequired(): void
    {
        $data = ['field' => null];

        $this->validator
            ->field('field')
            ->required()
            ->min(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('required', $this->validator->getError('field'));
    }

    public function testNullValueWithoutRequired(): void
    {
        $data = ['field' => null];

        $this->validator
            ->field('field')
            ->min(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Optional fields pass validation when empty
        $this->assertTrue($result->passes());
    }

    public function testWhitespaceString(): void
    {
        $data = ['field' => '   '];

        $this->validator
            ->field('field')
            ->min(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // Chained Validation Tests
    // ========================================

    public function testMinChainedWithRequired(): void
    {
        $data = ['price' => ''];

        $this->validator
            ->field('price')
            ->required()
            ->min(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('required', $this->validator->getError('price'));
    }

    public function testMinChainedWithMax(): void
    {
        $data = ['price' => '150'];

        $this->validator
            ->field('price')
            ->min(100)
            ->max(1000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testMinFailsWhenBelowMinimumInChain(): void
    {
        $data = ['price' => '47'];

        $this->validator
            ->field('price')
            ->required()
            ->min(100)
            ->max(1000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be less than 100', $this->validator->getError('price'));
    }

    // ========================================
    // Custom Message Tests
    // ========================================

    public function testMinWithCustomMessage(): void
    {
        $data = ['price' => '47'];

        $this->validator
            ->field('price')
            ->min(100)
            ->message('Price must be at least $100');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertEquals('Price must be at least $100', $this->validator->getError('price'));
    }

    // ========================================
    // Real-world Scenarios
    // ========================================

    public function testProductPriceValidation(): void
    {
        // Simulating a product form submission
        $data = [
            'name' => 'Test Product',
            'price' => '150.00',
            'stock' => '10'
        ];

        $this->validator
            ->field('price')
            ->required()
            ->min(100)
            ->max(10000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testProductPriceValidationFails(): void
    {
        // Simulating a product form submission with price below minimum
        $data = [
            'name' => 'Test Product',
            'price' => '47.00',
            'stock' => '10'
        ];

        $this->validator
            ->field('price')
            ->required()
            ->min(100)
            ->max(10000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be less than 100', $this->validator->getError('price'));
    }

    public function testAgeValidation(): void
    {
        $data = ['age' => '25'];

        $this->validator
            ->field('age')
            ->required()
            ->min(18);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testMinimumOrderQuantity(): void
    {
        $data = ['quantity' => '5'];

        $this->validator
            ->field('quantity')
            ->required()
            ->min(10);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // Context-Aware Validation Tests
    // ========================================

    public function testPasswordWithStringTypeValidatesLength(): void
    {
        // Critical: Password "123" should FAIL when using string() + min(8)
        // because string length is 3, not because 123 >= 8
        $data = ['password' => '123'];

        $this->validator
            ->field('password')
            ->required()
            ->string()      // Force string length validation
            ->min(8);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be less than 8', $this->validator->getError('password'));
    }

    public function testPasswordWithStringTypePassesWhenLengthValid(): void
    {
        $data = ['password' => 'password123'];  // 11 chars

        $this->validator
            ->field('password')
            ->required()
            ->string()
            ->min(8);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testPriceWithNumericTypeValidatesValue(): void
    {
        // Critical: Price "500" should FAIL with numeric() + min(1000)
        // because numeric value 500 < 1000
        $data = ['price' => '500'];

        $this->validator
            ->field('price')
            ->required()
            ->numeric()     // Force numeric value validation
            ->min(1000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be less than 1000', $this->validator->getError('price'));
    }

    public function testPriceWithNumericTypePassesWhenValueValid(): void
    {
        $data = ['price' => '1500.50'];

        $this->validator
            ->field('price')
            ->required()
            ->numeric()
            ->min(1000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testIntTypeWithMinValidatesNumericValue(): void
    {
        $data = ['age' => '15'];

        $this->validator
            ->field('age')
            ->required()
            ->int()
            ->min(18);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testFloatTypeWithMinValidatesNumericValue(): void
    {
        $data = ['rating' => '3.5'];

        $this->validator
            ->field('rating')
            ->required()
            ->float()
            ->min(4);  // min() expects int

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // Backward Compatibility Tests
    // ========================================

    public function testNumericStringWithoutTypeUsesNumericValidation(): void
    {
        // Backward compatibility: Without type declaration,
        // numeric strings are validated as numbers
        $data = ['value' => '123'];

        $this->validator
            ->field('value')
            ->min(8);  // No type declared

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Passes because 123 >= 8 (numeric validation)
        $this->assertTrue($result->passes());
    }

    public function testNonNumericStringWithoutTypeUsesStringLength(): void
    {
        // Without type, non-numeric strings use length validation
        $data = ['username' => 'abc'];

        $this->validator
            ->field('username')
            ->min(5);  // No type declared

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Fails because string length 3 < 5
        $this->assertTrue($result->fails());
    }
}
