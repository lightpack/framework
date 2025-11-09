<?php

namespace Lightpack\Console\Views\Migrations;

class WebhooksView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

use Lightpack\Database\Schema\Table;
use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('webhook_events', function (Table $table) {
            $table->id();
            $table->varchar('provider', 64);
            $table->varchar('event_id', 128)->nullable();
            $table->column('payload')->type('text');
            $table->column('headers')->type('text')->nullable();
            $table->varchar('status', 32)->default('pending');
            $table->datetime('received_at')->default('CURRENT_TIMESTAMP');
            $table->unique(['provider', 'event_id']);
            $table->index('status');
            $table->index('received_at');
        });
    }

    public function down(): void
    {
        $this->drop('webhook_events');
    }
};
TEMPLATE;
    }
}
