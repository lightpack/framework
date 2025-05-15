<?php

use Lightpack\Database\Schema\Table;
use Lightpack\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $this->alter('users', function (Table $table) {
            $table->varchar('mfa_method', 32)->nullable(); // User's chosen MFA factor
            $table->boolean('mfa_enabled')->default(false); // Optional: for opt-in scenarios
        });
    }

    public function down(): void
    {
        $this->alter('users', function (Table $table) {
            $table->dropColumn('mfa_method');
            $table->dropColumn('mfa_enabled');
        });
    }
};
