<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ComparisonValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // ========================================
    // InRule Tests
    // ========================================

    public function testInRuleWithValidValue(): void
    {
        $data = ['status' => 'active'];

        $this->validator
            ->field('status')
            ->in(['active', 'inactive', 'pending']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInRuleWithInvalidValue(): void
    {
        $data = ['status' => 'deleted'];

        $this->validator
            ->field('status')
            ->in(['active', 'inactive', 'pending']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testInRuleWithNumericValues(): void
    {
        $data = ['priority' => 2];

        $this->validator
            ->field('priority')
            ->in([1, 2, 3]);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInRuleStrictComparison(): void
    {
        $data = ['value' => '1'];

        $this->validator
            ->field('value')
            ->in([1, 2, 3]);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Strict comparison: '1' !== 1
        $this->assertTrue($result->fails());
    }

    public function testInRuleWithArrayValue(): void
    {
        $data = ['tags' => ['php', 'javascript']];

        $this->validator
            ->field('tags')
            ->in(['php', 'javascript', 'python', 'ruby']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testInRuleWithArrayValueContainingInvalid(): void
    {
        $data = ['tags' => ['php', 'invalid']];

        $this->validator
            ->field('tags')
            ->in(['php', 'javascript', 'python']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    // ========================================
    // NotInRule Tests
    // ========================================

    public function testNotInRuleWithValidValue(): void
    {
        $data = ['username' => 'john'];

        $this->validator
            ->field('username')
            ->notIn(['admin', 'root', 'system']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testNotInRuleWithInvalidValue(): void
    {
        $data = ['username' => 'admin'];

        $this->validator
            ->field('username')
            ->notIn(['admin', 'root', 'system']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testNotInRuleStrictComparison(): void
    {
        $data = ['value' => '1'];

        $this->validator
            ->field('value')
            ->notIn([1, 2, 3]);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Strict comparison: '1' !== 1
        $this->assertTrue($result->passes());
    }

    // ========================================
    // SameRule Tests
    // ========================================

    public function testSameRuleWithMatchingValues(): void
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'secret123'
        ];

        $this->validator
            ->field('password_confirmation')
            ->same('password');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testSameRuleWithDifferentValues(): void
    {
        $data = [
            'password' => 'secret123',
            'password_confirmation' => 'different'
        ];

        $this->validator
            ->field('password_confirmation')
            ->same('password');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testSameRuleStrictComparison(): void
    {
        $data = [
            'value1' => '1',
            'value2' => 1
        ];

        $this->validator
            ->field('value2')
            ->same('value1');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Strict comparison: '1' !== 1
        $this->assertTrue($result->fails());
    }

    public function testSameRuleWithNestedFields(): void
    {
        $data = [
            'user' => [
                'email' => 'test@example.com'
            ],
            'email_confirmation' => 'test@example.com'
        ];

        $this->validator
            ->field('email_confirmation')
            ->same('user.email');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // DifferentRule Tests
    // ========================================

    public function testDifferentRuleWithDifferentValues(): void
    {
        $data = [
            'old_password' => 'old123',
            'new_password' => 'new456'
        ];

        $this->validator
            ->field('new_password')
            ->different('old_password');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testDifferentRuleWithSameValues(): void
    {
        $data = [
            'old_password' => 'secret123',
            'new_password' => 'secret123'
        ];

        $this->validator
            ->field('new_password')
            ->different('old_password');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testDifferentRuleStrictComparison(): void
    {
        $data = [
            'value1' => '1',
            'value2' => 1
        ];

        $this->validator
            ->field('value2')
            ->different('value1');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Strict comparison: '1' !== 1
        $this->assertTrue($result->passes());
    }

    // ========================================
    // BetweenRule Tests
    // ========================================

    public function testBetweenRuleWithNumericValue(): void
    {
        $data = ['age' => '25'];

        $this->validator
            ->field('age')
            ->between(18, 65);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBetweenRuleWithValueBelowMin(): void
    {
        $data = ['age' => '15'];

        $this->validator
            ->field('age')
            ->between(18, 65);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testBetweenRuleWithValueAboveMax(): void
    {
        $data = ['age' => '70'];

        $this->validator
            ->field('age')
            ->between(18, 65);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testBetweenRuleWithFloatValue(): void
    {
        $data = ['price' => '99.99'];

        $this->validator
            ->field('price')
            ->between(50, 200);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBetweenRuleWithStringLength(): void
    {
        $data = ['username' => 'johndoe'];

        $this->validator
            ->field('username')
            ->between(5, 20);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBetweenRuleWithStringLengthTooShort(): void
    {
        $data = ['username' => 'joe'];

        $this->validator
            ->field('username')
            ->between(5, 20);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testBetweenRuleWithStringLengthTooLong(): void
    {
        $data = ['username' => 'verylongusernamethatexceedslimit'];

        $this->validator
            ->field('username')
            ->between(5, 20);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testBetweenRuleAtMinBoundary(): void
    {
        $data = ['value' => '18'];

        $this->validator
            ->field('value')
            ->between(18, 65);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testBetweenRuleAtMaxBoundary(): void
    {
        $data = ['value' => '65'];

        $this->validator
            ->field('value')
            ->between(18, 65);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    // ========================================
    // LengthRule Tests
    // ========================================

    public function testLengthRuleExactMatch(): void
    {
        $data = ['code' => 'ABC123'];

        $this->validator
            ->field('code')
            ->length(6);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testLengthRuleTooShort(): void
    {
        $data = ['code' => 'ABC'];

        $this->validator
            ->field('code')
            ->length(6);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testLengthRuleTooLong(): void
    {
        $data = ['code' => 'ABC123456'];

        $this->validator
            ->field('code')
            ->length(6);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testLengthRuleWithMultibyte(): void
    {
        $data = ['text' => '你好世界'];

        $this->validator
            ->field('text')
            ->length(4);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testLengthRuleWithNull(): void
    {
        $data = ['code' => null];

        $this->validator
            ->field('code')
            ->length(6);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Optional fields skip validation when empty
        $this->assertTrue($result->passes());
    }

    // ========================================
    // ArrayRule Tests
    // ========================================

    public function testArrayRuleBasic(): void
    {
        $data = ['tags' => ['php', 'javascript']];

        $this->validator
            ->field('tags')
            ->array();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testArrayRuleWithNonArray(): void
    {
        $data = ['tags' => 'php'];

        $this->validator
            ->field('tags')
            ->array();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testArrayRuleWithMin(): void
    {
        $data = ['tags' => ['php', 'javascript', 'python']];

        $this->validator
            ->field('tags')
            ->array(2);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testArrayRuleWithMinFails(): void
    {
        $data = ['tags' => ['php']];

        $this->validator
            ->field('tags')
            ->array(2);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testArrayRuleWithMax(): void
    {
        $data = ['tags' => ['php', 'javascript']];

        $this->validator
            ->field('tags')
            ->array(null, 5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testArrayRuleWithMaxFails(): void
    {
        $data = ['tags' => ['php', 'javascript', 'python', 'ruby', 'go', 'rust']];

        $this->validator
            ->field('tags')
            ->array(null, 5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testArrayRuleWithMinAndMax(): void
    {
        $data = ['tags' => ['php', 'javascript', 'python']];

        $this->validator
            ->field('tags')
            ->array(2, 5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testArrayRuleWithMinAndMaxFailsMin(): void
    {
        $data = ['tags' => ['php']];

        $this->validator
            ->field('tags')
            ->array(2, 5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testArrayRuleWithMinAndMaxFailsMax(): void
    {
        $data = ['tags' => ['php', 'javascript', 'python', 'ruby', 'go', 'rust']];

        $this->validator
            ->field('tags')
            ->array(2, 5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testArrayRuleWithEmptyArray(): void
    {
        $data = ['tags' => []];

        $this->validator
            ->field('tags')
            ->array();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Optional fields skip validation when empty
        $this->assertTrue($result->passes());
    }

    public function testArrayRuleWithEmptyArrayAndMin(): void
    {
        $data = ['tags' => []];

        $this->validator
            ->field('tags')
            ->array(1);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        // Optional fields skip validation when empty
        $this->assertTrue($result->passes());
    }

    public function testArrayRuleWithEmptyArrayRequiredAndMin(): void
    {
        $data = ['tags' => []];

        $this->validator
            ->field('tags')
            ->required()
            ->array(1);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }
}
