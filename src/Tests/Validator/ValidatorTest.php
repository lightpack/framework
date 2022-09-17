<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Lightpack\Validator\Validator;

final class ValidatorTest extends TestCase
{
    public function testHasValidationErrors()
    {
        $data = [
            'name' => 'ma',
            'email' => 'hello@world',
        ];

        $rules = [
            'name' => 'required|min:3|max:12',
            'email' => 'required|email'
        ];

        $validator = new Validator();
        $validator->setInput($data)->setrules($rules)->run();

        $this->assertTrue($validator->hasErrors());
    }

    public function testHasNoValidationErrors()
    {
        $data = [
            'name' => 'maxim',
            'email' => 'hello@world',
        ];

        $rules = [
            'name' => 'required|min:3|max:12',
            'email' => 'required|email'
        ];

        $validator = new Validator();
        $validator->setInput($data)->setrules($rules)->run();

        $this->assertTrue($validator->hasErrors());
    }

    public function testCreatesAppropriateErrorMessages()
    {
        $data = ['password'  => 'hello', 'email' => 'hello@example.com'];
        $rules = ['password' => 'min:6', 'email' => 'email'];

        $validator = new Validator();
        $validator->setInput($data)->setRules($rules)->run();

        $this->assertCount(1, $validator->getErrors());
        $this->assertTrue($validator->getError('email') === '');
        $this->assertTrue($validator->getError('password') !== '');
    }

    public function testCanSetRulesIndividually()
    {
        $data = [
            'phone' => '091234521',
            'fname' => 'Bob123',
            'lname' => 'Williams'
        ];

        $validator = new Validator();

        $validator
            ->setInput($data)
            ->setRule('phone', 'required|length:10')
            ->setRule('fname', [
                'rules' => 'required|alpha',
                'label' => 'First name',
            ])
            ->setRule('lname', [
                'rules' => 'required|alpha',
                'error' => 'Last name should be your title.'
            ])
            ->run();

        $errors = $validator->getErrors();

        $this->assertTrue($validator->hasErrors());
        $this->assertCount(2, $errors);
        $this->assertTrue(isset($errors['phone']));
        $this->assertTrue(isset($errors['fname']));
        $this->assertFalse(isset($errors['lname']));
    }

