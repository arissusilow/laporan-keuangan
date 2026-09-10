<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('transactions')->whereNull('description')->update(['description' => '']);

        Schema::table('transactions', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
        });
    }
};
