<?php
namespace Lightpack\Faker;

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
        $attempts = 0;
        do {
            $value = $this->faker->$method(...$args);
            $attempts++;
        } while (in_array($value, $this->generated, true) && $attempts < 100);
        if ($attempts >= 100) {
            throw new \RuntimeException("Unable to generate a unique value for $method after 100 attempts.");
        }
        $this->generated[] = $value;
        return $value;
    }
}
