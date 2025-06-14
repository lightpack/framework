<?php

namespace Lightpack\Faker;

/**
 * Proxy for unique fake data generation.
 *
 * @method string name()
 * @method string email()
 * @method string username()
 * @method string phone()
 * @method string address()
 * @method string city()
 * @method string state()
 * @method string country()
 * @method string company()
 * @method string jobTitle()
 * @method string productName()
 * @method string url()
 * @method string ipv4()
 * @method string ipv6()
 * @method string hexColor()
 * @method string password(int $length = 10)
 * @method int age(int $min = 18, int $max = 65)
 * @method string dob(int $min = 18, int $max = 65)
 * @method string zipCode()
 * @method float latitude()
 * @method float longitude()
 * @method string slug(int $words = 2)
 * @method string price(int $min = 1, int $max = 100, string $currency = '$')
 * @method string creditCardNumber()
 * @method string otp()
 * @method string sentence(int $words = 6)
 * @method string paragraph(int $sentences = 3)
 * @method string date(string $format = 'Y-m-d')
 * @method string uuid()
 * @method mixed enum(array $values)
 * @method bool bool()
 * @method int number(int $min, int $max)
 * @method float float(float $min, float $max, int $decimals = 2)
 * @method array arrayOf(string $method, int $count, ...$args)
 * @method void seed(int $seed)
 * @method void setLocaleData(array $data)
 */

class UniqueFaker
{
    private Faker $faker;
    private array $generated = [];

    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
    }

    public function __call($method, $args)
    {
        $maxAttempts = 100;
        $attempts = 0;

        // Try to generate a unique value up to $maxAttempts times
        do {
            $value = $this->faker->$method(...$args);
            $attempts++;

            // If the value is unique, return it immediately
            if (!in_array($value, $this->generated, true)) {
                $this->generated[] = $value;
                return $value;
            }
        } while ($attempts < $maxAttempts);

        // If we reach here, all attempts failed
        throw new \RuntimeException(
            "Unable to generate a unique value for '{$method}' after {$maxAttempts} attempts."
        );
    }
}
