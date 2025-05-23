<?php

namespace Lightpack\Uploads\Migration;

use Lightpack\Database\Schema\Table;
use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->alter('users')->add(function (Table $table) {
            $table->varchar('phone', 20)->nullable(); // User's phone number for SMS MFA
            $table->varchar('mfa_method', 32)->nullable(); // User's chosen MFA factor
            $table->boolean('mfa_enabled')->default(false); // Optional: for opt-in scenarios
            $table->text('mfa_backup_codes')->nullable(); // Backup code hashes (JSON array)
            $table->varchar('mfa_totp_secret', 64)->nullable(); // TOTP secret for authenticator apps
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->alter('users')->modify(function (Table $table) {
            $table->dropColumn('phone');
            $table->dropColumn('mfa_method');
            $table->dropColumn('mfa_enabled');
            $table->dropColumn('mfa_backup_codes');
            $table->dropColumn('mfa_totp_secret');
        });
    }
};