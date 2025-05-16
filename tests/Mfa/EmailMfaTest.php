<?php

namespace Lightpack\Tests\Mfa;

// Dummy mailer class for tests
class Mail {
    public function dispatch(...$args) { /* no-op for test */ }
}

use PHPUnit\Framework\TestCase;
use Lightpack\Mfa\Factor\EmailMfa;
use Lightpack\Config\Config;
use Lightpack\Cache\Cache;
use Lightpack\Utils\Otp;
use Lightpack\Auth\Models\AuthUser;
use Lightpack\Container\Container;

class EmailMfaTest extends TestCase
{
    protected $cache;
    protected $config;
    protected $otp;
    protected $user;
    protected $factor;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);
        $this->config = $this->createMock(Config::class);
        $this->otp = new Otp();
        $this->user = new AuthUser();
        $this->user->id = 42;
        $this->user->email = 'user@example.com';
        $this->factor = new EmailMfa($this->cache, $this->config, $this->otp);

        // Patch the IoC container so app('config') returns our config mock
        Container::getInstance()->register('config', fn() => $this->config);
    }

    public function testSendSetsCodeInCache()
    {
        $this->config->method('get')
            ->will($this->returnValueMap([
                ['mfa.email.code_length', 6, 6],
                ['mfa.email.code_type', 'numeric', 'numeric'],
                ['mfa.email.ttl', null, 300],
                ['mfa.email.bypass_code', null, null],
                ['mfa.email.queue', 'default', 'default'],
                ['mfa.email.mailer', null, 'Lightpack\\Tests\\Mfa\\Mail'],
            ]));

        $this->cache->expects($this->once())
            ->method('set')
            ->with(
                'mfa_email_42',
                $this->callback(function($code) {
                    return is_string($code) && strlen($code) === 6;
                }),
                300
            );

        $this->factor->send($this->user);
    }

    public function testValidateReturnsTrueAndDeletesCodeOnCorrectInput()
    {
        $this->cache->method('get')->willReturn('123456');
        $this->cache->expects($this->once())->method('delete')->with('mfa_email_42');
        $result = $this->factor->validate($this->user, '123456');
        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseOnWrongInput()
    {
        $this->cache->method('get')->willReturn('654321');
        $this->cache->expects($this->never())->method('delete');
        $result = $this->factor->validate($this->user, '123456');
        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseOnEmptyInput()
    {
        $result = $this->factor->validate($this->user, null);
        $this->assertFalse($result);
    }

    public function testGenerateCodeUsesBypassCodeFromConfig()
    {
        $this->config->method('get')
            ->will($this->returnValueMap([
                ['mfa.email.bypass_code', null, '999999'],
                ['mfa.email.code_length', 6, 6],
                ['mfa.email.code_type', 'numeric', 'numeric'],
            ]));
        $code = (new class($this->cache, $this->config, $this->otp) extends EmailMfa {
            public function publicGenerateCode() { return $this->generateCode(); }
        })->publicGenerateCode();
        $this->assertEquals('999999', $code);
    }
}
