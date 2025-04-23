<?php

namespace Lightpack\Uploads\Migration;

use Lightpack\Database\Migrations\Migration;

class AddIsPrivateToUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->alterTable('uploads', function($table) {
            $table->column('is_private')->type('tinyint')->default(0)->after('path');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->schema->alterTable('uploads', function($table) {
            $table->dropColumn('is_private');
        });
    }
}
