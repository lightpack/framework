<?php

use PHPUnit\Framework\TestCase;
use Lightpack\Database\Schema\Schema;
use Lightpack\Database\Schema\Table;
use Lightpack\Container\Container;
use Lightpack\Webhook\WebhookController;
use Lightpack\Webhook\WebhookEvent;
use Lightpack\Webhook\BaseWebhookHandler;
use Lightpack\Http\Response;

// mock config
if (!function_exists('config')) {
    function config($key) {
        return new \Lightpack\Config\Config;
    }
}

// mock request
if (!function_exists('request')) {
    function request() {
        return new \Lightpack\Http\Request();
    }
}

// mock response()
if (!function_exists('response')) {
    function response($status = 200, $headers = [], $body = '') {
        return new \Lightpack\Http\Response($status, $headers, $body);
    }
}

class WebhookReceiverTest extends TestCase
{
    private $db;
    private $schema;
    private $container;
    private $testConfig = [
        'webhook' => [
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
        $this->container->register('config', fn() => fn($key) => $this->testConfig[$key] ?? []);
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
        // Simulate Lightpack's request() global
        $_SERVER['HTTP_' . str_replace('-', '_', strtoupper($header))] = $signature;
        $_POST = json_decode($payload, true) ?? [];
        // Optionally set request()->getRawBody() if needed (depends on Lightpack implementation)
        $GLOBALS['__RAW_BODY__'] = $payload;
    }

    public function testValidWebhookIsProcessedAndStored()
    {
        $payload = json_encode(['id' => 'evt_123', 'foo' => 'bar']);
        $signature = hash_hmac('sha256', $payload, 'testsecret');
        $this->setRequest($payload, $signature);
        $controller = new WebhookController();
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
        $controller = new WebhookController();
        $this->expectException(\Lightpack\Exceptions\HttpException::class);
        $controller->handle('dummy');
    }

    public function testDuplicateEventIsIdempotent()
    {
        $payload = json_encode(['id' => 'evt_789']);
        $signature = hash_hmac('sha256', $payload, 'testsecret');
        $this->setRequest($payload, $signature);
        $controller = new WebhookController();
        $controller->handle('dummy');
        // Second call with same event_id
        $this->setRequest($payload, $signature);
        $this->expectException(\Lightpack\Exceptions\HttpException::class);
        $controller->handle('summy');
    }

    public function testEventStatusIsFailedOnException()
    {
        $payload = json_encode(['id' => 'evt_fail']);
        $signature = hash_hmac('sha256', $payload, 'testsecret');
        $this->setRequest($payload, $signature);
        $this->testConfig['webhook']['dummy']['handler'] = ExceptionThrowingWebhookHandler::class;
        $controller = new WebhookController();
        try {
            $controller->handle('dummy');
        } catch (\Throwable $e) {
            $event = WebhookEvent::query()->where('event_id', 'evt_fail')->one();
            $this->assertEquals('failed', $event->status);
        }
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