<?php

namespace Lightpack\Uploads\Migration;

use Lightpack\Database\Migrations\Migration;

class CreateUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->createTable('uploads', function($table) {
            $table->id();
            $table->varchar('model_type');
            $table->column('model_id')->type('bigint')->attribute('unsigned');
            $table->varchar('collection')->default('default');
            $table->varchar('name');
            $table->varchar('file_name');
            $table->varchar('mime_type');
            $table->varchar('extension');
            $table->column('size')->type('bigint');
            $table->varchar('path');
            $table->column('meta')->type('json')->nullable();
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
        $this->schema->dropTable('uploads');
    }
}
