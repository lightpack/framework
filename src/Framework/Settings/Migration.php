<?php

use Lightpack\Database\Schema\Table;
use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('settings', function (Table $table) {
            $table->id();
            $table->varchar('key', 191);
            $table->text('value');
            $table->varchar('type', 32)->nullable();
            $table->varchar('model_type', 64);
            $table->column('model_id')->type('bigint')->attribute('unsigned');
            $table->timestamps();
            $table->index(['model_type', 'model_id', 'key']);
        });
    }

    public function down(): void
    {
        $this->drop('settings');
    }
};