    public function testValidationRuleRequired()
    {
        // Assertion 1
        $data = ['password' => ''];
        $validator = new Validator();
        $validator->setInput($data)->setRule('password', 'required')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['password' => 'hello'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('password', 'required')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleAlpha()
    {
        // Assertion 1
        $data = ['name' => 'Bob123'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'alpha')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['name' => 'Bob'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'alpha')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleAlnum()
    {
        // Assertion 1
        $data = ['name' => '@Bob123'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'alnum')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['name' => 'Bob123'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'alnum')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleEmail()
    {
        // Assertion 1
        $data = ['email' => 'hello@example'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('email', 'email')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['email' => 'hello@example.co.in'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('email', 'email')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleSlug()
    {
        // Assertion 1
        $data = ['slug' => 'hello%world'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('slug', 'slug')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['slug' => 'hello-world'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('slug', 'slug')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleUrl()
    {
        // Assertion 1
        $data = ['url' => 'http://example:8080'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('url', 'url')->run();

        // Assertion 2
        $data = ['url' => 'http://example'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('url', 'url')->run();

        $this->assertFalse($validator->hasErrors());

        // Assertion 3
        $data = ['url' => 'http://example.com'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('url', 'url')->run();

        $this->assertFalse($validator->hasErrors());

        // Assertion 4
        $data = ['url' => 'http://123.example.com'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('url', 'url')->run();

        $this->assertFalse($validator->hasErrors());

        // Assertion 5
        $data = ['url' => 'http//example.com'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('url', 'url')->run();

        $this->assertTrue($validator->hasErrors());
    }

    public function testValidationRuleIpAdress()
    {
        // Assertion 1
        $data = ['ip' => '0.0.0.0'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('ip', 'ip')->run();

        $this->assertFalse($validator->hasErrors());

        // Assertion 2
        $data = ['ip' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('ip', 'ip')->run();

        $this->assertFalse($validator->hasErrors());

        // Assertion 2
        $data = ['ip' => '192.254.254.XX'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('ip', 'ip')->run();

        $this->assertTrue($validator->hasErrors());
    }

    public function testValidationRuleLength()
    {
        // Assertion 1
        $data = ['name' => 'Bruce'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'length:6')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['name' => 'Bruce'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'length:5')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleMin()
    {
        // Assertion 1
        $data = ['name' => 'Bruce'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'min:6')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['name' => 'Bruce'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'min:5')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleMax()
    {
        // Assertion 1
        $data = ['name' => 'Bruce'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'max:4')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['name' => 'Bob'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'max:3')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleBetween()
    {
        // Assertion 1
        $data = ['age' => 23];
        $validator = new Validator();
        $validator->setInput($data)->setRule('age', 'between:4,8')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['age' => 23];
        $validator = new Validator();
        $validator->setInput($data)->setRule('age', 'between:18,30')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleDate()
    {
        // Assertion 1
        $data = ['date' => '19-01-2021'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('date', 'date:d-m-y')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['date' => '19-01-2021'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('date', 'date:d-m-Y')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleBefore()
    {
        // Assertion 1
        $data = ['date_before' => '19-01-2021'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('date_before', 'before:/d-m-y,12-01-2021/')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['date_before' => '19-01-2021'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('date_before', 'before:/d-m-Y,12-01-2021/')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 3
        $data = ['date_before' => '19-01-2021'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('date_before', 'before:d-m-Y,12-09-2021')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleAfter()
    {
        // Assertion 1
        $data = ['date_after' => '19-01-2021'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('date_after', 'after:/d-m-y,18-01-2021/')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['date_after' => '19-01-2021'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('date_after', 'after:/d-m-Y,19-01-2021/')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 3
        $data = ['date_after' => '19-01-2021'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('date_after', 'after:d-m-Y,12-01-2021')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleSame()
    {
        // Assertion 1
        $data = ['password' => 'hello', 'confirm_password' => 'helloo'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('confirm_password', 'same:password')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['password' => 'hello', 'confirm_password' => 'hello'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('confirm_password', 'same:password')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleRegex()
    {
        // Assertion 1
        $data = ['phone' => '123-321-445'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('phone', 'regex:/^\d{3}-\d{3}-\d{4}$/')->run();

        $this->assertTrue($validator->hasErrors());

        // Assertion 2
        $data = ['phone' => '123-321-4455'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('phone', 'regex:/^\d{3}-\d{3}-\d{4}$/')->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleCanValidateNestedFields()
    {
        $data = ['name' => 'John', 'address' => ['street' => '123 Main St', 'city' => 'Bengaluru', 'state' => 'NY']];
        $validator = new Validator();
        $validator->setInput($data)->setRule('name', 'required')->run();
        $validator->setRule('address.street', 'required')->run();
        $validator->setRule('address.city', 'required')->run();
        $validator->setRule('address.state', 'required')->run();
        $validator->setRule('address.state', [
            'rules' => 'required|length:2',
            'error' => 'State must be 2 characters long',
        ])->run();
        
        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleCanUseCallback()
    {
        $data = ['framework' => 'Lightpack'];
        $validator = new Validator();
        $validator->setInput($data)->setRule('framework', function ($data) {
            return 'Lightpack' === $data;
        })->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleCanSetCallback()
    {
        $data = ['framework' => 'Lightpack'];
        $validator = new Validator();
        $validator->setInput($data)->setInput($data)->setRules([
            'framework' => function ($data) {
                return 'Lightpack' === $data;
            }
        ])->run();

        $this->assertFalse($validator->hasErrors());
    }

    public function testValidationRuleCanSetCallbackErrors()
    {
        $data = ['age' => 23];
        $validator = new Validator();
        $validator->setInput($data)->setRule('age', function ($data) {
            return 23 !== $data;
        })->run();

        $this->assertTrue($validator->hasErrors());
        $this->assertEquals($validator->getError('age'), 'Age is invalid');
    }

    public function testValidationRuleCanSetCustomCallbackErrors()
    {
        $data = ['age' => 17];
        $validator = new Validator();
        $validator->setInput($data)->setRule('age', [
            'error' => 'You must be above 18',
            'rules' => function ($data) {
                return $data >= 18;
            }
        ])->run();

        $this->assertTrue($validator->hasErrors());
        $this->assertEquals($validator->getError('age'), 'You must be above 18');
    }

    public function testValidationRuleCanSetCustomCallbackLabels()
    {
        $data = ['age' => 17];
        $validator = new Validator();
        $validator->setInput($data)->setRule('age', [
            'label' => 'Your age',
            'rules' => function ($data) {
                return $data >= 18;
            }
        ])->run();

        $this->assertTrue($validator->hasErrors());
        $this->assertEquals($validator->getError('age'), 'Your age is invalid');
    }

    public function testValidationRuleCanSetMultipleCallbacksTogether()
    {
        $data = ['age1' => 17, 'age2' => 23, 'age3' => 49];
        $validator = new Validator();

        $validator->setInput($data)->setRules([
            'age1' => function ($data) {
                return $data >= 23;
            },
            'age2' => function ($data) {
                return $data >= 23;
            },
            'age3' => [
                'label' => 'Grandpa\'s age',
                'rules' => function($data) {
                    return $data >= 50;
                }
            ],
        ])->run();

        $this->assertTrue($validator->hasErrors());
        $this->assertEquals($validator->getError('age1'), 'Age1 is invalid');
        $this->assertEmpty($validator->getError('age2'));
        $this->assertEquals($validator->getError('age3'), "Grandpa's age is invalid");
    }
}
