<?php

namespace Lightpack\Console\Views\Migrations;

class UploadsView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

namespace Lightpack\Uploads\Migration;

use Lightpack\Database\Schema\Table;
use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('uploads', function(Table $table) {
            $table->id();
            $table->varchar('model_type');
            $table->column('model_id')->type('bigint')->attribute('unsigned');
            $table->varchar('collection')->default('default');
            $table->varchar('name');
            $table->varchar('file_name');
            $table->varchar('mime_type');
            $table->varchar('type', 25);
            $table->varchar('extension');
            $table->column('size')->type('bigint');
            $table->varchar('visibility', 25)->default('public');
            $table->column('meta')->type('json')->nullable();
            $table->timestamps();
            
            $table->index(['model_type', 'model_id']);
            $table->index('collection');
        });
    }

    public function down(): void
    {
        $this->drop('uploads');
    }
};
TEMPLATE;
    }
}
