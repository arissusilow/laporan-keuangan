<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_categories', function (Blueprint $table): void {
            $table->string('type', 3)->default('OUT')->after('report_session_id');
        });

        Schema::table('expense_categories', function (Blueprint $table): void {
            $table->dropUnique('expense_categories_report_session_id_name_unique');
            $table->unique(['report_session_id', 'type', 'name']);
            $table->unique(['report_session_id', 'id', 'type']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transactions DROP CONSTRAINT tx_category_report_fk');
            DB::statement('ALTER TABLE transactions DROP CONSTRAINT transaction_category_check');
            DB::statement("ALTER TABLE expense_categories ADD CONSTRAINT expense_category_type_check CHECK (type IN ('IN','OUT'))");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transaction_category_check CHECK (type = 'IN' OR (type = 'OUT' AND expense_category_id IS NOT NULL))");
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT tx_category_report_type_fk FOREIGN KEY (report_session_id, expense_category_id, type) REFERENCES expense_categories (report_session_id, id, type) ON DELETE RESTRICT');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transactions DROP CONSTRAINT tx_category_report_type_fk');
            DB::statement('ALTER TABLE transactions DROP CONSTRAINT transaction_category_check');
            DB::statement('ALTER TABLE expense_categories DROP CONSTRAINT expense_category_type_check');
        }

        DB::table('transactions')->where('type', 'IN')->update(['expense_category_id' => null]);
        DB::table('expense_categories')->where('type', 'IN')->delete();

        Schema::table('expense_categories', function (Blueprint $table): void {
            $table->dropUnique(['report_session_id', 'type', 'name']);
            $table->dropUnique(['report_session_id', 'id', 'type']);
            $table->unique(['report_session_id', 'name']);
        });

        Schema::table('expense_categories', function (Blueprint $table): void {
            $table->dropColumn('type');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transaction_category_check CHECK ((type = 'IN' AND expense_category_id IS NULL) OR (type = 'OUT' AND expense_category_id IS NOT NULL))");
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT tx_category_report_fk FOREIGN KEY (report_session_id, expense_category_id) REFERENCES expense_categories (report_session_id, id) ON DELETE RESTRICT');
        }
    }
};
