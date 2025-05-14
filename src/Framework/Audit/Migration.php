<?php

namespace Lightpack\Audit;

use Lightpack\Database\Schema\Table;
use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('audit_logs', function (Table $table) {
            $table->id();
            $table->column('user_id')->type('bigint')->nullable();
            $table->varchar('action', 50);
            $table->varchar('audit_type', 150);
            $table->column('audit_id')->type('bigint')->nullable();
            $table->column('old_values')->type('text')->nullable();
            $table->column('new_values')->type('text')->nullable();
            $table->column('message')->type('text')->nullable();
            $table->varchar('url', 255)->nullable();
            $table->varchar('ip_address', 45)->nullable();
            $table->varchar('user_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->drop('audit_logs');
    }
};
