<?php

use Lightpack\Database\Schema\Table;
use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('tags', function (Table $table) {
            $table->id();
            $table->varchar('name', 150)->unique();
            $table->varchar('slug', 150)->unique();
            $table->timestamps();
        });

        $this->create('taggables', function (Table $table) {
            $table->column('tag_id')->type('bigint')->attribute('unsigned');
            $table->column('taggable_id')->type('bigint')->attribute('unsigned');
            $table->varchar('taggable_type', 150);

            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);
            $table->foreignKey('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $this->drop('taggables');
        $this->drop('tags');
    }
};