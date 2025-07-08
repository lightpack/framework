<?php

use Lightpack\Database\Migrations\Migration;
use Lightpack\Database\Schema\Table;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('social_accounts', function (Table $table) {
            $table->id();
            $table->column('user_id')->type('BIGINT')->attribute('UNSIGNED');
            $table->varchar('provider', 20);
            $table->varchar('provider_id', 255);
            $table->timestamps();

            $table->foreignKey('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['provider', 'provider_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        $this->drop('social_accounts');
    }
};
