<?php

namespace Lightpack\Console\Views\Migrations;

class SocialView
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
        $this->create('social_accounts', function (Table $table) {
            $table->id();
            $table->column('tenant_id')->type('BIGINT')->attribute('UNSIGNED')->default(0);
            $table->column('user_id')->type('BIGINT')->attribute('UNSIGNED');
            $table->varchar('provider', 20);
            $table->varchar('provider_id', 255);
            $table->timestamps();

            $table->foreignKey('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['tenant_id', 'provider', 'provider_id']);
            $table->index('user_id');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        $this->drop('social_accounts');
    }
};
TEMPLATE;
    }
}
