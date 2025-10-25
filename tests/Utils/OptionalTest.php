<?php

namespace Lightpack\Tests\Utils;

use PHPUnit\Framework\TestCase;

class OptionalTest extends TestCase
{
    public function testOptionalReturnsNullForNullValue()
    {
        $result = optional(null);
        
        $this->assertNotNull($result); // Returns null object, not actual null
        $this->assertEquals('', (string)$result->property);
        $this->assertEquals('', (string)$result->method());
    }
    
    public function testOptionalReturnsValueForNonNullValue()
    {
        $user = new \stdClass();
        $user->name = 'John';
        
        $result = optional($user);
        
        $this->assertEquals($user, $result);
        $this->assertEquals('John', $result->name);
    }
    
    public function testOptionalSafePropertyAccess()
    {
        $user = null;
        
        // No error even though $user is null
        $name = optional($user)->name;
        
        // Returns null object (for chaining), converts to empty string
        $this->assertEquals('', (string)$name);
    }
    
    public function testOptionalSafeMethodCall()
    {
        $user = null;
        
        // No error even though $user is null
        $email = optional($user)->getEmail();
        
        // Returns null object
        $this->assertEquals('', (string)$email);
    }
    
    public function testOptionalWithCallback()
    {
        $user = new \stdClass();
        $user->name = 'John Doe';
        
        // With callback
        $result = optional($user, function($u) {
            return strtoupper($u->name);
        });
        
        $this->assertEquals('JOHN DOE', $result);
    }
    
    public function testOptionalWithCallbackOnNull()
    {
        $user = null;
        
        // Callback not executed on null
        $result = optional($user, function($u) {
            return strtoupper($u->name);
        });
        
        $this->assertNotNull($result); // Returns null object
        $this->assertEquals('', (string)$result->anything);
    }
    
    public function testOptionalChainedPropertyAccess()
    {
        $user = null;
        
        // Deep chaining without errors
        $city = optional($user)->profile->address->city;
        
        // Returns null object that chains
        $this->assertEquals('', (string)$city);
    }
    
    public function testOptionalWithRealObject()
    {
        $user = new class {
            public $name = 'Jane';
            public $profile;
            
            public function __construct()
            {
                $this->profile = new class {
                    public $email = 'jane@example.com';
                };
            }
            
            public function getName()
            {
                return $this->name;
            }
        };
        
        // Access properties
        $this->assertEquals('Jane', optional($user)->name);
        $this->assertEquals('jane@example.com', optional($user)->profile->email);
        
        // Call methods
        $this->assertEquals('Jane', optional($user)->getName());
    }
    
    public function testOptionalWithNullInChain()
    {
        $user = new \stdClass();
        $user->name = 'John';
        $user->profile = null;
        
        // Profile is null, should not error
        $email = optional($user->profile)->email;
        
        // Returns null object
        $this->assertEquals('', (string)$email);
    }
    
    public function testOptionalToString()
    {
        $result = optional(null);
        
        // Should convert to empty string
        $this->assertEquals('', (string) $result);
    }
    
    public function testOptionalWithArray()
    {
        $data = ['name' => 'John', 'age' => 30];
        
        $result = optional($data);
        
        $this->assertEquals($data, $result);
    }
    
    public function testOptionalWithCallbackTransformation()
    {
        $user = new \stdClass();
        $user->firstName = 'John';
        $user->lastName = 'Doe';
        
        $fullName = optional($user, function($u) {
            return $u->firstName . ' ' . $u->lastName;
        });
        
        $this->assertEquals('John Doe', $fullName);
    }
    
    public function testOptionalRealWorldScenario()
    {
        // Simulate fetching user from database
        $getUserById = function($id) {
            return $id === 1 ? (object)['name' => 'John', 'email' => 'john@example.com'] : null;
        };
        
        // User exists
        $user1 = $getUserById(1);
        $name1 = optional($user1)->name;
        $this->assertEquals('John', $name1);
        
        // User doesn't exist
        $user2 = $getUserById(999);
        $name2 = optional($user2)->name;
        $this->assertEquals('', (string)$name2);
    }
    
    public function testOptionalWithNestedObjects()
    {
        $order = new \stdClass();
        $order->customer = new \stdClass();
        $order->customer->address = new \stdClass();
        $order->customer->address->city = 'New York';
        
        // Access nested property
        $city = optional($order)->customer->address->city;
        $this->assertEquals('New York', $city);
        
        // Access with null in chain
        $order->customer->address = null;
        $city = optional($order->customer->address)->city;
        $this->assertEquals('', (string)$city);
    }
    
    public function testOptionalWithMethodChaining()
    {
        $user = new class {
            public function getProfile()
            {
                return null;
            }
        };
        
        // Method returns null, chaining should not error
        $email = optional($user->getProfile())->email;
        
        $this->assertEquals('', (string)$email);
    }
    
    public function testOptionalPreventsFatalErrors()
    {
        $data = null;
        
        // These would normally cause fatal errors
        $result1 = optional($data)->property;
        $result2 = optional($data)->method();
        $result3 = optional($data)->nested->property;
        
        // All return null object
        $this->assertEquals('', (string)$result1);
        $this->assertEquals('', (string)$result2);
        $this->assertEquals('', (string)$result3);
    }
}
