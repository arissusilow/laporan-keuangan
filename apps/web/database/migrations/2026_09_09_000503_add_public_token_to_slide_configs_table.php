<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slide_configs', function (Blueprint $table) {
            $table->text('public_token')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('slide_configs', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
