<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation\Rules;

use Lightpack\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ConditionalRequiredRulesTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // RequiredWith Tests
    public function testRequiredWithPassesWhenOtherFieldNotPresent(): void
    {
        $this->validator->field('phone')->requiredWith('country_code');

        $this->validator->setInput(['phone' => '']);
        $this->assertTrue($this->validator->validate()->passes());
    }

    public function testRequiredWithFailsWhenOtherFieldPresentButValueEmpty(): void
    {
        $this->validator->field('phone')->requiredWith('country_code');

        $this->validator->setInput([
            'country_code' => '+1',
            'phone' => ''
        ]);
        $this->assertTrue($this->validator->validate()->fails());
        $this->assertStringContainsString('required when', $this->validator->getError('phone'));
    }

    public function testRequiredWithPassesWhenBothPresent(): void
    {
        $this->validator->field('phone')->requiredWith('country_code');

        $this->validator->setInput([
            'country_code' => '+1',
            'phone' => '1234567890'
        ]);
        $this->assertTrue($this->validator->validate()->passes());
    }

    public function testRequiredWithMultipleFields(): void
    {
        // No fields present - passes
        $validator = new Validator();
        $validator->field('address')->requiredWith(['city', 'state']);
        $validator->setInput(['address' => '']);
        $this->assertTrue($validator->validate()->passes());

        // One field present - fails
        $validator = new Validator();
        $validator->field('address')->requiredWith(['city', 'state']);
        $validator->setInput([
            'city' => 'New York',
            'address' => ''
        ]);
        $this->assertTrue($validator->validate()->fails());

        // Both present - passes
        $validator = new Validator();
        $validator->field('address')->requiredWith(['city', 'state']);
        $validator->setInput([
            'city' => 'New York',
            'state' => 'NY',
            'address' => '123 Main St'
        ]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testRequiredWithZeroValue(): void
    {
        $this->validator->field('discount')->requiredWith('promo_code');

        $this->validator->setInput([
            'promo_code' => 'SAVE10',
            'discount' => 0
        ]);
        $this->assertTrue($this->validator->validate()->passes());
    }

    // RequiredWithout Tests
    public function testRequiredWithoutPassesWhenOtherFieldPresent(): void
    {
        $this->validator->field('billing_address')->requiredWithout('same_as_shipping');

        $this->validator->setInput([
            'same_as_shipping' => true,
            'billing_address' => ''
        ]);
        $this->assertTrue($this->validator->validate()->passes());
    }

    public function testRequiredWithoutFailsWhenOtherFieldNotPresentAndValueEmpty(): void
    {
        $this->validator->field('billing_address')->requiredWithout('same_as_shipping');

        $this->validator->setInput(['billing_address' => '']);
        $this->assertTrue($this->validator->validate()->fails());
        $this->assertStringContainsString('required when', $this->validator->getError('billing_address'));
    }

    public function testRequiredWithoutPassesWhenOtherFieldNotPresentButValueProvided(): void
    {
        $this->validator->field('billing_address')->requiredWithout('same_as_shipping');

        $this->validator->setInput(['billing_address' => '123 Main St']);
        $this->assertTrue($this->validator->validate()->passes());
    }

    public function testRequiredWithoutMultipleFields(): void
    {
        // At least one field present - passes
        $validator = new Validator();
        $validator->field('email')->requiredWithout(['phone', 'fax']);
        $validator->setInput([
            'phone' => '1234567890',
            'email' => ''
        ]);
        $this->assertTrue($validator->validate()->passes());

        // No fields present - fails
        $validator = new Validator();
        $validator->field('email')->requiredWithout(['phone', 'fax']);
        $validator->setInput(['email' => '']);
        $this->assertTrue($validator->validate()->fails());

        // No fields present but email provided - passes
        $validator = new Validator();
        $validator->field('email')->requiredWithout(['phone', 'fax']);
        $validator->setInput(['email' => 'test@example.com']);
        $this->assertTrue($validator->validate()->passes());
    }

    // RequiredUnless Tests
    public function testRequiredUnlessPassesWhenConditionMet(): void
    {
        $this->validator->field('shipping_address')->requiredUnless('same_as_billing', true);

        $this->validator->setInput([
            'same_as_billing' => true,
            'shipping_address' => ''
        ]);
        $this->assertTrue($this->validator->validate()->passes());
    }

    public function testRequiredUnlessFailsWhenConditionNotMetAndValueEmpty(): void
    {
        $this->validator->field('shipping_address')->requiredUnless('same_as_billing', true);

        $this->validator->setInput([
            'same_as_billing' => false,
            'shipping_address' => ''
        ]);
        $this->assertTrue($this->validator->validate()->fails());
        $this->assertStringContainsString('required unless', $this->validator->getError('shipping_address'));
    }

    public function testRequiredUnlessPassesWhenConditionNotMetButValueProvided(): void
    {
        $this->validator->field('shipping_address')->requiredUnless('same_as_billing', true);

        $this->validator->setInput([
            'same_as_billing' => false,
            'shipping_address' => '456 Oak Ave'
        ]);
        $this->assertTrue($this->validator->validate()->passes());
    }

    public function testRequiredUnlessWithStringValue(): void
    {
        // Status is approved - not required
        $validator = new Validator();
        $validator->field('reason')->requiredUnless('status', 'approved');
        $validator->setInput([
            'status' => 'approved',
            'reason' => ''
        ]);
        $this->assertTrue($validator->validate()->passes());

        // Status is not approved - required
        $validator = new Validator();
        $validator->field('reason')->requiredUnless('status', 'approved');
        $validator->setInput([
            'status' => 'rejected',
            'reason' => ''
        ]);
        $this->assertTrue($validator->validate()->fails());
    }

    public function testRequiredUnlessWithNumericValue(): void
    {
        $validator = new Validator();
        $validator->field('notes')->requiredUnless('priority', 1);
        $validator->setInput([
            'priority' => 1,
            'notes' => ''
        ]);
        $this->assertTrue($validator->validate()->passes());

        $validator = new Validator();
        $validator->field('notes')->requiredUnless('priority', 1);
        $validator->setInput([
            'priority' => 2,
            'notes' => ''
        ]);
        $this->assertTrue($validator->validate()->fails());
    }

    // File Upload Tests
    public function testRequiredWithFileUpload(): void
    {
        $this->validator->field('document')->requiredWith('document_type');

        $this->validator->setInput([
            'document_type' => 'passport',
            'document' => [
                'name' => '',
                'type' => '',
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
                'size' => 0
            ]
        ]);
        $this->assertTrue($this->validator->validate()->fails());
    }

    // Chaining Tests
    public function testRequiredWithChainedWithOtherRules(): void
    {
        $this->validator
            ->field('phone')
            ->requiredWith('country_code')
            ->numeric()
            ->length(10);

        // Country code present, phone invalid
        $this->validator->setInput([
            'country_code' => '+1',
            'phone' => '123'
        ]);
        $this->assertTrue($this->validator->validate()->fails());

        // Country code present, phone valid
        $this->validator->setInput([
            'country_code' => '+1',
            'phone' => '1234567890'
        ]);
        $this->assertTrue($this->validator->validate()->passes());
    }

    public function testRequiredWithCustomMessage(): void
    {
        $this->validator
            ->field('phone')
            ->requiredWith('country_code')
            ->message('Phone number is required when country code is provided');

        $this->validator->setInput([
            'country_code' => '+1',
            'phone' => ''
        ]);
        $this->assertTrue($this->validator->validate()->fails());
        $this->assertEquals(
            'Phone number is required when country code is provided',
            $this->validator->getError('phone')
        );
    }

    // Complex Scenarios
    public function testMultipleConditionalRules(): void
    {
        $this->validator
            ->field('email')->requiredWithout('phone')
            ->field('phone')->requiredWithout('email')
            ->field('reason')->requiredUnless('status', 'approved');

        // No email or phone - both fail
        $this->validator->setInput([
            'status' => 'approved'
        ]);
        $this->assertTrue($this->validator->validate()->fails());
        $errors = $this->validator->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('phone', $errors);
    }

    public function testNestedFieldsWithRequiredWith(): void
    {
        $this->validator->field('company.tax_id')->requiredWith('company.name');

        $this->validator->setInput([
            'company' => [
                'name' => 'Acme Corp',
                'tax_id' => ''
            ]
        ]);
        $this->assertTrue($this->validator->validate()->fails());

        $this->validator->setInput([
            'company' => [
                'name' => 'Acme Corp',
                'tax_id' => '12-3456789'
            ]
        ]);
        $this->assertTrue($this->validator->validate()->passes());
    }
}
