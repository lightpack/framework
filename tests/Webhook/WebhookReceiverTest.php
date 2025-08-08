<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Container\Container;
use Lightpack\Webhook\WebhookController;
use Lightpack\Webhook\WebhookEvent;
use Lightpack\Webhook\BaseWebhookHandler;
use Lightpack\Http\Response;

class WebhookReceiverTest extends TestCase
{
    private $db;
    private $schema;
    private $container;
    private $testConfig = [
        'webhooks' => [
            'dummy' => [
                'secret' => 'testsecret',
                'algo' => 'hmac',
                'id' => 'id',
                'handler' => DummyWebhookHandler::class,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        // Setup test DB connection
        $config = require __DIR__ . '/../Database/tmp/mysql.config.php';
        $this->db = new \Lightpack\Database\Adapters\Mysql($config);
        $this->schema = new Schema($this->db);
        $this->container = Container::getInstance();
        $this->container->register('db', fn() => $this->db);
        $this->container->register('logger', function () {
            return new class {
                public function error($message, $context = []) {}
                public function critical($message, $context = []) {}
            };
        });

        $this->createWebhookEventsTable();

        // Ensure DIR_CONFIG is defined for Config loading
        if (!defined('DIR_CONFIG')) {
            define('DIR_CONFIG', __DIR__ . '/tmp');
        }
    }

    protected function tearDown(): void
    {
        $this->schema->dropTable('webhook_events');
        $this->db = null;
        parent::tearDown();
    }

    private function createWebhookEventsTable()
    {
        $this->schema->createTable('webhook_events', function(Table $table) {
            $table->id();
            $table->varchar('provider', 64);
            $table->varchar('event_id', 128)->nullable();
            $table->column('payload')->type('text');
            $table->column('headers')->type('text')->nullable();
            $table->varchar('status', 32)->default('pending');
            $table->datetime('received_at')->default('CURRENT_TIMESTAMP');
            $table->unique(['provider', 'event_id']);
        });
    }

    private function setRequest($payload, $signature, $header = 'X-Webhook-Signature')
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))] = $signature;
        $_POST = json_decode($payload, true);
        $_SERVER['X_LIGHTPACK_RAW_INPUT'] = $payload;
    }

    private function getWebhookControllerInstance()
    {
        $config = new \Lightpack\Config\Config();
        $config->set('webhooks', $this->testConfig['webhooks']);
        $request = new \Lightpack\Http\Request();
        $response = new \Lightpack\Http\Response();

        return new \Lightpack\Webhook\WebhookController($config, $request, $response);
    }

    public function testValidWebhookIsProcessedAndStored()
    {
        $payload = json_encode(['id' => 'evt_123', 'foo' => 'bar']);
        $signature = hash_hmac('sha256', $payload, 'testsecret');
        $this->setRequest($payload, $signature);
        $controller = $this->getWebhookControllerInstance();
        $response = $controller->handle('dummy');
        $this->assertEquals(200, $response->getStatus());
        $event = WebhookEvent::query()->where('event_id', 'evt_123')->one();
        $this->assertNotNull($event);
        $this->assertEquals('processed', $event->status);
    }

    public function testSignatureVerificationFails()
    {
        $payload = json_encode(['id' => 'evt_456']);
        $this->setRequest($payload, 'invalidsig');
        $controller = $this->getWebhookControllerInstance();
        $this->expectException(\Lightpack\Exceptions\HttpException::class);
        $controller->handle('dummy');
    }

    public function testDuplicateEventIsIdempotent()
    {
        $payload = json_encode(['id' => 'evt_789']);
        $signature = hash_hmac('sha256', $payload, 'testsecret');
        $this->setRequest($payload, $signature);
        $controller = $this->getWebhookControllerInstance();
        $controller->handle('dummy');
        // Second call with same event_id
        $this->setRequest($payload, $signature);
        $this->expectException(\Lightpack\Exceptions\HttpException::class);
        $controller->handle('dummy');
    }

    public function testEventStatusIsFailedOnException()
    {
        $payload = json_encode(['id' => 'evt_fail']);
        $signature = hash_hmac('sha256', $payload, 'testsecret');
        $this->setRequest($payload, $signature);
        $this->testConfig['webhooks']['dummy']['handler'] = ExceptionThrowingWebhookHandler::class;
        $controller = $this->getWebhookControllerInstance();
        try {
            $controller->handle('dummy');
        } catch (\Throwable $e) {
            $event = WebhookEvent::query()->where('event_id', 'evt_fail')->one();
            $this->assertEquals('failed', $event->status);
        }
    }

    public function testUnknownProviderReturns404()
    {
        $payload = json_encode(['id' => 'evt_unknown']);
        $signature = hash_hmac('sha256', $payload, 'testsecret');
        $this->setRequest($payload, $signature);
        $controller = $this->getWebhookControllerInstance();
        $response = $controller->handle('not_in_config');
        $this->assertEquals(404, $response->getStatus());
        $this->assertStringContainsString('Unknown or unconfigured provider', $response->getBody());
    }

    public function testMissingEventIdIsStoredWithNull()
    {
        $payload = json_encode(['foo' => 'bar']); // No 'id' field
        $signature = hash_hmac('sha256', $payload, 'testsecret');
        $this->setRequest($payload, $signature);
        $controller = $this->getWebhookControllerInstance();
        $response = $controller->handle('dummy');
        $this->assertEquals(200, $response->getStatus());

        // Assert event is stored with event_id = null
        $event = WebhookEvent::query()->one();
        $this->assertNotNull($event);
        $this->assertNull($event->event_id);

        // Assert that another event with null event_id can be stored (no idempotency)
        $this->setRequest($payload, $signature);
        $controller->handle('dummy');
        $count = WebhookEvent::query()->count();
        $this->assertGreaterThan(1, $count);
    }

    public function testMalformedPayloadFailsGracefully()
    {
        $payload = '{invalid_json:';
        $signature = hash_hmac('sha256', $payload, 'testsecret');
        $this->setRequest($payload, $signature);
        $controller = $this->getWebhookControllerInstance();
        $this->expectException(\Throwable::class); // Could be a decode or input error
        $controller->handle('dummy');
    }

    public function testMultiProviderConfigIsolation()
    {
        // Add a second provider
        $this->testConfig['webhooks']['github'] = [
            'secret' => 'othersecret',
            'algo' => 'hmac',
            'id' => 'delivery',
            'handler' => DummyWebhookHandler::class,
        ];
        // Stripe (dummy)
        $payload1 = json_encode(['id' => 'evt_stripe']);
        $signature1 = hash_hmac('sha256', $payload1, 'testsecret');
        $this->setRequest($payload1, $signature1);
        $controller = $this->getWebhookControllerInstance();
        $response1 = $controller->handle('dummy');
        $this->assertEquals(200, $response1->getStatus());
        // GitHub
        $payload2 = json_encode(['delivery' => 'evt_github']);
        $signature2 = hash_hmac('sha256', $payload2, 'othersecret');
        $this->setRequest($payload2, $signature2);
        $controller = $this->getWebhookControllerInstance();
        $response2 = $controller->handle('github');
        $this->assertEquals(200, $response2->getStatus());
        // Check both events stored
        $event1 = WebhookEvent::query()->where('event_id', 'evt_stripe')->one();
        $event2 = WebhookEvent::query()->where('event_id', 'evt_github')->one();
        $this->assertNotNull($event1);
        $this->assertNotNull($event2);
    }
}


// Dummy handler for successful processing
class DummyWebhookHandler extends BaseWebhookHandler
{
    public function handle(): Response
    {
        return new Response(200, [], 'OK');
    }
}

// Dummy handler that throws in handle()
class ExceptionThrowingWebhookHandler extends BaseWebhookHandler
{
    public function handle(): Response
    {
        throw new \Exception('Handler failed');
    }
}