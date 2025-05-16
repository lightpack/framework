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
            $table->varchar('mfa_method', 32)->nullable(); // User's chosen MFA factor
            $table->boolean('mfa_enabled')->default(false); // Optional: for opt-in scenarios
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
            $table->dropColumn('mfa_method');
            $table->dropColumn('mfa_enabled');
        });
    }
};