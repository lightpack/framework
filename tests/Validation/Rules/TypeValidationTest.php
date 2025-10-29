<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class TypeValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // ========================================
    // IntRule Tests
    // ========================================

    public function testIntegerValue(): void
    {
        $data = ['age' => 25];

        $this->validator
            ->field('age')
            ->int();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testIntegerStringValue(): void
    {
        $data = ['age' => '25'];

        $this->validator
            ->field('age')
            ->int();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNegativeInteger(): void
    {
        $data = ['temperature' => '-10'];

        $this->validator
            ->field('temperature')
            ->int();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testZeroInteger(): void
    {
        $data = ['count' => '0'];

        $this->validator
            ->field('count')
            ->int();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testFloatFailsIntValidation(): void
    {
        $data = ['value' => '25.5'];

        $this->validator
            ->field('value')
            ->int();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testNonNumericStringFailsIntValidation(): void
    {
        $data = ['value' => 'abc'];

        $this->validator
            ->field('value')
            ->int();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // FloatRule Tests
    // ========================================

    public function testFloatValue(): void
    {
        $data = ['price' => 25.99];

        $this->validator
            ->field('price')
            ->float();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testFloatStringValue(): void
    {
        $data = ['price' => '25.99'];

        $this->validator
            ->field('price')
            ->float();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNegativeFloat(): void
    {
        $data = ['balance' => '-100.50'];

        $this->validator
            ->field('balance')
            ->float();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testZeroFloat(): void
    {
        $data = ['value' => '0.0'];

        $this->validator
            ->field('value')
            ->float();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testIntegerFailsFloatValidation(): void
    {
        $data = ['value' => '25'];

        $this->validator
            ->field('value')
            ->float();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testNonNumericStringFailsFloatValidation(): void
    {
        $data = ['value' => 'abc'];

        $this->validator
            ->field('value')
            ->float();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // NumericRule Tests
    // ========================================

    public function testNumericInteger(): void
    {
        $data = ['value' => 25];

        $this->validator
            ->field('value')
            ->numeric();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNumericFloat(): void
    {
        $data = ['value' => 25.99];

        $this->validator
            ->field('value')
            ->numeric();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNumericStringInteger(): void
    {
        $data = ['value' => '25'];

        $this->validator
            ->field('value')
            ->numeric();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNumericStringFloat(): void
    {
        $data = ['value' => '25.99'];

        $this->validator
            ->field('value')
            ->numeric();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNumericNegative(): void
    {
        $data = ['value' => '-25.5'];

        $this->validator
            ->field('value')
            ->numeric();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNumericZero(): void
    {
        $data = ['value' => '0'];

        $this->validator
            ->field('value')
            ->numeric();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNonNumericStringFailsNumericValidation(): void
    {
        $data = ['value' => 'abc'];

        $this->validator
            ->field('value')
            ->numeric();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // StringRule Tests
    // ========================================

    public function testStringValue(): void
    {
        $data = ['name' => 'John Doe'];

        $this->validator
            ->field('name')
            ->string();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testEmptyString(): void
    {
        $data = ['name' => ''];

        $this->validator
            ->field('name')
            ->string();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Empty optional fields skip validation
        $this->assertTrue($result->passes());
    }

    public function testNumericStringIsStillString(): void
    {
        $data = ['value' => '123'];

        $this->validator
            ->field('value')
            ->string();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testIntegerFailsStringValidation(): void
    {
        $data = ['value' => 123];

        $this->validator
            ->field('value')
            ->string();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testArrayFailsStringValidation(): void
    {
        $data = ['value' => ['a', 'b']];

        $this->validator
            ->field('value')
            ->string();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // BoolRule Tests
    // ========================================

    public function testBooleanTrue(): void
    {
        $data = ['active' => true];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanFalse(): void
    {
        $data = ['active' => false];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanStringTrue(): void
    {
        $data = ['active' => 'true'];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanStringFalse(): void
    {
        $data = ['active' => 'false'];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanString1(): void
    {
        $data = ['active' => '1'];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanString0(): void
    {
        $data = ['active' => '0'];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanStringYes(): void
    {
        $data = ['active' => 'yes'];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanStringNo(): void
    {
        $data = ['active' => 'no'];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanStringOn(): void
    {
        $data = ['active' => 'on'];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanStringOff(): void
    {
        $data = ['active' => 'off'];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanInteger1(): void
    {
        $data = ['active' => 1];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBooleanInteger0(): void
    {
        $data = ['active' => 0];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInvalidBooleanString(): void
    {
        $data = ['active' => 'maybe'];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInvalidBooleanInteger(): void
    {
        $data = ['active' => 2];

        $this->validator
            ->field('active')
            ->bool();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // Mixed Type Scenarios
    // ========================================

    public function testFormInputNumericValidation(): void
    {
        // Simulating form submission where all inputs are strings
        $data = [
            'age' => '25',
            'price' => '99.99',
            'quantity' => '10'
        ];

        $this->validator
            ->field('age')
            ->int()
            ->field('price')
            ->float()
            ->field('quantity')
            ->numeric();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }
}
