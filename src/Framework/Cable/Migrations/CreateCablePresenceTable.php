<?php

use Lightpack\Database\Migration\Schema;
use Lightpack\Database\Migrations\Migration;
use Lightpack\Database\Schema\Table;

class CreateCablePresenceTable extends Migration
{
    /**
     * Run the migration
     */
    public function up(): void
    {
        $this->schema->createTable('cable_presence', function(Table $table) {
            $table->id();
            $table->varchar('channel', 255);
            $table->column('user_id')->type('bigint');
            $table->datetime('last_seen');
            
            // Add unique constraint to prevent duplicates
            $table->unique(['channel', 'user_id']);
            
            // Add indexes for fast lookups
            $table->index('channel');
            $table->index('last_seen');
        });
    }
    
    /**
     * Reverse the migration
     */
    public function down(): void
    {
        $this->schema->dropTable('cable_presence');
    }
}
