<?php

use Lightpack\Faker\Faker;
use Lightpack\Factory\Factory;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testMakeReturnsArrayWithExpectedKeys()
    {
        $factory = new UserFactory();
        $user = $factory->make();
        $this->assertIsArray($user);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('address', $user);
        $this->assertArrayHasKey('created_at', $user);
    }

    public function testMakeWithOverrides()
    {
        $factory = new UserFactory();
        $user = $factory->make(['email' => 'custom@example.com']);
        $this->assertSame('custom@example.com', $user['email']);
    }

    public function testManyReturnsCorrectCount()
    {
        $factory = new UserFactory();
        $users = $factory->many(5);
        $this->assertCount(5, $users);
        foreach ($users as $user) {
            $this->assertIsArray($user);
        }
    }

    public function testManyWithOverrides()
    {
        $factory = new UserFactory();
        $users = $factory->many(3, ['address' => '123 Main St']);
        $this->assertCount(3, $users);
        foreach ($users as $user) {
            $this->assertSame('123 Main St', $user['address']);
        }
    }
}

// UserFactory
class UserFactory extends Factory
{
    protected function definition(): array
    {
        $faker = new Faker();
        return [
            'name' => $faker->name(),
            'email' => $faker->unique()->email(),
            'address' => $faker->address(),
            'created_at' => $faker->date('Y-m-d H:i:s'),
        ];
    }
}
