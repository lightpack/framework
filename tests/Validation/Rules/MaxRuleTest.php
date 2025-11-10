<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class MaxRuleTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // ========================================
    // Numeric Value Tests (Form Input Scenario)
    // ========================================

    public function testNumericStringBelowMaximum(): void
    {
        $data = ['price' => '50'];

        $this->validator
            ->field('price')
            ->max(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNumericStringAboveMaximum(): void
    {
        $data = ['price' => '150'];

        $this->validator
            ->field('price')
            ->max(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be greater than 100', $this->validator->getError('price'));
    }

    public function testNumericStringExactlyAtMaximum(): void
    {
        $data = ['price' => '100'];

        $this->validator
            ->field('price')
            ->max(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testFloatStringBelowMaximum(): void
    {
        $data = ['price' => '49.99'];

        $this->validator
            ->field('price')
            ->max(50);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testFloatStringAboveMaximum(): void
    {
        $data = ['price' => '50.01'];

        $this->validator
            ->field('price')
            ->max(50);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testIntegerBelowMaximum(): void
    {
        $data = ['quantity' => 50];

        $this->validator
            ->field('quantity')
            ->max(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testIntegerAboveMaximum(): void
    {
        $data = ['quantity' => 150];

        $this->validator
            ->field('quantity')
            ->max(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testNegativeNumbers(): void
    {
        $data = ['temperature' => '-5'];

        $this->validator
            ->field('temperature')
            ->max(-3);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // -5 < -3, so it passes max(-3) validation
        $this->assertTrue($result->passes());
    }

    public function testNegativeNumbersAboveMax(): void
    {
        $data = ['temperature' => '-2'];

        $this->validator
            ->field('temperature')
            ->max(-3);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // -2 > -3, so it fails max(-3) validation
        $this->assertTrue($result->fails());
    }

    public function testZeroValue(): void
    {
        $data = ['value' => '0'];

        $this->validator
            ->field('value')
            ->max(0);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // String Length Tests
    // ========================================

    public function testStringLengthBelowMaximum(): void
    {
        $data = ['username' => 'joe'];

        $this->validator
            ->field('username')
            ->max(10);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testStringLengthAboveMaximum(): void
    {
        $data = ['username' => 'verylongusername'];

        $this->validator
            ->field('username')
            ->max(10);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testStringLengthExactlyAtMaximum(): void
    {
        $data = ['code' => 'ABCDE'];

        $this->validator
            ->field('code')
            ->max(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testMultibyteStringLength(): void
    {
        $data = ['message' => '你好世界'];

        $this->validator
            ->field('message')
            ->max(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // Array Count Tests
    // ========================================

    public function testArrayCountBelowMaximum(): void
    {
        $data = ['tags' => ['php']];

        $this->validator
            ->field('tags')
            ->max(3);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testArrayCountAboveMaximum(): void
    {
        $data = ['tags' => ['php', 'javascript', 'python', 'ruby']];

        $this->validator
            ->field('tags')
            ->max(3);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testArrayCountExactlyAtMaximum(): void
    {
        $data = ['items' => ['a', 'b', 'c']];

        $this->validator
            ->field('items')
            ->max(3);

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
            ->max(5);

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
            ->max(5);

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
            ->max(10);

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
            ->max(10);

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
            ->max(10);

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
            ->max(10);

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
            ->max(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // Chained Validation Tests
    // ========================================

    public function testMaxChainedWithRequired(): void
    {
        $data = ['price' => ''];

        $this->validator
            ->field('price')
            ->required()
            ->max(1000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('required', $this->validator->getError('price'));
    }

    public function testMaxChainedWithMin(): void
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

    public function testMaxFailsWhenAboveMaximumInChain(): void
    {
        $data = ['price' => '1500'];

        $this->validator
            ->field('price')
            ->required()
            ->min(100)
            ->max(1000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be greater than 1000', $this->validator->getError('price'));
    }

    // ========================================
    // Custom Message Tests
    // ========================================

    public function testMaxWithCustomMessage(): void
    {
        $data = ['price' => '1500'];

        $this->validator
            ->field('price')
            ->max(1000)
            ->message('Price cannot exceed $1000');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertEquals('Price cannot exceed $1000', $this->validator->getError('price'));
    }

    // ========================================
    // Real-world Scenarios
    // ========================================

    public function testProductPriceValidation(): void
    {
        // Simulating a product form submission
        $data = [
            'name' => 'Test Product',
            'price' => '500.00',
            'stock' => '10'
        ];

        $this->validator
            ->field('price')
            ->required()
            ->min(1)
            ->max(10000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testProductPriceValidationFails(): void
    {
        // Simulating a product form submission with price above maximum
        $data = [
            'name' => 'Test Product',
            'price' => '15000.00',
            'stock' => '10'
        ];

        $this->validator
            ->field('price')
            ->required()
            ->min(1)
            ->max(10000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be greater than 10000', $this->validator->getError('price'));
    }

    public function testAgeValidation(): void
    {
        $data = ['age' => '65'];

        $this->validator
            ->field('age')
            ->required()
            ->max(120);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testMaximumQuantityLimit(): void
    {
        $data = ['quantity' => '150'];

        $this->validator
            ->field('quantity')
            ->required()
            ->max(100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // Context-Aware Validation Tests
    // ========================================

    public function testPasswordWithStringTypeValidatesLength(): void
    {
        // Password "passwordtoolong" should FAIL when using string() + max(10)
        // because string length is 15, not because it's a string
        $data = ['password' => 'passwordtoolong'];

        $this->validator
            ->field('password')
            ->required()
            ->string()      // Force string length validation
            ->max(10);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be greater than 10', $this->validator->getError('password'));
    }

    public function testPasswordWithStringTypePassesWhenLengthValid(): void
    {
        $data = ['password' => 'pass123'];  // 7 chars

        $this->validator
            ->field('password')
            ->required()
            ->string()
            ->max(10);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testPriceWithNumericTypeValidatesValue(): void
    {
        // Price "15000" should FAIL with numeric() + max(10000)
        // because numeric value 15000 > 10000
        $data = ['price' => '15000'];

        $this->validator
            ->field('price')
            ->required()
            ->numeric()     // Force numeric value validation
            ->max(10000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be greater than 10000', $this->validator->getError('price'));
    }

    public function testPriceWithNumericTypePassesWhenValueValid(): void
    {
        $data = ['price' => '9999.99'];

        $this->validator
            ->field('price')
            ->required()
            ->numeric()
            ->max(10000);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testIntTypeWithMaxValidatesNumericValue(): void
    {
        $data = ['age' => '150'];

        $this->validator
            ->field('age')
            ->required()
            ->int()
            ->max(120);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testFloatTypeWithMaxValidatesNumericValue(): void
    {
        $data = ['rating' => '5.5'];

        $this->validator
            ->field('rating')
            ->required()
            ->float()
            ->max(5);  // max() expects int

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
        $data = ['value' => '5'];

        $this->validator
            ->field('value')
            ->max(10);  // No type declared

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Passes because 5 <= 10 (numeric validation)
        $this->assertTrue($result->passes());
    }

    public function testNonNumericStringWithoutTypeUsesStringLength(): void
    {
        // Without type, non-numeric strings use length validation
        $data = ['username' => 'verylongusername'];

        $this->validator
            ->field('username')
            ->max(10);  // No type declared

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Fails because string length 16 > 10
        $this->assertTrue($result->fails());
    }
}
