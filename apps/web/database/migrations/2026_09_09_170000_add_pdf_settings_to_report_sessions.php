<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_sessions', function (Blueprint $table): void {
            $table->json('pdf_settings')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('report_sessions', function (Blueprint $table): void {
            $table->dropColumn('pdf_settings');
        });
    }
};
