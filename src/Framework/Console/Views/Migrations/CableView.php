<?php

namespace Lightpack\Console\Views\Migrations;

class CableView
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
        $this->create('cable_messages', function(Table $table) {
            $table->id();
            $table->varchar('channel', 255);
            $table->varchar('event', 255);
            $table->column('payload')->type('json')->nullable();
            $table->datetime('created_at')->nullable();
            
            $table->index('channel');
        }); 

        $this->create('cable_presence', function(Table $table) {
            $table->id();
            $table->varchar('channel', 255);
            $table->column('user_id')->type('bigint')->attribute('unsigned');
            $table->datetime('last_seen');
            
            $table->unique(['channel', 'user_id']);
            $table->index('channel');
            $table->index('last_seen');
        });
    }
    
    public function down(): void
    {
        $this->drop('cable_messages');
        $this->drop('cable_presence');
    }
};
TEMPLATE;
    }
}
