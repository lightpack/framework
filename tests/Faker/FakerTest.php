<?php

declare(strict_types=1);

use Lightpack\Faker\Faker;
use PHPUnit\Framework\TestCase;

class FakerTest extends TestCase
{
    private Faker $faker;

    protected function setUp(): void
    {
        $this->faker = new Faker();
    }

    public function testName()
    {
        $name = $this->faker->name();
        $this->assertIsString($name);
        $this->assertMatchesRegularExpression('/^[A-Za-z]+ [A-Za-z]+$/', $name, 'Name should be two words');
    }

    public function testEmail()
    {
        $email = $this->faker->email();
        $this->assertIsString($email);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+@[a-z0-9.]+$/', $email, 'Email should be plausible');
    }

    public function testUsername()
    {
        $username = $this->faker->username();
        $this->assertIsString($username);
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z]+[0-9]+$/', $username, 'Username format');
    }

    public function testPhone()
    {
        $phone = $this->faker->phone();
        $this->assertIsString($phone);
        $this->assertMatchesRegularExpression('/^\+\d{1,3}-\d{8}$/', $phone, 'Phone format');
    }

    public function testAddress()
    {
        $address = $this->faker->address();
        $this->assertIsString($address);
        $this->assertStringContainsString(',', $address, 'Address contains commas');
    }

    public function testCity()
    {
        $city = $this->faker->city();
        $this->assertIsString($city);
        $this->assertNotEmpty($city);
    }

    public function testState()
    {
        $state = $this->faker->state();
        $this->assertIsString($state);
        $this->assertNotEmpty($state);
    }

    public function testCountry()
    {
        $country = $this->faker->country();
        $this->assertIsString($country);
        $this->assertNotEmpty($country);
    }

    public function testCompany()
    {
        $company = $this->faker->company();
        $this->assertIsString($company);
        $this->assertNotEmpty($company);
    }

    public function testJobTitle()
    {
        $title = $this->faker->jobTitle();
        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testProductName()
    {
        $product = $this->faker->productName();
        $this->assertIsString($product);
        $this->assertNotEmpty($product);
    }

    public function testUrl()
    {
        $url = $this->faker->url();
        $this->assertIsString($url);
        $this->assertStringStartsWith('https://', $url);
    }

    public function testIpv4()
    {
        $ip = $this->faker->ipv4();
        $this->assertMatchesRegularExpression('/^(\d{1,3}\.){3}\d{1,3}$/', $ip);
    }

    public function testIpv6()
    {
        $ip = $this->faker->ipv6();
        $this->assertMatchesRegularExpression('/^([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}$/i', $ip);
    }

    public function testHexColor()
    {
        $color = $this->faker->hexColor();
        $this->assertMatchesRegularExpression('/^#[A-Fa-f0-9]{6}$/', $color);
    }

    public function testPassword()
    {
        $pass = $this->faker->password(12);
        $this->assertIsString($pass);
        $this->assertSame(12, strlen($pass));
    }

    public function testAge()
    {
        $age = $this->faker->age(18, 65);
        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(18, $age);
        $this->assertLessThanOrEqual(65, $age);
    }

    public function testDob()
    {
        $dob = $this->faker->dob(18, 65);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $dob);
    }

    public function testZipCode()
    {
        $zip = $this->faker->zipCode();
        $this->assertIsString($zip);
        $this->assertGreaterThanOrEqual(5, strlen(str_replace([' ', '-'], '', $zip)));
    }

    public function testLatitude()
    {
        $lat = $this->faker->latitude();
        $this->assertIsFloat($lat);
        $this->assertGreaterThanOrEqual(-90, $lat);
        $this->assertLessThanOrEqual(90, $lat);
    }

    public function testLongitude()
    {
        $lon = $this->faker->longitude();
        $this->assertIsFloat($lon);
        $this->assertGreaterThanOrEqual(-180, $lon);
        $this->assertLessThanOrEqual(180, $lon);
    }

    public function testSlug()
    {
        $slug = $this->faker->slug(3);
        $this->assertMatchesRegularExpression('/^[a-z]+(-[a-z]+){2}$/', $slug);
    }

    public function testPrice()
    {
        $price = $this->faker->price(10, 100, '$');
        $this->assertIsString($price);
        $this->assertStringStartsWith('$', $price);
        $this->assertMatchesRegularExpression('/^\$\d+\.\d{2}$/', $price);
    }

    public function testCreditCardNumber()
    {
        $cc = $this->faker->creditCardNumber();
        $ccDigits = str_replace(' ', '', $cc);
        $this->assertMatchesRegularExpression('/^(4\d{15}|5\d{15}|3[47]\d{13})$/', $ccDigits, 'Plausible Visa/MasterCard/Amex');
    }

    public function testOtp()
    {
        $otp = $this->faker->otp();
        $this->assertIsString($otp);
        $this->assertSame(6, strlen($otp));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $otp);
    }

    public function testSentence()
    {
        $sentence = $this->faker->sentence(8);
        $this->assertIsString($sentence);
        $this->assertStringEndsWith('.', $sentence);
    }

    public function testParagraph()
    {
        $para = $this->faker->paragraph(3);
        $this->assertIsString($para);
        $this->assertGreaterThan(10, strlen($para));
    }

    public function testDate()
    {
        $date = $this->faker->date('Y-m-d');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $date);
    }

    public function testUuid()
    {
        $uuid = $this->faker->uuid();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $uuid);
    }

    public function testEnum()
    {
        $val = $this->faker->enum(['red', 'blue', 'green']);
        $this->assertContains($val, ['red', 'blue', 'green']);
    }

    public function testBool()
    {
        $bool = $this->faker->bool();
        $this->assertIsBool($bool);
    }

    public function testNumber()
    {
        $num = $this->faker->number(10, 20);
        $this->assertIsInt($num);
        $this->assertGreaterThanOrEqual(10, $num);
        $this->assertLessThanOrEqual(20, $num);
    }

    public function testFloat()
    {
        $float = $this->faker->float(1.5, 5.5, 3);
        $this->assertIsFloat($float);
        $this->assertGreaterThanOrEqual(1.5, $float);
        $this->assertLessThanOrEqual(5.5, $float);
    }

    public function testSeedRepeatability()
    {
        $faker1 = new Faker();
        $faker1->seed(1234);
        $name1 = $faker1->name();
        $email1 = $faker1->email();
        $number1 = $faker1->number(1, 100);

        $faker2 = new Faker();
        $faker2->seed(1234);
        $name2 = $faker2->name();
        $email2 = $faker2->email();
        $number2 = $faker2->number(1, 100);

        $this->assertSame($name1, $name2, 'Seed should make name output deterministic');
        $this->assertSame($email1, $email2, 'Seed should make email output deterministic');
        $this->assertSame($number1, $number2, 'Seed should make number output deterministic');
    }

    public function testArrayOfName()
    {
        $faker = new Faker();
        $faker->seed(2024);
        $names = $faker->arrayOf('name', 5);
        $this->assertIsArray($names);
        $this->assertCount(5, $names);
        foreach ($names as $name) {
            $this->assertIsString($name);
            $this->assertMatchesRegularExpression('/^[A-Za-z]+ [A-Za-z]+$/', $name);
        }
    }

    public function testArrayOfNumber()
    {
        $faker = new Faker();
        $faker->seed(2024);
        $numbers = $faker->arrayOf('number', 3, 10, 20);
        $this->assertIsArray($numbers);
        $this->assertCount(3, $numbers);
        foreach ($numbers as $num) {
            $this->assertIsInt($num);
            $this->assertGreaterThanOrEqual(10, $num);
            $this->assertLessThanOrEqual(20, $num);
        }
    }

    public function testArrayOfDeterminism()
    {
        $faker1 = new Faker();
        $faker1->seed(555);
        $arr1 = $faker1->arrayOf('email', 4);

        $faker2 = new Faker();
        $faker2->seed(555);
        $arr2 = $faker2->arrayOf('email', 4);

        $this->assertSame($arr1, $arr2, 'Seed should make arrayOf deterministic');
    }

    public function testArrayOfThrowsOnInvalidMethod()
    {
        $faker = new Faker();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Method \'notAMethod\' does not exist/');
        $faker->arrayOf('notAMethod', 3);
    }

    public function testUniqueEmailGeneration()
    {
        $faker = new Faker();
        $uniqueFaker = $faker->unique();
        $emails = [];
        for ($i = 0; $i < 200; $i++) {
            $email = $uniqueFaker->email();
            $this->assertIsString($email);
            $this->assertNotContains($email, $emails, 'Email should be unique');
            $emails[] = $email;
        }
    }

    public function testUniqueThrowsWhenExhausted()
    {
        $faker = new Faker();
        $faker->setLocaleData([
            'firstNames' => ['A'],
            'lastNames' => ['B'],
            'domains' => ['x.com'],
        ]);
        $uniqueFaker = $faker->unique();
        $uniqueFaker->email(); // First call OK
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Unable to generate a unique value/');
        $uniqueFaker->email(); // Second call will exhaust pool
    }
}
