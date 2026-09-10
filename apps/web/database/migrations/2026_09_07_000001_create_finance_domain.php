<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false);
            $table->boolean('active')->default(true);
            $table->boolean('must_change_password')->default(false);
        });

        Schema::create('report_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->bigInteger('opening_balance')->default(0);
            $table->char('currency', 3)->default('IDR');
            $table->string('color', 7)->default('#12372A');
            $table->string('logo_path')->nullable();
            $table->string('status', 16)->default('ACTIVE');
            $table->timestamps();
            $table->index(['status', 'name']);
        });

        Schema::create('report_session_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16);
            $table->boolean('can_add_in')->default(false);
            $table->boolean('can_add_out')->default(false);
            $table->boolean('can_edit_own')->default(false);
            $table->boolean('can_edit_all')->default(false);
            $table->boolean('can_cancel')->default(false);
            $table->boolean('can_export_pdf')->default(false);
            $table->timestamps();
            $table->unique(['report_session_id', 'user_id']);
        });

        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_session_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#64748B');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['report_session_id', 'name']);
            $table->unique(['report_session_id', 'id']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number', 40);
            $table->date('transaction_date');
            $table->string('type', 3);
            $table->bigInteger('amount');
            $table->text('description');
            $table->string('status', 16)->default('ACTIVE');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['report_session_id', 'number']);
            $table->unique(['report_session_id', 'id']);
            $table->index(['report_session_id', 'transaction_date', 'status']);
            $table->index(['report_session_id', 'type', 'expense_category_id']);
            $table->foreign(['report_session_id', 'expense_category_id'], 'tx_category_report_fk')
                ->references(['report_session_id', 'id'])->on('expense_categories')->restrictOnDelete();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('transaction_id');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->foreign(['report_session_id', 'transaction_id'], 'attachment_transaction_report_fk')
                ->references(['report_session_id', 'id'])->on('transactions')->cascadeOnDelete();
        });

        Schema::create('pdf_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('transaction_type', 3)->nullable();
            $table->foreignId('expense_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('PENDING');
            $table->string('path')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['report_session_id', 'created_at']);
        });

        Schema::create('slide_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_session_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('token_hash', 64)->nullable()->unique();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->unsignedInteger('duration_seconds')->default(12);
            $table->unsignedInteger('refresh_seconds')->default(60);
            $table->boolean('show_latest_transactions')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('backup_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16)->default('MANUAL');
            $table->string('status', 16)->default('PENDING');
            $table->string('path')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('report_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 80);
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['report_session_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE report_sessions ADD CONSTRAINT report_status_check CHECK (status IN ('ACTIVE','CLOSED','ARCHIVED'))");
            DB::statement("ALTER TABLE report_session_members ADD CONSTRAINT member_role_check CHECK (role IN ('ADMIN','OFFICER','VIEWER'))");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transaction_type_check CHECK (type IN ('IN','OUT'))");
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT transaction_amount_check CHECK (amount > 0)');
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transaction_category_check CHECK ((type = 'IN' AND expense_category_id IS NULL) OR (type = 'OUT' AND expense_category_id IS NOT NULL))");
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT transaction_status_check CHECK (status IN ('ACTIVE','CANCELLED'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('backup_jobs');
        Schema::dropIfExists('slide_configs');
        Schema::dropIfExists('pdf_exports');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('report_session_members');
        Schema::dropIfExists('report_sessions');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_super_admin', 'active', 'must_change_password']));
    }
};
