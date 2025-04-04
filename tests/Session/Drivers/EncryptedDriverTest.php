<?php

namespace Lightpack\Tests\Session\Drivers;

use Lightpack\Utils\Crypto;
use PHPUnit\Framework\TestCase;
use Lightpack\Session\DriverInterface;
use Lightpack\Session\Drivers\EncryptedDriver;

class EncryptedDriverTest extends TestCase
{
    private EncryptedDriver $driver;
    private DriverInterface $baseDriver;
    private Crypto $crypto;

    protected function setUp(): void
    {
        $this->baseDriver = $this->createMock(DriverInterface::class);
        $this->crypto = new Crypto('test-key-32-characters-exactly!!');
        $this->driver = new EncryptedDriver($this->baseDriver, $this->crypto);
    }

    public function testStartDelegatesToBaseDriver()
    {
        $this->baseDriver->expects($this->once())
            ->method('start')
            ->willReturn(true);

        $this->assertTrue($this->driver->start());
    }

    public function testRegenerateDelegatesToBaseDriver()
    {
        $this->baseDriver->expects($this->once())
            ->method('regenerate')
            ->willReturn(true);

        $this->assertTrue($this->driver->regenerate());
    }

    public function testDestroyDelegatesToBaseDriver()
    {
        $this->baseDriver->expects($this->once())
            ->method('destroy')
            ->willReturn(true);

        $this->assertTrue($this->driver->destroy());
    }

    public function testStartedDelegatesToBaseDriver()
    {
        $this->baseDriver->expects($this->once())
            ->method('started')
            ->willReturn(true);

        $this->assertTrue($this->driver->started());
    }

    public function testSetEncryptsDataBeforeStorage()
    {
        $key = 'test_key';
        $value = ['foo' => 'bar'];

        $this->baseDriver->expects($this->once())
            ->method('set')
            ->with(
                $this->equalTo($key),
                $this->callback(function($encryptedValue) use ($value) {
                    $decrypted = unserialize($this->crypto->decrypt($encryptedValue));
                    return $decrypted == $value;
                })
            );

        $this->driver->set($key, $value);
    }

    public function testGetDecryptsStoredData()
    {
        $key = 'test_key';
        $value = ['foo' => 'bar'];
        $encrypted = $this->crypto->encrypt(serialize($value));

        $this->baseDriver->method('get')
            ->with($key)
            ->willReturn($encrypted);

        $result = $this->driver->get($key);
        $this->assertEquals($value, $result);
    }

    public function testGetAllDecryptsAllStoredData()
    {
        $data = [
            'key1' => ['foo' => 'bar'],
            'key2' => 'test value',
        ];

        $encryptedData = array_map(
            fn($value) => $this->crypto->encrypt(serialize($value)), 
            $data
        );

        $this->baseDriver->method('get')
            ->with(null)
            ->willReturn($encryptedData);

        $result = $this->driver->get();
        $this->assertEquals($data, $result);
    }

    public function testDeleteDelegatesToBaseDriver()
    {
        $key = 'test_key';

        $this->baseDriver->expects($this->once())
            ->method('delete')
            ->with($this->equalTo($key));

        $this->driver->delete($key);
    }

    public function testHandlesNonEncryptedDataGracefully()
    {
        $key = 'test_key';
        $value = 'non_encrypted_value';

        $this->baseDriver->method('get')
            ->with($key)
            ->willReturn($value);

        $result = $this->driver->get($key);
        $this->assertEquals($value, $result);
    }
}
