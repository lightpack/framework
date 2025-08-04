<?php

namespace Lightpack\Console\Views\Migrations;

class TaxonomiesView
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
        $this->create('taxonomies', function (Table $table) {
            $table->id();
            $table->varchar('name', 150);
            $table->varchar('slug', 150);
            $table->varchar('type', 50); // e.g., 'category', 'tag', 'menu'
            $table->column('parent_id')->type('bigint')->attribute('unsigned')->nullable();
            $table->column('sort_order')->type('integer')->default(0);
            $table->text('meta')->nullable();
            $table->timestamps();
            $table->foreignKey('parent_id')->references('id')->on('taxonomies')->cascadeOnDelete();
            $table->unique(['type', 'slug']); // Unique slugs per type
        });

        $this->create('taxonomy_models', function (Table $table) {
            $table->column('taxonomy_id')->type('bigint')->attribute('unsigned');
            $table->column('model_id')->type('bigint')->attribute('unsigned');
            $table->varchar('model_type', 150);
            $table->primary(['taxonomy_id', 'model_id', 'model_type']);
            $table->foreignKey('taxonomy_id')->references('id')->on('taxonomies')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $this->drop('taxonomy_models');
        $this->drop('taxonomies');
    }
};
TEMPLATE;
    }
}
