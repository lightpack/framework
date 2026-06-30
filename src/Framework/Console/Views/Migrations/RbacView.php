<?php

namespace Lightpack\Console\Views\Migrations;

class RbacView
{
    public static function getTemplate()
    {
        return <<<'TEMPLATE'
<?php

use Lightpack\Database\Migrations\Migration;
use Lightpack\Database\Schema\Table;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('roles', function (Table $table) {
            $table->id();
            $table->column('tenant_id')->type('bigint')->attribute('unsigned')->default(0);
            $table->varchar('name');
            $table->varchar('description')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        $this->create('permissions', function (Table $table) {
            $table->id();
            $table->column('tenant_id')->type('bigint')->attribute('unsigned')->default(0);
            $table->varchar('name');
            $table->varchar('description')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        $this->create('user_role', function (Table $table) {
            $table->column('tenant_id')->type('bigint')->attribute('unsigned')->default(0);
            $table->column('user_id')->type('bigint')->attribute('unsigned');
            $table->column('role_id')->type('bigint')->attribute('unsigned');
            $table->timestamps();
            $table->foreignKey('role_id')->cascadeOnDelete();
            $table->foreignKey('user_id')->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
            $table->index('user_id');
        });

        $this->create('role_permission', function (Table $table) {
            $table->column('tenant_id')->type('bigint')->attribute('unsigned')->default(0);
            $table->column('role_id')->type('bigint')->attribute('unsigned');
            $table->column('permission_id')->type('bigint')->attribute('unsigned');
            $table->timestamps();
            $table->foreignKey('permission_id')->cascadeOnDelete();
            $table->foreignKey('role_id')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
            $table->index('role_id');
        });
    }

    public function down(): void
    {
        $this->drop('role_permission');
        $this->drop('user_role');
        $this->drop('permissions');
        $this->drop('roles');
    }
};
TEMPLATE;
    }
}
