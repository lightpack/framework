<?php

namespace Lightpack\Rbac;

use Lightpack\Database\Schema\Table;
use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->create('roles', function (Table $table) {
            $table->id();
            $table->varchar('name', 100)->unique();
            $table->varchar('label', 150)->nullable();
            $table->timestamps();
        });

        $this->create('permissions', function (Table $table) {
            $table->id();
            $table->varchar('name', 100)->unique();
            $table->varchar('label', 150)->nullable();
            $table->timestamps();
        });

        $this->create('user_role', function (Table $table) {
            $table->column('user_id')->type('bigint');
            $table->column('role_id')->type('bigint');
            $table->unique(['user_id', 'role_id']);
            $table->foreignKey('user_id')->references('id')->on('users');
            $table->foreignKey('role_id')->references('id')->on('roles');
        });

        $this->create('role_permission', function (Table $table) {
            $table->column('role_id')->type('bigint');
            $table->column('permission_id')->type('bigint');
            $table->unique(['role_id', 'permission_id']);
            $table->foreignKey('role_id')->references('id')->on('roles');
            $table->foreignKey('permission_id')->references('id')->on('permissions');
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
