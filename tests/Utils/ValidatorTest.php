<?php

declare(strict_types=1);

namespace Lightpack\Tests\Utils;

use Lightpack\Utils\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testBasicValidation(): void
    {
        $data = ['name' => 'John'];

        $result = $this->validator
            ->field('name')
            ->required()
            ->min(2)
            ->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testFailedValidation(): void
    {
        $data = ['name' => 'J'];

        $result = $this->validator
            ->field('name')
            ->required()
            ->min(2)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('name', $result->getErrors());
    }

    public function testEmailValidation(): void
    {
        $data = ['email' => 'invalid'];

        $result = $this->validator
            ->field('email')
            ->required()
            ->email()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('email', $result->getErrors());
    }

    public function testCustomValidation(): void
    {
        $data = ['age' => 15];

        $result = $this->validator
            ->field('age')
            ->required()
            ->custom(fn($value) => $value >= 18, 'Must be 18 or older')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertEquals('Must be 18 or older', $result->getErrors()['age']);
    }

    public function testWildcardValidation(): void
    {
        // Test 1: Basic array validation with required and min length
        $data = [
            'skills' => ['', 'php', '']
        ];

        $result = $this->validator
            ->field('skills.*')
            ->required()
            ->min(2)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('skills.0', $result->getErrors());
        $this->assertArrayHasKey('skills.2', $result->getErrors());

        // Test 2: Complex nested array with multiple validations
        $data = [
            'users' => [
                ['name' => 'Jo', 'email' => 'invalid-email', 'age' => '17'],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => '25'],
                ['name' => '', 'email' => '', 'age' => 'not-numeric']
            ]
        ];

        $validator = new Validator();
        $result = $validator
            ->field('users.*.name')
            ->required()
            ->min(3)
            ->field('users.*.email')
            ->required()
            ->email()
            ->field('users.*.age')
            ->required()
            ->numeric()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('users.0.name', $result->getErrors());
        $this->assertArrayHasKey('users.0.email', $result->getErrors());
        $this->assertArrayHasKey('users.2.name', $result->getErrors());
        $this->assertArrayHasKey('users.2.email', $result->getErrors());
        $this->assertArrayHasKey('users.2.age', $result->getErrors());

        // Test 3: Array with custom validation and transformation
        $data = [
            'scores' => ['85', '90', '110', '75']
        ];

        $result = $this->validator
            ->field('scores.*')
            ->required()
            ->numeric()
            ->transform(fn($value) => (int) $value)
            ->custom(fn($value) => $value <= 100, 'Score must not exceed 100')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('scores.2', $result->getErrors());
        $this->assertEquals('Score must not exceed 100', $result->getErrors()['scores.2']);
        $this->assertIsInt($data['scores'][0]);

        // Test 4: Valid complex data
        $data = [
            'contacts' => [
                ['email' => 'john@example.com', 'phone' => '1234567890'],
                ['email' => 'jane@example.com', 'phone' => '9876543210']
            ]
        ];

        $validator = new Validator();
        $result = $validator
            ->field('contacts.*.email')
            ->required()
            ->email()
            ->field('contacts.*.phone')
            ->required()
            ->numeric()
            ->custom(fn($value) => strlen((string) $value) === 10, 'Phone must be exactly 10 digits')
            ->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testCustomMessage(): void
    {
        $data = ['name' => ''];
        $message = 'Name is required!';

        $result = $this->validator
            ->field('name')
            ->required()
            ->message($message)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertEquals($message, $result->getErrors()['name']);
    }

    public function testTransformation(): void
    {
        $data = ['name' => ' john '];

        $this->validator
            ->field('name')
            ->required()
            ->transform(fn($value) => trim($value))
            ->min(4)
            ->validate($data);

        $this->assertEquals('john', $data['name']);
    }

    public function testCustomRule(): void
    {
        $this->validator->addRule('uppercase', function ($value) {
            return strtoupper($value) === $value;
        }, 'Must be uppercase');

        $data = ['code' => 'abc'];

        $result = $this->validator
            ->field('code')
            ->required()
            ->uppercase()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertEquals('Must be uppercase', $result->getErrors()['code']);
    }

    public function testNestedValidation(): void
    {
        $data = [
            'user' => [
                'profile' => [
                    'name' => '',
                    'age' => 15
                ]
            ]
        ];

        $result = $this->validator
            ->field('user.profile.name')
            ->required()
            ->field('user.profile.age')
            ->required()
            ->custom(fn($value) => $value >= 18)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('user.profile.name', $result->getErrors());
        $this->assertArrayHasKey('user.profile.age', $result->getErrors());
    }

    public function testTypeValidation(): void
    {
        // Test string validation
        $data = ['name' => true];
        $result = $this->validator
            ->field('name')
            ->string()
            ->validate($data);
        $this->assertFalse($result->isValid());

        // Test int validation
        $data = ['age' => '25'];
        $result = $this->validator
            ->field('age')
            ->int()
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test float validation
        $data = ['price' => '99.99'];
        $result = $this->validator
            ->field('price')
            ->float()
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test bool validation
        $data = ['active' => 'true'];
        $result = $this->validator
            ->field('active')
            ->bool()
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test array validation
        $data = ['items' => 'not-array'];
        $result = $this->validator
            ->field('items')
            ->array()
            ->validate($data);
        $this->assertFalse($result->isValid());
    }

    public function testDateValidation(): void
    {
        // Test date without format
        $data = ['created' => '2025-02-11'];
        $result = $this->validator
            ->field('created')
            ->date()
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test date with format
        $data = ['birthday' => '11/02/2025'];
        $result = $this->validator
            ->field('birthday')
            ->date('d/m/Y')
            ->validate($data);
        $this->assertTrue($result->isValid());

        // Test invalid date
        $data = ['invalid' => 'not-a-date'];
        $result = $this->validator
            ->field('invalid')
            ->date()
            ->validate($data);
        $this->assertFalse($result->isValid());
    }

    public function testUrlValidation(): void
    {
        $data = [
            'valid' => 'https://example.com',
            'invalid' => 'not-a-url',
        ];

        $result = $this->validator
            ->field('valid')
            ->url()
            ->field('invalid')
            ->url()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testBetweenValidation(): void
    {
        $data = [
            'valid' => '15',
            'invalid' => '25',
            'non_numeric' => 'abc'
        ];

        $result = $this->validator
            ->field('valid')
            ->between(10, 20)
            ->field('invalid')
            ->between(0, 10)
            ->field('non_numeric')
            ->between(0, 10)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayHasKey('non_numeric', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testUniqueValidation(): void
    {
        $data = [
            'valid' => [1, 2, 3],
            'invalid' => [1, 2, 2, 3],
        ];

        $result = $this->validator
            ->field('valid')
            ->array()
            ->unique()
            ->field('invalid')
            ->array()
            ->unique()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testNullableValidation(): void
    {
        $data = [
            'empty' => '',
            'null' => null,
            'value' => 'test',
        ];

        $result = $this->validator
            ->field('empty')
            ->nullable()
            ->string()
            ->field('null')
            ->nullable()
            ->string()
            ->field('value')
            ->nullable()
            ->string()
            ->validate($data);

        $this->assertTrue($result->isValid());
    }

    public function testSameValidation(): void
    {
        $data = [
            'password' => 'secret123',
            'confirm_password' => 'secret123',
            'wrong_confirm' => 'different'
        ];

        $result = $this->validator
            ->field('confirm_password')
            ->same('password')
            ->field('wrong_confirm')
            ->same('password')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('wrong_confirm', $result->getErrors());
        $this->assertArrayNotHasKey('confirm_password', $result->getErrors());
    }

    public function testDifferentValidation(): void
    {
        $data = [
            'current_password' => 'secret123',
            'new_password' => 'newpass456',
            'wrong_new' => 'secret123'
        ];

        $result = $this->validator
            ->field('new_password')
            ->different('current_password')
            ->field('wrong_new')
            ->different('current_password')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('wrong_new', $result->getErrors());
        $this->assertArrayNotHasKey('new_password', $result->getErrors());
    }

    public function testMultibyteStringValidation(): void
    {
        $data = [
            'name' => 'José',
            'long_name' => 'あいうえお', // 5 Japanese characters
            'short_name' => '李', // 1 Chinese character
        ];

        $result = $this->validator
            ->field('name')
            ->string()
            ->min(4)
            ->field('long_name')
            ->string()
            ->max(5)
            ->field('short_name')
            ->string()
            ->min(2)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('short_name', $result->getErrors());
        $this->assertArrayNotHasKey('name', $result->getErrors());
        $this->assertArrayNotHasKey('long_name', $result->getErrors());
    }

    public function testAlphaValidation(): void
    {
        $data = [
            'name' => 'José',
            'invalid' => 'John123',
            'numbers' => '123'
        ];

        $result = $this->validator
            ->field('name')
            ->alpha()
            ->field('invalid')
            ->alpha()
            ->field('numbers')
            ->alpha()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayHasKey('numbers', $result->getErrors());
        $this->assertArrayNotHasKey('name', $result->getErrors());
    }

    public function testAlphaNumValidation(): void
    {
        $data = [
            'username' => 'José123',
            'invalid' => 'John_123',
            'valid' => '123abc'
        ];

        $result = $this->validator
            ->field('username')
            ->alphaNum()
            ->field('invalid')
            ->alphaNum()
            ->field('valid')
            ->alphaNum()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayNotHasKey('username', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testInValidation(): void
    {
        $data = [
            'color' => 'red',
            'invalid' => 'orange',
            'valid' => 'blue'
        ];

        $result = $this->validator
            ->field('color')
            ->in(['red', 'green', 'blue'])
            ->field('invalid')
            ->in(['red', 'green', 'blue'])
            ->field('valid')
            ->in(['red', 'green', 'blue'])
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayNotHasKey('color', $result->getErrors());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
    }

    public function testNotInValidation(): void
    {
        $data = [
            'color' => 'red',
            'invalid' => 'orange',
            'valid' => 'blue'
        ];

        $result = $this->validator
            ->field('color')
            ->notIn(['red', 'green', 'blue'])
            ->field('invalid')
            ->notIn(['red', 'green', 'blue'])
            ->field('valid')
            ->notIn(['red', 'green', 'blue'])
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayNotHasKey('invalid', $result->getErrors());
        $this->assertArrayHasKey('color', $result->getErrors());
        $this->assertArrayHasKey('valid', $result->getErrors());
    }

    public function testRegexValidation(): void
    {
        $data = [
            'valid' => 'abcdef',
            'invalid' => '@#$%^&',
            'empty' => ''
        ];

        $result = $this->validator
            ->field('valid')
            ->required()
            ->regex('/^[a-f0-9]{6}$/i')
            ->field('invalid')
            ->required()
            ->regex('/^[a-f0-9]{6}$/i')
            ->field('empty')
            ->required()
            ->regex('/^[a-f0-9]{6}$/i')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayNotHasKey('valid', $result->getErrors());
        $this->assertArrayHasKey('invalid', $result->getErrors());
        $this->assertArrayHasKey('empty', $result->getErrors());
    }

    public function testUserAddressesValidation(): void
    {
        $data = [
            'user' => [
                'addresses' => [
                    [
                        'type' => 'home',
                        'street' => '123 Main St',
                        'city' => 'San Francisco',
                        'state' => 'CA',
                        'zip' => '941',  // Invalid: too short
                        'country' => 'US',
                        'is_primary' => true
                    ],
                    [
                        'type' => 'office',
                        'street' => '',  // Invalid: empty
                        'city' => 'San Jose',
                        'state' => 'CA',
                        'zip' => '95113',
                        'country' => 'US',
                        'is_primary' => true  // Invalid: multiple primary addresses
                    ],
                    [
                        'type' => 'invalid-type',  // Invalid: not in allowed types
                        'street' => '789 Park Ave',
                        'city' => 'Mountain View',
                        'state' => 'CA',
                        'zip' => '94041',
                        'country' => 'XX',  // Invalid: invalid country code
                        'is_primary' => false
                    ]
                ]
            ]
        ];

        $result = $this->validator
            // Basic address fields validation
            ->field('user.addresses.*.type')
            ->required()
            ->in(['home', 'office', 'shipping'])
            ->field('user.addresses.*.street')
            ->required()
            ->string()
            ->min(5)
            ->field('user.addresses.*.city')
            ->required()
            ->string()
            ->min(2)
            ->field('user.addresses.*.state')
            ->required()
            ->string()
            ->regex('/^[A-Z]{2}$/')
            ->field('user.addresses.*.zip')
            ->required()
            ->string()
            ->regex('/^\d{5}(-\d{4})?$/')
            ->field('user.addresses.*.country')
            ->required()
            ->string()
            ->in(['US', 'CA'])  // Just US and Canada for this example
            ->field('user.addresses.*.is_primary')
            ->required()
            ->bool()

            // Custom validation to ensure only one primary address
            ->field('user.addresses')
            ->custom(function ($addresses) {
                $primaryCount = 0;
                foreach ($addresses as $address) {
                    if ($address['is_primary']) {
                        $primaryCount++;
                    }
                }
                return $primaryCount === 1;
            }, 'Only one address can be marked as primary')
            ->validate($data);

        $errors = $result->getErrors();

        // Assert overall validation failed
        $this->assertFalse($result->isValid());

        // Assert specific validation failures
        $this->assertArrayHasKey('user.addresses.0.zip', $errors);
        $this->assertArrayHasKey('user.addresses.1.street', $errors);
        $this->assertArrayHasKey('user.addresses.2.type', $errors);
        $this->assertArrayHasKey('user.addresses.2.country', $errors);
        $this->assertArrayHasKey('user.addresses', $errors);

        // Verify error messages
        $this->assertStringContainsString('pattern', $errors['user.addresses.0.zip']);
        $this->assertStringContainsString('required', $errors['user.addresses.1.street']);
        $this->assertStringContainsString('one of', $errors['user.addresses.2.type']);
        $this->assertStringContainsString('one of', $errors['user.addresses.2.country']);
        $this->assertStringContainsString('primary', $errors['user.addresses']);

        // Fix the validation errors
        $data['user']['addresses'][0]['zip'] = '94105';
        $data['user']['addresses'][1]['street'] = '456 Technology Dr';
        $data['user']['addresses'][1]['is_primary'] = false;
        $data['user']['addresses'][2]['type'] = 'shipping';
        $data['user']['addresses'][2]['country'] = 'CA';

        // Validate again
        $result = $this->validator->validate($data);

        // Assert validation passes after fixes
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testRequiredIfValidation(): void
    {
        $data = [
            'shipping_method' => 'pickup',
            'pickup_location' => null,  
            'delivery_address' => null  
        ];

        $result = $this->validator
            ->field('pickup_location')
            ->requiredIf('shipping_method', 'pickup')
            ->field('delivery_address')
            ->requiredIf('shipping_method', 'delivery')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('pickup_location', $result->getErrors());
        $this->assertArrayNotHasKey('delivery_address', $result->getErrors());

        $data = [
            'has_company' => true,
            'company_name' => null,  
            'company_tax_id' => null 
        ];

        $result = $this->validator
            ->field('company_name')
            ->requiredIf('has_company')
            ->field('company_tax_id')
            ->requiredIf('has_company')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('company_name', $result->getErrors());
        $this->assertArrayHasKey('company_tax_id', $result->getErrors());

        $data = [
            'has_company' => false,
            'company_name' => null,  
            'shipping_method' => 'delivery',
            'pickup_location' => null 
        ];

        $result = $this->validator
            ->field('company_name')
            ->requiredIf('has_company')
            ->field('pickup_location')
            ->requiredIf('shipping_method', 'pickup')
            ->validate($data);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());

        $data = [
            'payment' => [
                'method' => 'card',
                'card' => [
                    'number' => '4242424242424242',
                    'cvv' => null  
                ]
            ]
        ];

        $result = $this->validator
            ->field('payment.card.cvv')
            ->requiredIf('payment.method', 'card')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('payment.card.cvv', $result->getErrors());
    }

    public function testLengthValidation(): void
    {
        $data = [
            'phone' => '1234567890',      // Valid: exactly 10 chars
            'code' => '123',              // Valid: exactly 3 chars
            'pin' => '12345',             // Invalid: not 4 chars
            'empty' => '',                // Invalid: not 1 char
            'null' => null,               // Invalid: not 2 chars
        ];

        $result = $this->validator
            ->field('phone')->length(10)
            ->field('code')->length(3)
            ->field('pin')->length(4)
            ->field('empty')->length(1)
            ->field('null')->length(2)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        
        $this->assertArrayNotHasKey('phone', $errors);
        $this->assertArrayNotHasKey('code', $errors);
        $this->assertArrayHasKey('pin', $errors);
        $this->assertArrayHasKey('empty', $errors);
        $this->assertArrayHasKey('null', $errors);
        $this->assertEquals('Length must be exactly 4 characters', $errors['pin']);
        $this->assertEquals('Length must be exactly 1 characters', $errors['empty']);
        $this->assertEquals('Length must be exactly 2 characters', $errors['null']);

        // Test with non-string values
        $data = [
            'number' => 123,              // Valid: 3 chars when cast to string
            'bool' => true,               // Invalid: not 5 chars when cast to string
        ];

        $result = $this->validator
            ->field('number')->length(3)
            ->field('bool')->length(5)
            ->validate($data);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        
        $this->assertArrayNotHasKey('number', $errors);
        $this->assertArrayHasKey('bool', $errors);
        $this->assertEquals('Length must be exactly 5 characters', $errors['bool']);
    }

    public function testIpValidation(): void
    {
        $data = [
            'ipv4' => '192.168.1.1',             // Valid IPv4
            'ipv6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',  // Valid IPv6
            'invalid_ip' => '256.256.256.256',    // Invalid IP
            'not_ip' => 'hello',                  // Not an IP
        ];

        $result = $this->validator
            ->field('ipv4')
                ->ip('v4')                        // IPv4 only
            ->field('ipv6')
                ->ip('v6')                        // IPv6 only
            ->field('invalid_ip')
                ->ip()                            // Any IP version
            ->field('not_ip')
                ->ip()                            // Any IP version
            ->validate($data);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        
        $this->assertArrayNotHasKey('ipv4', $errors);
        $this->assertArrayNotHasKey('ipv6', $errors);
        $this->assertArrayHasKey('invalid_ip', $errors);
        $this->assertArrayHasKey('not_ip', $errors);
        $this->assertEquals('Must be a valid IP address', $errors['invalid_ip']);
        $this->assertEquals('Must be a valid IP address', $errors['not_ip']);

        // Test IPv6 with IPv4 validation
        $data = ['mixed' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'];
        $result = $this->validator
            ->field('mixed')
            ->ip('v4')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('mixed', $errors);
        $this->assertEquals('Must be a valid IPv4 address', $errors['mixed']);
    }

    public function testSlugValidation(): void
    {
        $data = [
            'valid1' => 'hello-world',           // Valid
            'valid2' => 'my-blog-post-123',      // Valid
            'invalid1' => 'Hello World',         // Invalid: spaces and uppercase
            'invalid2' => 'hello--world',        // Invalid: consecutive hyphens
            'invalid3' => '-hello-world',        // Invalid: starts with hyphen
            'invalid4' => 'hello-world-',        // Invalid: ends with hyphen
            'invalid5' => 'hello_world',         // Invalid: underscore
            'invalid6' => 'héllo-world',         // Invalid: special characters
        ];

        $result = $this->validator
            ->field('valid1')->slug()
            ->field('valid2')->slug()
            ->field('invalid1')->slug()
            ->field('invalid2')->slug()
            ->field('invalid3')->slug()
            ->field('invalid4')->slug()
            ->field('invalid5')->slug()
            ->field('invalid6')->slug()
            ->validate($data);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        
        $this->assertArrayNotHasKey('valid1', $errors);
        $this->assertArrayNotHasKey('valid2', $errors);
        $this->assertArrayHasKey('invalid1', $errors);
        $this->assertArrayHasKey('invalid2', $errors);
        $this->assertArrayHasKey('invalid3', $errors);
        $this->assertArrayHasKey('invalid4', $errors);
        $this->assertArrayHasKey('invalid5', $errors);
        $this->assertArrayHasKey('invalid6', $errors);
    }

    public function testDateBeforeAfterValidation(): void
    {
        $data = [
            'past_date' => '2023-01-01',
            'future_date' => '2025-12-31',
            'custom_date' => '31-12-2024',
            'invalid_date' => 'not-a-date',
        ];

        $result = $this->validator
            ->field('past_date')
                ->before('2024-01-01')
            ->field('future_date')
                ->after('2025-01-01')
            ->field('custom_date')
                ->before('01-01-2025', 'd-m-Y')
            ->field('invalid_date')
                ->before('2024-01-01')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        
        $this->assertArrayNotHasKey('past_date', $errors);
        $this->assertArrayNotHasKey('future_date', $errors);
        $this->assertArrayNotHasKey('custom_date', $errors);
        $this->assertArrayHasKey('invalid_date', $errors);

        // Test invalid dates
        $data = [
            'too_late' => '2024-12-31',
            'too_early' => '2023-01-01',
        ];

        $result = $this->validator
            ->field('too_late')
                ->before('2024-01-01')
            ->field('too_early')
                ->after('2024-01-01')
            ->validate($data);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        
        $this->assertArrayHasKey('too_late', $errors);
        $this->assertArrayHasKey('too_early', $errors);
        $this->assertEquals('Date must be before 2024-01-01', $errors['too_late']);
        $this->assertEquals('Date must be after 2024-01-01', $errors['too_early']);
    }
}
