<?php

declare(strict_types=1);

namespace Lightpack\Tests\Validation;

use Lightpack\Validation\Validator;
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

        $this->validator
            ->field('name')
            ->required()
            ->min(2);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->passes());
    }

    public function testFailedValidation(): void
    {
        $data = ['name' => 'J'];

        $this->validator
            ->field('name')
            ->required()
            ->min(2);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testEmailValidation(): void
    {
        $data = ['email' => 'invalid'];

        $this->validator
            ->field('email')
            ->required()
            ->email();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertFalse($result->passes());
    }

    public function testCustomValidation(): void
    {
        $data = ['age' => 15];

        $this->validator
            ->field('age')
            ->required()
            ->custom(fn($value) => $value >= 18, 'Must be 18 or older');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testWildcardValidation(): void
    {
        // Test 1: Basic array validation with required and min length
        $data = [
            'skills' => ['', 'php', '']
        ];

        $this->validator
            ->field('skills.*')
            ->required()
            ->min(2);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());

        // Test 2: Complex nested array with multiple validations
        $data = [
            'users' => [
                ['name' => 'Jo', 'email' => 'invalid-email', 'age' => '17'],
                ['name' => 'Jane', 'email' => 'jane@example.com', 'age' => '25'],
                ['name' => '', 'email' => '', 'age' => 'not-numeric']
            ]
        ];

        $validator = new Validator();
        $validator
            ->field('users.*.name')
            ->required()
            ->min(3)
            ->field('users.*.email')
            ->required()
            ->email()
            ->field('users.*.age')
            ->required()
            ->numeric();

        $validator->setInput($data);
        $result = $validator->validate();
        $this->assertTrue($result->fails());

        // Test 3: Valid complex data
        $data = [
            'contacts' => [
                ['email' => 'john@example.com', 'phone' => '1234567890'],
                ['email' => 'jane@example.com', 'phone' => '9876543210']
            ]
        ];

        $validator = new Validator();
        $validator
            ->field('contacts.*.email')
            ->required()
            ->email()
            ->field('contacts.*.phone')
            ->required()
            ->numeric()
            ->custom(fn($value) => strlen((string) $value) === 10, 'Phone must be exactly 10 digits');

        $validator->setInput($data);
        $result = $validator->validate();
        $this->assertTrue($result->passes());
    }

    public function testCustomMessage(): void
    {
        $data = ['name' => ''];
        $message = 'Name is required!';

        $this->validator
            ->field('name')
            ->required()
            ->message($message);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testCustomRule(): void
    {
        $this->validator->addRule('uppercase', function ($value) {
            return strtoupper($value) === $value;
        }, 'Must be uppercase');

        $data = ['code' => 'abc'];

        $this->validator
            ->field('code')
            ->required()
            ->uppercase();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
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

        $this->validator
            ->field('user.profile.name')
            ->required()
            ->field('user.profile.age')
            ->required()
            ->custom(fn($value) => $value >= 18);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testTypeValidation(): void
    {
        // Test string validation
        $data = ['name' => true];
        $this->validator->field('name')->string();
        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());

        // Test int validation
        $data = ['age' => '25'];
        $this->validator->field('age')->int();
        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->passes());

        // Test float validation
        $data = ['price' => '99.99'];
        $this->validator->field('price')->float();
        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->passes());

        // Test bool validation
        $data = ['active' => 'true'];
        $this->validator->field('active')->bool();
        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->passes());

        // Test array validation
        $data = ['items' => 'not-array'];
        $this->validator->field('items')->array();
        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testDateValidation(): void
    {
        // Test date without format
        $data = ['created' => '2025-02-11'];
        $this->validator->field('created')->date();
        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->passes());

        // Test date with format
        $data = ['birthday' => '11/02/2025'];
        $this->validator->field('birthday')->date('d/m/Y');
        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->passes());

        // Test invalid date
        $data = ['invalid' => 'not-a-date'];
        $this->validator->field('invalid')->date();
        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testUrlValidation(): void
    {
        $data = [
            'valid' => 'https://example.com',
            'invalid' => 'not-a-url',
        ];

        $this->validator
            ->field('valid')
            ->url()
            ->field('invalid')
            ->url();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testBetweenValidation(): void
    {
        // Test basic between validation
        $data = [
            'valid' => '15',
            'invalid' => '25',
            'non_numeric' => 'abc'
        ];

        $this->validator
            ->field('valid')
            ->between(10, 20)
            ->field('invalid')
            ->between(0, 10)
            ->field('non_numeric')
            ->between(0, 10);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());

        // Test chained between validation
        $data = [
            'age' => '25',
            'score' => '85.5'
        ];

        $this->validator
            ->field('age')
            ->required()
            ->int()
            ->between(18, 30)
            ->field('score')
            ->required()
            ->float()
            ->between(0, 100);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->passes());
    }

    public function testUniqueValidation(): void
    {
        $data = [
            'valid' => [1, 2, 3],
            'invalid' => [1, 2, 2, 3],
        ];

        $this->validator
            ->field('valid')
            ->array()
            ->unique()
            ->field('invalid')
            ->array()
            ->unique();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testNullableValidation(): void
    {
        $data = [
            'empty' => '',
            'null' => null,
            'value' => 'test',
        ];

        $this->validator
            ->field('empty')->string()
            ->field('null')->string()
            ->field('value')->string();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->passes());
    }

    public function testSameValidation(): void
    {
        $data = [
            'password' => 'secret123',
            'confirm_password' => 'secret123',
            'wrong_confirm' => 'different'
        ];

        $this->validator
            ->field('confirm_password')
            ->same('password')
            ->field('wrong_confirm')
            ->same('password');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testDifferentValidation(): void
    {
        $data = [
            'current_password' => 'secret123',
            'new_password' => 'newpass456',
            'wrong_new' => 'secret123'
        ];

        $this->validator
            ->field('new_password')
            ->different('current_password')
            ->field('wrong_new')
            ->different('current_password');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testMultibyteStringValidation(): void
    {
        $data = [
            'name' => 'José',
            'long_name' => 'あいうえお', // 5 Japanese characters
            'short_name' => '李', // 1 Chinese character
        ];

        $this->validator
            ->field('name')
            ->string()
            ->min(4)
            ->field('long_name')
            ->string()
            ->max(5)
            ->field('short_name')
            ->string()
            ->min(2);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testAlphaValidation(): void
    {
        $data = [
            'name' => 'José',
            'invalid' => 'John123',
            'numbers' => '123'
        ];

        $this->validator
            ->field('name')
            ->alpha()
            ->field('invalid')
            ->alpha()
            ->field('numbers')
            ->alpha();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testAlphaNumValidation(): void
    {
        $data = [
            'username' => 'José123',
            'invalid' => 'John_123',
            'valid' => '123abc'
        ];

        $this->validator
            ->field('username')
            ->alphaNum()
            ->field('invalid')
            ->alphaNum()
            ->field('valid')
            ->alphaNum();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testInValidation(): void
    {
        $data = [
            'color' => 'red',
            'invalid' => 'orange',
            'valid' => 'blue'
        ];

        $this->validator
            ->field('color')
            ->in(['red', 'green', 'blue'])
            ->field('invalid')
            ->in(['red', 'green', 'blue'])
            ->field('valid')
            ->in(['red', 'green', 'blue']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testNotInValidation(): void
    {
        $data = [
            'color' => 'red',
            'invalid' => 'orange',
            'valid' => 'blue'
        ];

        $this->validator
            ->field('color')
            ->notIn(['red', 'green', 'blue'])
            ->field('invalid')
            ->notIn(['red', 'green', 'blue'])
            ->field('valid')
            ->notIn(['red', 'green', 'blue']);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testRegexValidation(): void
    {
        $data = [
            'valid' => 'abcdef',
            'invalid' => '@#$%^&',
            'empty' => ''
        ];

        $this->validator
            ->field('valid')
            ->required()
            ->regex('/^[a-f0-9]{6}$/i')
            ->field('invalid')
            ->required()
            ->regex('/^[a-f0-9]{6}$/i')
            ->field('empty')
            ->required()
            ->regex('/^[a-f0-9]{6}$/i');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
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

        $this->validator
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
            }, 'Only one address can be marked as primary');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());

        // Fix the validation errors
        $data['user']['addresses'][0]['zip'] = '94105';
        $data['user']['addresses'][1]['street'] = '456 Technology Dr';
        $data['user']['addresses'][1]['is_primary'] = false;
        $data['user']['addresses'][2]['type'] = 'shipping';
        $data['user']['addresses'][2]['country'] = 'CA';

        // Validate again
        $this->validator->setInput($data);
        $result = $this->validator->validate();

        // Assert validation passes after fixes
        $this->assertTrue($result->passes());
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

        $this->validator
            ->field('phone')->length(10)
            ->field('code')->length(3)
            ->field('pin')->length(4)
            ->field('empty')->length(1)
            ->field('null')->length(2);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());

        // Test with non-string values
        $data = [
            'number' => 123,              // Valid: 3 chars when cast to string
            'bool' => true,               // Invalid: not 5 chars when cast to string
        ];

        $this->validator
            ->field('number')->length(3)
            ->field('bool')->length(5);

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testIpValidation(): void
    {
        $data = [
            'ipv4' => '192.168.1.1',             // Valid IPv4
            'ipv6' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',  // Valid IPv6
            'invalid_ip' => '256.256.256.256',    // Invalid IP
            'not_ip' => 'hello',                  // Not an IP
        ];

        $this->validator
            ->field('ipv4')
            ->ip('v4')                        // IPv4 only
            ->field('ipv6')
            ->ip('v6')                        // IPv6 only
            ->field('invalid_ip')
            ->ip()                            // Any IP version
            ->field('not_ip')
            ->ip();                           // Any IP version

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());

        // Test IPv6 with IPv4 validation
        $data = ['mixed' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'];
        $this->validator
            ->field('mixed')
            ->ip('v4');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
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

        $this->validator
            ->field('valid1')->slug()
            ->field('valid2')->slug()
            ->field('invalid1')->slug()
            ->field('invalid2')->slug()
            ->field('invalid3')->slug()
            ->field('invalid4')->slug()
            ->field('invalid5')->slug()
            ->field('invalid6')->slug();

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testDateBeforeAfterValidation(): void
    {
        $data = [
            'past_date' => '2023-01-01',
            'future_date' => '2025-12-31',
            'custom_date' => '31-12-2024',
            'invalid_date' => 'not-a-date',
        ];

        $this->validator
            ->field('past_date')
            ->before('2024-01-01')
            ->field('future_date')
            ->after('2025-01-01')
            ->field('custom_date')
            ->before('01-01-2025', 'd-m-Y')
            ->field('invalid_date')
            ->before('2024-01-01');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());

        // Test invalid dates
        $data = [
            'too_late' => '2024-12-31',
            'too_early' => '2023-01-01',
        ];

        $this->validator
            ->field('too_late')
            ->before('2024-01-01')
            ->field('too_early')
            ->after('2024-01-01');

        $this->validator->setInput($data);
        $result = $this->validator->validate();
        $this->assertTrue($result->fails());
    }

    public function testBasicArrayValidation()
    {
        $validator = new Validator();

        // Test valid array
        $validator->field('items')->required()->array();
        $validator->setInput(['items' => ['a', 'b', 'c']]);
        $this->assertTrue($validator->validate()->passes());

        // Test non-array
        $validator->field('items')->required()->array();
        $validator->setInput(['items' => 'not an array']);
        $this->assertFalse($validator->validate()->passes());
    }

    public function testArrayMinValidation()
    {
        $validator = new Validator();
        $validator->field('items')->required()->array(2);

        // Test with less items than minimum
        $validator->setInput(['items' => ['a']]);
        $this->assertFalse($validator->validate()->passes());

        // Test with exact minimum
        $validator->setInput(['items' => ['a', 'b']]);
        $this->assertTrue($validator->validate()->passes());

        // Test with more than minimum
        $validator->setInput(['items' => ['a', 'b', 'c']]);
        $this->assertTrue($validator->validate()->passes());
    }

    public function testArrayMaxValidation()
    {
        $validator = new Validator();

        // Test with less than maximum
        $validator->field('items')->required()->array(null, 2);
        $validator->setInput(['items' => ['a']]);
        $this->assertTrue($validator->validate()->passes());

        // Test with exact maximum
        $validator->field('items')->required()->array(null, 2);
        $validator->setInput(['items' => ['a', 'b']]);
        $this->assertTrue($validator->validate()->passes());

        // Test with more than maximum
        $validator->field('items')->required()->array(null, 2);
        $validator->setInput(['items' => ['a', 'b', 'c']]);
        $this->assertFalse($validator->validate()->passes());
    }

    public function testArrayMinMaxValidation()
    {
        $validator = new Validator();

        // Test with less than minimum
        $validator->field('items')->required()->array(2, 4);
        $validator->setInput(['items' => ['a']]);
        $this->assertFalse($validator->validate()->passes());

        // Test with minimum
        $validator->field('items')->required()->array(2, 4);
        $validator->setInput(['items' => ['a', 'b']]);
        $this->assertTrue($validator->validate()->passes());

        // Test with between min and max
        $validator->field('items')->required()->array(2, 4);
        $validator->setInput(['items' => ['a', 'b', 'c']]);
        $this->assertTrue($validator->validate()->passes());

        // Test with maximum
        $validator->field('items')->required()->array(2, 4);
        $validator->setInput(['items' => ['a', 'b', 'c', 'd']]);
        $this->assertTrue($validator->validate()->passes());

        // Test with more than maximum
        $validator->field('items')->required()->array(2, 4);
        $validator->setInput(['items' => ['a', 'b', 'c', 'd', 'e']]);
        $this->assertFalse($validator->validate()->passes());
    }

    public function testErrorMessages()
    {
        $validator = new Validator();

        // Test min error message
        $validator->field('items')->required()->array(2);
        $validator->setInput(['items' => ['a']]);
        $validator->validate();
        $this->assertStringContainsString('at least 2 items', $validator->getError('items'));

        // Test max error message
        $validator->field('items')->required()->array(null, 2);
        $validator->setInput(['items' => ['a', 'b', 'c']]);
        $validator->validate();
        $this->assertStringContainsString('more than 2 items', $validator->getError('items'));

        // Test min-max error message
        $validator->field('items')->required()->array(2, 4);
        $validator->setInput(['items' => ['a']]);
        $validator->validate();
        $this->assertStringContainsString('between 2 and 4 items', $validator->getError('items'));
    }

    public function testChainedRuleMessages()
    {
        $validator = new Validator();
        
        // Define custom messages for each rule
        $messages = [
            'email' => [
                'required' => 'Email is required',
                'email' => 'Please provide a valid email format',
            ],
            'password' => [
                'required' => 'Password cannot be empty',
                'alphanum' => 'Password must be text wth alphabets and numbers only',
                'between' => 'Password must be between 8 and 16 characters',
            ],
        ];

        // Test with invalid data
        $data = [
            'email' => '',                // Should fail required
            'password' => '',          // Should fail required
        ];

        // Set up validation rules
        $validator->field('email')->required()->message($messages['email']['required'])->email()->message($messages['email']['email']);
        $validator->field('password')->required()->message($messages['password']['required'])->alphaNum()->message($messages['password']['alphanum'])->between(8, 16)->message($messages['password']['between']);
        $validator->setInput($data)->validate();

        // Verify each error message matches our custom messages
        $this->assertEquals($messages['email']['required'], $validator->getError('email'));
        $this->assertEquals($messages['password']['required'], $validator->getError('password'));

        // Now test with partially valid data to trigger different error messages
        $data = [
            'email' => 'invalid-email',   // Should fail email format
            'password' => '!abcde@',            // Should fail alphanum
        ];

        // Set up validation rules
        $validator->field('email')->required()->message($messages['email']['required'])->email()->message($messages['email']['email']);
        $validator->field('password')->required()->message($messages['password']['required'])->alphaNum()->message($messages['password']['alphanum'])->between(8, 16)->message($messages['password']['between']);
        $validator->setInput($data)->validate($data);

        // Verify the different error messages
        $this->assertEquals($messages['email']['email'], $validator->getError('email'));
        $this->assertEquals($messages['password']['alphanum'], $validator->getError('password'));
    }

    public function testPasswordValidationRules()
    {
        $validator = new Validator();
        
        // Test valid password
        $validator->field('password')->required()->between(8, 32)->hasUppercase()->hasLowercase()->hasNumber()->hasSymbol();

        $data = ['password' => 'Test123!@#', 'a' => 'b'];
        $validator->setInput($data)->validate();
        $this->assertTrue($validator->passes());

        // Test missing uppercase
        $validator->field('password')->required()->between(8, 32)->hasUppercase()->hasLowercase()->hasNumber()->hasSymbol();
        $data = ['password' => 'test123!@#'];
        $validator->setInput($data)->validate();
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('uppercase', $validator->getError('password'));

        // Test missing lowercase
        $validator->field('password')->required()->between(8, 32)->hasUppercase()->hasLowercase()->hasNumber()->hasSymbol();
        $data = ['password' => 'TEST123!@#'];
        $validator->setInput($data)->validate();
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('lowercase', $validator->getError('password'));

        // Test missing number
        $validator->field('password')->required()->between(8, 32)->hasUppercase()->hasLowercase()->hasNumber()->hasSymbol();
        $data = ['password' => 'TestABC!@#'];
        $validator->setInput($data)->validate();
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('numeric', $validator->getError('password'));

        // Test missing symbol
        $validator->field('password')->required()->between(8, 32)->hasUppercase()->hasLowercase()->hasNumber()->hasSymbol();
        $data = ['password' => 'Test1234ABC'];
        $validator->setInput($data)->validate();
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('special character', $validator->getError('password'));

        // Test too short password
        $validator->field('password')->required()->between(8, 32)->hasUppercase()->hasLowercase()->hasNumber()->hasSymbol();
        $data = ['password' => 'Te1!'];
        $validator->setInput($data)->validate();
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('between', $validator->getError('password'));

        // Test too long password
        $validator->field('password')->required()->between(8, 32)->hasUppercase()->hasLowercase()->hasNumber()->hasSymbol();
        $data = ['password' => str_repeat('Te1!', 10)];
        $validator->setInput($data)->validate();
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('between', $validator->getError('password'));
    }
}
