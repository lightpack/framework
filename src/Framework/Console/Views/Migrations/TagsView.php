<?php

namespace Lightpack\Console\Views\Migrations;

class TagsView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
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

        $this->create('tag_models', function (Table $table) {
            $table->column('tag_id')->type('bigint')->attribute('unsigned');
            $table->column('model_id')->type('bigint')->attribute('unsigned');
            $table->varchar('model_type', 191);
            $table->primary(['tag_id', 'model_id', 'model_type']);
            $table->foreignKey('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $this->drop('tag_models');
        $this->drop('tags');
    }
};
TEMPLATE;
    }
}
