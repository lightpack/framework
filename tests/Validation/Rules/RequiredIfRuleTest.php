<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class RequiredIfRuleTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testRequiredIfConditionMet(): void
    {
        $data = [
            'status' => 'rejected',
            'reason' => ''
        ];

        $this->validator
            ->field('reason')
            ->requiredIf('status', 'rejected');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('required when status is rejected', $this->validator->getError('reason'));
    }

    public function testRequiredIfConditionMetWithValue(): void
    {
        $data = [
            'status' => 'rejected',
            'reason' => 'Not suitable'
        ];

        $this->validator
            ->field('reason')
            ->requiredIf('status', 'rejected');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testRequiredIfConditionNotMet(): void
    {
        $data = [
            'status' => 'approved',
            'reason' => ''
        ];

        $this->validator
            ->field('reason')
            ->requiredIf('status', 'rejected');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testRequiredIfWithNestedFields(): void
    {
        $data = [
            'user' => [
                'type' => 'business'
            ],
            'company_name' => ''
        ];

        $this->validator
            ->field('company_name')
            ->requiredIf('user.type', 'business');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testRequiredIfWithNestedFieldsValid(): void
    {
        $data = [
            'user' => [
                'type' => 'business'
            ],
            'company_name' => 'Acme Corp'
        ];

        $this->validator
            ->field('company_name')
            ->requiredIf('user.type', 'business');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->passes());
    }

    public function testRequiredIfWithNumericValue(): void
    {
        $data = [
            'payment_method' => 1,
            'card_number' => ''
        ];

        $this->validator
            ->field('card_number')
            ->requiredIf('payment_method', 1);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testRequiredIfWithBooleanValue(): void
    {
        $data = [
            'has_discount' => true,
            'discount_code' => ''
        ];

        $this->validator
            ->field('discount_code')
            ->requiredIf('has_discount', true);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testRequiredIfWithFileUpload(): void
    {
        $data = [
            'document_type' => 'passport',
            'document' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
                'size' => 0
            ]
        ];

        $this->validator
            ->field('document')
            ->requiredIf('document_type', 'passport');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
    }

    public function testRequiredIfChainedWithOtherRules(): void
    {
        $data = [
            'status' => 'rejected',
            'reason' => 'Too short'
        ];

        $this->validator
            ->field('reason')
            ->requiredIf('status', 'rejected')
            ->min(20);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertStringContainsString('not be less than', $this->validator->getError('reason'));
    }

    public function testRequiredIfWithCustomMessage(): void
    {
        $data = [
            'status' => 'rejected',
            'reason' => ''
        ];

        $this->validator
            ->field('reason')
            ->requiredIf('status', 'rejected')
            ->message('Please provide a reason for rejection');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $this->assertEquals('Please provide a reason for rejection', $this->validator->getError('reason'));
    }

    public function testMultipleRequiredIfConditions(): void
    {
        $data = [
            'order_type' => 'delivery',
            'delivery_address' => '',
            'payment_method' => 'card',
            'card_number' => ''
        ];

        $this->validator
            ->field('delivery_address')
            ->requiredIf('order_type', 'delivery')
            ->field('card_number')
            ->requiredIf('payment_method', 'card');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        
        $this->assertTrue($result->fails());
        $errors = $this->validator->getErrors();
        $this->assertArrayHasKey('delivery_address', $errors);
        $this->assertArrayHasKey('card_number', $errors);
    }
}
