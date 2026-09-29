<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('slide_configs', function (Blueprint $table) {
            $table->string('public_alias', 80)->nullable()->unique()->after('public_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('slide_configs', function (Blueprint $table) {
            $table->dropColumn('public_alias');
        });
    }
};
