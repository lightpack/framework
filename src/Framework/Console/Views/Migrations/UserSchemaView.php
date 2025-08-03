<?php

namespace Lightpack\Console\Views\Migrations;

class UserSchemaView
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
        $this->create('users', function (Table $table) {
            $table->id();
            $table->varchar('name');
            $table->varchar('email')->unique();
            $table->datetime('email_verified_at')->nullable();
            $table->varchar('password');
            $table->varchar('remember_token')->nullable();
            $table->varchar('verification_token')->nullable();
            $table->varchar('recovery_token')->nullable();
            $table->boolean('status')->default(true);
            $table->varchar('phone', 20)->nullable();
            $table->varchar('mfa_method', 32)->nullable();
            $table->boolean('mfa_enabled')->default(false);
            $table->text('mfa_backup_codes')->nullable();
            $table->varchar('mfa_totp_secret', 64)->nullable();
            $table->datetime('last_login_at')->nullable();
            $table->timestamps();

            $table->index('remember_token');
            $table->index('verification_token');
            $table->index('recovery_token');
        });

        $this->create('access_tokens', function (Table $table) {
            $table->id();
            $table->column('user_id')->type('BIGINT')->attribute('UNSIGNED')->nullable();
            $table->varchar('name', 55);
            $table->varchar('token', 100);
            $table->text('abilities')->nullable();
            $table->datetime('last_used_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->timestamps();

            $table->foreignKey('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'token']);
        });
    }

    public function down(): void
    {
        $this->drop('access_tokens');
        $this->drop('users');
    }
};
TEMPLATE;
    }
}
