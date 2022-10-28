<?php

namespace Lightpack\Console\Views;

class MigrationView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): string
    {
        return '';
    }

    public function down(): string
    {
        return '';
    }
};
TEMPLATE;
    }
}
