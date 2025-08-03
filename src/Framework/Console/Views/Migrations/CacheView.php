<?php

namespace Lightpack\Console\Views\Migrations;

class CacheView
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
        $this->create('cache', function (Table $table) {
            $table->varchar('`key`', 255)->primary();
            $table->column('value')->type('longtext');
            $table->column('expires_at')->type('int')->attribute('UNSIGNED');
            $table->index('expires_at', 'idx_cache_expiry');
        });
    }

    public function down(): void
    {
        $this->drop('cache');
    }
};
TEMPLATE;
    }
}
