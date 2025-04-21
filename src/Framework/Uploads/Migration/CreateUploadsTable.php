<?php

namespace Lightpack\Uploads\Migration;

use Lightpack\Database\Migration\Migration;

class CreateUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->create('uploads', function($table) {
            $table->id();
            $table->string('model_type');
            $table->integer('model_id');
            $table->string('collection')->default('default');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type');
            $table->string('extension');
            $table->bigInteger('size');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index(['model_type', 'model_id']);
            $table->index('collection');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->schema->drop('uploads');
    }
}
