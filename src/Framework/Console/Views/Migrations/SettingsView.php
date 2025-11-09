<?php

namespace Lightpack\Console\Views\Migrations;

class SettingsView
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
        $this->create('settings', function (Table $table) {
            $table->id();
            $table->varchar('key', 150);
            $table->varchar('key_type', 25)->nullable();
            $table->text('value');
            $table->varchar('group', 150)->default('global');
            $table->column('owner_id')->type('bigint')->attribute('unsigned')->nullable();
            $table->timestamps();
            $table->unique(['key', 'group', 'owner_id']);
            $table->index(['group', 'owner_id']);
        });
    }

    public function down(): void
    {
        $this->drop('settings');
    }
};
TEMPLATE;
    }
}
