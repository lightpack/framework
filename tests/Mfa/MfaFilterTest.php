<?php

namespace Lightpack\Filters;

global $testMocks;
function auth()    { global $testMocks; return $testMocks['auth']   ?? null; }
function session() { global $testMocks; return $testMocks['session']?? null; }
function config($key) { global $testMocks; return $testMocks['config'][$key] ?? null; }
function app($service) { global $testMocks; return $testMocks['app'][$service] ?? null; }
function redirect()
{
    // Return a mock object with a route() method
    return new class {
        public $routeCalled = false;
        public $routeName = null;
        public function route($name)
        {
            $this->routeCalled = true;
            $this->routeName = $name;
            return $this;
        }
    };
}

namespace Lightpack\Tests\Mfa;

use PHPUnit\Framework\TestCase;
use Lightpack\Filters\MfaFilter;
use Lightpack\Http\Request;
use Lightpack\Http\Response;

class MfaFilterTest extends TestCase
{
    protected $filter;
    protected $request;
    protected $response;
    protected $user;

    protected function setUp(): void
    {
        $this->filter = new MfaFilter();
        $this->request = $this->createMock(Request::class);
        $this->response = $this->createMock(Response::class);
        $this->user = new class {
            public $mfa_enabled = true;
            public function sendMfa()
            {
                $this->mfaSent = true;
            }
        };
    }

    private function setTestMocks(array $mocks) {
        global $testMocks;
        $testMocks = $mocks;
    }

    public function testDoesNotRedirectIfUserNotAuthenticated()
    {
        // Simulate no user
        $this->setTestMocks([
            'auth' => new class { public function user() { return null; } },
        ]);
        $result = $this->filter->before($this->request);
        $this->assertNull($result);
    }

    public function testDoesNotRedirectIfMfaAlreadyPassed()
    {
        // Simulate user and session flag
        $this->setTestMocks([
            'auth' => new class { public function user() { return (object)['mfa_enabled' => true]; } },
            'session' => new class { public function get($key) { return $key === 'mfa_passed' ? true : null; } },
        ]);
        $result = $this->filter->before($this->request);
        $this->assertNull($result);
    }

    public function testRedirectsAndSendsMfaIfEnforcedOrEnabled()
    {
        // Simulate user and session
        $emailFactor = new class { public function send($user) {} };
        $mfaMock = new class($emailFactor) {
            private $factor;
            public function __construct($factor) { $this->factor = $factor; }
            public function getFactor($type) { return $this->factor; }
        };
        $this->setTestMocks([
            'auth' => new class {
                public function user() {
                    return new class { public $mfa_enabled = true; public function sendMfa() {} };
                }
            },
            'session' => new class { public function get($key) { return null; } },
            'config' => ['mfa.enforce' => true],
            'app' => ['mfa' => $mfaMock],
        ]);
        $result = $this->filter->before($this->request);
        // Should be a redirect response (simulate by checking for not null)
        $this->assertNotNull($result);
    }
}
