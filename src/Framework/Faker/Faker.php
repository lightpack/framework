<?php

/**
 * Lightpack Minimal Faker: Explicit, No-Magic Data Fake Generator
 */

namespace Lightpack\Faker;

class Faker
{
    protected string $locale = 'en';
    protected array $data = [];

    public function __construct(string $locale = 'en', ?string $customLocalePath = null)
    {
        $this->locale = $locale;
        $this->loadLocaleData($locale, $customLocalePath);
    }

    /**
     * Allow users to set locale data directly (array injection)
     */
    public function setLocaleData(array $data): void
    {
        $this->data = $data;
    }

    protected function loadLocaleData(string $locale, ?string $customLocalePath = null): void
    {
        if ($customLocalePath && file_exists($customLocalePath)) {
            $this->data = require $customLocalePath;
            return;
        }
        $base = __DIR__ . '/Locales/';
        $file = $base . $locale . '.php';
        $default = $base . 'en.php';

        if (file_exists($file)) {
            $this->data = require $file;
        } elseif (file_exists($default)) {
            $this->data = require $default;
            $this->locale = 'en';
        } else {
            throw new \RuntimeException('No locale data found for Faker.');
        }
    }

    protected function getData(string $key): array
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        // fallback to English
        $en = require __DIR__ . '/Locales/en.php';
        return $en[$key] ?? [];
    }
    public function name(): string
    {
        $firstNames = $this->getData('firstNames');
        $lastNames = $this->getData('lastNames');
        $first = $firstNames[mt_rand(0, count($firstNames) - 1)];
        $last = $lastNames[mt_rand(0, count($lastNames) - 1)];
        return "$first $last";
    }

    public function email(): string
    {
        $user = strtolower(preg_replace('/\s+/', '', $this->name()));
        $domains = $this->getData('domains');
        $domain = $domains[mt_rand(0, count($domains) - 1)];
        return "$user@{$domain}";
    }

    public function number(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return mt_rand($min, $max);
    }

    public function float(float $min = 0, float $max = 1, int $decimals = 2): float
    {
        $factor = pow(10, $decimals);
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $decimals);
    }

    public function bool(): bool
    {
        return (bool)mt_rand(0, 1);
    }

    public function sentence(int $words = 8): string
    {
        $out = [];
        $wordList = $this->getData('words');
        for ($i = 0; $i < $words; $i++) {
            $out[] = $wordList[mt_rand(0, count($wordList) - 1)];
        }
        $str = ucfirst(implode(' ', $out)) . '.';
        return $str;
    }

    public function paragraph(int $sentences = 3): string
    {
        $out = [];
        for ($i = 0; $i < $sentences; $i++) {
            $out[] = $this->sentence(mt_rand(7, 15));
        }
        return implode(' ', $out);
    }

    public function date(string $format = 'Y-m-d'): string
    {
        $timestamp = mt_rand(strtotime('-10 years'), time());
        return date($format, $timestamp);
    }

    public function uuid(): string
    {
        $data = '';
        for ($i = 0; $i < 16; $i++) {
            $data .= chr(mt_rand(0, 255));
        }
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // version 4
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // variant
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function enum(array $values)
    {
        return $values[mt_rand(0, count($values) - 1)];
    }

    /**
     * Set the random seed for deterministic fake data.
     */
    public function seed(int $seed): void
    {
        mt_srand($seed);
    }

    /**
     * Generate a fake phone number.
     */
    public function phone(): string
    {
        $prefixes = $this->getData('phonePrefixes');
        $prefix = $prefixes[mt_rand(0, count($prefixes) - 1)];
        $number = '';
        for ($i = 0; $i < 8; $i++) {
            $number .= mt_rand(0, 9);
        }
        return $prefix . $number;
    }

    /**
     * Generate a fake address.
     */
    public function address(): string
    {
        $num = mt_rand(1, 9999);
        $streets = $this->getData('streets');
        $street = $streets[mt_rand(0, count($streets) - 1)];
        $city = $this->city();
        $state = $this->state();
        $country = $this->country();
        return "$num $street, $city, $state, $country";
    }

    /**
     * Generate a fake city.
     */
    public function city(): string
    {
        $cities = $this->getData('cities');
        return $cities[mt_rand(0, count($cities) - 1)];
    }

    /**
     * Generate a fake state.
     */
    public function state(): string
    {
        $states = $this->getData('states');
        return $states[mt_rand(0, count($states) - 1)];
    }

    /**
     * Generate a fake country.
     */
    public function country(): string
    {
        $countries = $this->getData('countries');
        return $countries[mt_rand(0, count($countries) - 1)];
    }

    /**
     * Generate a fake company name.
     */
    public function company(): string
    {
        $companies = $this->getData('companies');
        return $companies[mt_rand(0, count($companies) - 1)];
    }

    /**
     * Generate a fake URL.
     */
    public function url(): string
    {
        $domains = $this->getData('domains');
        $domain = $domains[mt_rand(0, count($domains) - 1)];
        $user = strtolower(preg_replace('/\s+/', '', $this->name()));
        return "https://$domain/$user";
    }

    /**
     * Generate a fake username.
     */
    public function username(): string
    {
        $firstNames = $this->getData('firstNames');
        $lastNames = $this->getData('lastNames');
        $first = strtolower($firstNames[mt_rand(0, count($firstNames) - 1)]);
        $last = strtolower($lastNames[mt_rand(0, count($lastNames) - 1)]);
        $num = mt_rand(1, 99);
        return "$first.$last$num";
    }

    /**
     * Generate a fake IPv4 address.
     */
    public function ipv4(): string
    {
        return mt_rand(1, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255);
    }

    /**
     * Generate a fake IPv6 address.
     */
    public function ipv6(): string
    {
        $segments = [];
        for ($i = 0; $i < 8; $i++) {
            $segments[] = dechex(mt_rand(0, 0xffff));
        }
        return implode(':', $segments);
    }

    /**
     * Generate a fake hex color code.
     */
    public function hexColor(): string
    {
        return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
    }

    /**
     * Generate a random password.
     */
    public function password(int $length = 12): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $pass = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $pass .= $chars[mt_rand(0, $max)];
        }
        return $pass;
    }

    /**
     * Generate a random age between min and max.
     */
    public function age(int $min = 0, int $max = 100): int
    {
        return mt_rand($min, $max);
    }

    /**
     * Generate a fake date of birth string (Y-m-d) for a given age range.
     */
    public function dob(int $minAge = 18, int $maxAge = 65): string
    {
        $age = $this->age($minAge, $maxAge);
        $timestamp = strtotime("-{$age} years");
        // Add random days within a year for more realism
        $timestamp -= mt_rand(0, 364) * 86400;
        return date('Y-m-d', $timestamp);
    }

    /**
     * Generate a fake zip/postal code.
     */
    public function zipCode(): string
    {
        $formats = ['#####', '######', '### ###', '#####-####'];
        $format = $formats[mt_rand(0, count($formats) - 1)];
        return preg_replace_callback('/#/', fn() => mt_rand(0, 9), $format);
    }

    /**
     * Generate a fake job title.
     */
    public function jobTitle(): string
    {
        $jobTitles = $this->getData('jobTitles');
        return $jobTitles[mt_rand(0, count($jobTitles) - 1)];
    }

    /**
     * Generate a fake product name.
     */
    public function productName(): string
    {
        $productNames = $this->getData('productNames');
        return $productNames[mt_rand(0, count($productNames) - 1)];
    }

    /**
     * Generate a plausible fake credit card number (not Luhn-valid).
     */
    public function creditCardNumber(): string
    {
        // Choose a card type and its prefix/length
        $types = [
            ['4', 16],      // Visa, always 16 digits, starts with 4.
            ['5', 16],      // MasterCard, always 16 digits, starts with 5.
            ['34', 15],     // Amex, always 15 digits, starts with 34 or 37
            ['37', 15],     // Amex, always 15 digits, starts with 37
        ];
        [$prefix, $length] = $types[mt_rand(0, count($types) - 1)];
        $number = $prefix;
        while (strlen($number) < $length) {
            $number .= mt_rand(0, 9);
        }
        // Format as groups of 4 digits
        return trim(chunk_split($number, 4, ' '));
    }

    /**
     * Generate a random latitude.
     */
    public function latitude(): float
    {
        return round(mt_rand(-90000000, 90000000) / 1000000, 6);
    }

    /**
     * Generate a random longitude.
     */
    public function longitude(): float
    {
        return round(mt_rand(-180000000, 180000000) / 1000000, 6);
    }

    /**
     * Generate a numeric OTP (One-Time Password) code.
     *
     * @param int $digits Number of digits (default 6)
     * @return string
     */
    public function otp(int $digits = 6): string
    {
        $min = (int)str_pad('1', $digits, '0');
        $max = (int)str_pad('', $digits, '9');
        return str_pad((string)mt_rand($min, (int)$max), $digits, '0', STR_PAD_LEFT);
    }


    /**
     * Generate a slug from random words.
     */
    public function slug(int $words = 3): string
    {
        $chosen = [];
        $wordList = $this->getData('words');
        for ($i = 0; $i < $words; $i++) {
            $chosen[] = $wordList[mt_rand(0, count($wordList) - 1)];
        }
        return strtolower(implode('-', $chosen));
    }

    /**
     * Generate a fake price with optional currency.
     */
    public function price(float $min = 1, float $max = 9999, string $currency = '$'): string
    {
        $amount = $this->float($min, $max, 2);
        return $currency . number_format($amount, 2);
    }

    /**
     * Generate an array of fake values using a specified generator method.
     *
     * Example:
     *   $faker->arrayOf('name', 5); // [ 'John Doe', 'Jane Smith', ... ]
     *   $faker->arrayOf('number', 3, 1, 100); // [42, 17, 99]
     *
     * @param string $method Name of the generator method (e.g., 'name', 'email', 'number')
     * @param int $count Number of items to generate
     * @param mixed ...$args Arguments to pass to the generator method
     * @return array
     */
    public function arrayOf(string $method, int $count, ...$args): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $this->$method(...$args);
        }
        return $result;
    }
}
