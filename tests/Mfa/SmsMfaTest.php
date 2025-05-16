<?php

namespace Lightpack\Tests\Mfa;

use PHPUnit\Framework\TestCase;
use Lightpack\Mfa\Factor\SmsMfa;
use Lightpack\Config\Config;
use Lightpack\Cache\Cache;
use Lightpack\Utils\Otp;
use Lightpack\Auth\Models\AuthUser;
use Lightpack\Sms\Sms;
use Lightpack\Container\Container;

class SmsMfaTest extends TestCase
{
    protected $cache;
    protected $config;
    protected $otp;
    protected $sms;
    protected $user;
    protected $factor;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(Cache::class);
        $this->config = $this->createMock(Config::class);
        $this->otp = new Otp();
        $this->sms = $this->createMock(Sms::class);
        $this->user = new AuthUser();
        $this->user->id = 42;
        $this->user->phone = '+1234567890';
        $this->factor = new SmsMfa($this->cache, $this->config, $this->otp, $this->sms);

        Container::getInstance()->register('config', fn() => $this->config);
        Container::getInstance()->register('cache', fn() => $this->cache);
    }

    public function testSendSetsCodeInCache()
    {
        $this->config->method('get')
            ->will($this->returnValueMap([
                ['mfa.sms.code_length', 6, 6],
                ['mfa.sms.code_type', 'numeric', 'numeric'],
                ['mfa.sms.ttl', null, 300],
                ['mfa.sms.ttl', 300, 300],
                ['mfa.sms.bypass_code', null, null],
                ['mfa.sms.message', 'Your verification code is: {code}', 'Your verification code is: {code}'],
                ['mfa.sms.resend_max', 1, 1],
                ['mfa.sms.resend_interval', 10, 10],
            ]));

        // expect two cache set() calls in order:
        $this->cache->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                // First: the limiter key (for rate limiting)
                [
                    $this->equalTo('limiter:mfa_sms_resend_42'),
                    $this->equalTo(1),
                    $this->equalTo(10),
                    $this->equalTo(false)
                ],
                // Second: the actual MFA code key
                [
                    $this->equalTo('mfa_sms_42'),
                    $this->isType('string'),
                    $this->equalTo(300)
                ]
            );
        $this->sms->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo('+1234567890'),
                $this->stringContains('Your verification code is:'),
                $this->isType('array')
            );
        $this->factor->send($this->user);
    }

    public function testValidateReturnsTrueAndDeletesCodeOnCorrectInput()
    {
        $this->cache->method('get')->willReturn('123456');
        $this->cache->expects($this->once())->method('delete')->with('mfa_sms_42');
        $result = $this->factor->validate($this->user, '123456');
        $this->assertTrue($result);
    }

    public function testValidateReturnsFalseOnWrongInput()
    {
        $this->cache->method('get')->willReturn('123456');
        $result = $this->factor->validate($this->user, '000000');
        $this->assertFalse($result);
    }

    public function testValidateReturnsFalseOnEmptyInput()
    {
        $result = $this->factor->validate($this->user, '');
        $this->assertFalse($result);
    }

    public function testGenerateCodeUsesBypassCodeFromConfig()
    {
        $this->config->method('get')
            ->will($this->returnValueMap([
                ['mfa.sms.bypass_code', null, '999999'],
                ['mfa.sms.code_length', 6, 6],
                ['mfa.sms.code_type', 'numeric', 'numeric'],
            ]));
        $proxy = new SmsMfaTestProxy($this->cache, $this->config, $this->otp, $this->sms);
        $code = $proxy->publicGenerateCode($this->user);
        $this->assertEquals('999999', $code);
    }
}

// Proxy class for testing protected methods
class SmsMfaTestProxy extends SmsMfa {
    public function publicGenerateCode($user = null) {
        return $this->generateCode($user);
    }
}
