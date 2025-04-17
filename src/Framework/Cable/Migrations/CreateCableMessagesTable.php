<?php

namespace Lightpack\Cable\Migrations;

use Lightpack\Database\Migrations\Migration;

/**
 * Migration for creating the cable_messages table
 */
class CreateCableMessagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->schema->createTable('cable_messages', function($table) {
            $table->id();
            $table->varchar('channel', 255);
            $table->varchar('event', 255);
            $table->column('payload')->type('json')->nullable();
            $table->datetime('created_at')->nullable();
            
            $table->index('channel');
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->schema->dropTable('cable_messages');
    }
}
