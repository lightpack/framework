<?php

namespace Lightpack\Console\Views\Migrations;

class JobsView
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
        $this->create('jobs', function (Table $table) {
            $table->id();
            $table->varchar('handler', 255);
            $table->varchar('queue', 55)->index();
            $table->text('payload');
            $table->varchar('status', 55)->index();
            $table->column('attempts')->type('int');
            $table->column('exception')->type('longtext')->nullable();
            $table->createdAt();
            $table->datetime('scheduled_at')->default('CURRENT_TIMESTAMP')->index();
            $table->datetime('failed_at')->nullable();
        });
    }

    public function down(): void
    {
        $this->drop('jobs');
    }
};
TEMPLATE;
    }
}
