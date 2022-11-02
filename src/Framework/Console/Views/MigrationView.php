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
    public function up(): void
    {
        // ...
    }

    public function down(): void
    {
        // ...
    }
};
TEMPLATE;
    }
}
