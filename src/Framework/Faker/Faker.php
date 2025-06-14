<?php

/**
 * Lightpack Minimal Faker: Explicit, No-Magic Data Fake Generator
 */
namespace Lightpack\Faker;

class Faker
{
    public function name(): string
    {
        $first = self::$firstNames[mt_rand(0, count(self::$firstNames)-1)];
        $last = self::$lastNames[mt_rand(0, count(self::$lastNames)-1)];
        return "$first $last";
    }

    public function email(): string
    {
        $user = strtolower(preg_replace('/\s+/', '', $this->name()));
        $domain = self::$domains[mt_rand(0, count(self::$domains)-1)];
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
        for ($i = 0; $i < $words; $i++) {
            $out[] = self::$words[mt_rand(0, count(self::$words)-1)];
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
        return $values[mt_rand(0, count($values)-1)];
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
        $prefix = self::$phonePrefixes[mt_rand(0, count(self::$phonePrefixes)-1)];
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
        $street = self::$streets[mt_rand(0, count(self::$streets)-1)];
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
        return self::$cities[mt_rand(0, count(self::$cities)-1)];
    }

    /**
     * Generate a fake state.
     */
    public function state(): string
    {
        return self::$states[mt_rand(0, count(self::$states)-1)];
    }

    /**
     * Generate a fake country.
     */
    public function country(): string
    {
        return self::$countries[mt_rand(0, count(self::$countries)-1)];
    }

    /**
     * Generate a fake company name.
     */
    public function company(): string
    {
        return self::$companies[mt_rand(0, count(self::$companies)-1)];
    }

    /**
     * Generate a fake URL.
     */
    public function url(): string
    {
        $domain = self::$domains[mt_rand(0, count(self::$domains)-1)];
        $user = strtolower(preg_replace('/\s+/', '', $this->name()));
        return "https://$domain/$user";
    }

    /**
     * Generate a fake username.
     */
    public function username(): string
    {
        $first = strtolower(self::$firstNames[mt_rand(0, count(self::$firstNames)-1)]);
        $last = strtolower(self::$lastNames[mt_rand(0, count(self::$lastNames)-1)]);
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
        $format = $formats[mt_rand(0, count($formats)-1)];
        return preg_replace_callback('/#/', fn() => mt_rand(0,9), $format);
    }

    /**
     * Generate a fake job title.
     */
    public function jobTitle(): string
    {
        return self::$jobTitles[mt_rand(0, count(self::$jobTitles)-1)];
    }

    /**
     * Generate a fake product name.
     */
    public function productName(): string
    {
        return self::$productNames[mt_rand(0, count(self::$productNames)-1)];
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
        [$prefix, $length] = $types[mt_rand(0, count($types)-1)];
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
        for ($i = 0; $i < $words; $i++) {
            $chosen[] = self::$words[mt_rand(0, count(self::$words)-1)];
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

    // --- Static data ---
    protected static array $firstNames = [
        'Amit', 'Priya', 'John', 'Lucy', 'Carlos', 'Fatima', 'Wei', 'Anna', 'Tom', 'Sara',
        'David', 'Maria', 'James', 'Linda', 'Robert', 'Patricia', 'Michael', 'Barbara', 'William', 'Elizabeth',
    ];
    protected static array $lastNames = [
        'Sharma', 'Smith', 'Patel', 'Garcia', 'Wang', 'Kim', 'Kumar', 'Singh', 'Jones', 'Brown',
        'Davis', 'Miller', 'Wilson', 'Moore', 'Taylor', 'Anderson', 'Thomas', 'Jackson', 'White', 'Harris',
    ];
    protected static array $domains = [
        'example.com', 'test.com', 'demo.org', 'mail.com', 'sample.net',
    ];
    protected static array $phonePrefixes = [
        '+91-', '+1-', '+44-', '+81-', '+61-',
    ];
    protected static array $streets = [
        'Main St', 'High St', 'Station Rd', 'Park Ave', 'Church St', 'Maple Ave', 'Oak St', 'Pine Rd', 'Cedar St', 'Elm St',
    ];
    protected static array $cities = [
        'Mumbai', 'Delhi', 'London', 'New York', 'Sydney', 'Tokyo', 'Berlin', 'Paris', 'Toronto', 'San Francisco',
    ];
    protected static array $states = [
        'Maharashtra', 'California', 'New York', 'London', 'Tokyo', 'Berlin', 'Ontario', 'New South Wales', 'Île-de-France', 'Delhi',
    ];
    protected static array $countries = [
        'India', 'USA', 'UK', 'Australia', 'Japan', 'Germany', 'France', 'Canada', 'Brazil', 'Singapore',
    ];
    protected static array $companies = [
        'Acme Corp', 'Globex', 'Initech', 'Umbrella', 'Wayne Enterprises', 'Stark Industries', 'Hooli', 'Massive Dynamic', 'Wonka Industries', 'Cyberdyne Systems',
    ];
    protected static array $jobTitles = [
        'Software Engineer', 'Project Manager', 'Designer', 'Accountant', 'Sales Executive', 'Data Analyst', 'HR Manager', 'Consultant', 'Marketing Lead', 'Support Specialist',
    ];
    protected static array $productNames = [
        'UltraWidget', 'SuperGadget', 'PowerTool', 'EcoBottle', 'SmartLamp', 'Speedster Bike', 'ComfyChair', 'MaxiPhone', 'CleanSweep', 'FitTracker',
    ];
    protected static array $words = [
        'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'sed', 'do',
        'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua', 'ut',
        'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud', 'exercitation', 'ullamco', 'laboris', 'nisi',
    ];
}
