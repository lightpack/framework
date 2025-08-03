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
            $table->timestamps();
        });

        $this->create('permissions', function (Table $table) {
            $table->id();
            $table->varchar('name')->unique();
            $table->varchar('description')->nullable();
            $table->timestamps();
        });

        $this->create('role_user', function (Table $table) {
            $table->column('role_id')->type('bigint')->attribute('unsigned');
            $table->column('user_id')->type('bigint')->attribute('unsigned');
            $table->timestamps();
            $table->foreignKey('role_id')->cascadeOnDelete();
            $table->foreignKey('user_id')->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        $this->create('permission_role', function (Table $table) {
            $table->column('permission_id')->type('bigint')->attribute('unsigned');
            $table->column('role_id')->type('bigint')->attribute('unsigned');
            $table->timestamps();
            $table->foreignKey('permission_id')->cascadeOnDelete();
            $table->foreignKey('role_id')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });
    }

    public function down(): void
    {
        $this->drop('permission_role');
        $this->drop('role_user');
        $this->drop('permissions');
        $this->drop('roles');
    }
};
TEMPLATE;
    }
}
