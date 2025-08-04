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
            $table->varchar('name')->unique();
            $table->varchar('description')->nullable();
            $table->timestamps();
        });

        $this->create('permissions', function (Table $table) {
            $table->id();
            $table->varchar('name')->unique();
            $table->varchar('description')->nullable();
            $table->timestamps();
        });

        $this->create('user_role', function (Table $table) {
            $table->column('user_id')->type('bigint')->attribute('unsigned');
            $table->column('role_id')->type('bigint')->attribute('unsigned');
            $table->timestamps();
            $table->foreignKey('role_id')->cascadeOnDelete();
            $table->foreignKey('user_id')->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        $this->create('role_permission', function (Table $table) {
            $table->column('role_id')->type('bigint')->attribute('unsigned');
            $table->column('permission_id')->type('bigint')->attribute('unsigned');
            $table->timestamps();
            $table->foreignKey('permission_id')->cascadeOnDelete();
            $table->foreignKey('role_id')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
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
