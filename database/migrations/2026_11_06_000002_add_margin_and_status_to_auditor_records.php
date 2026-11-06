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
        Schema::table('auditor_records', function (Blueprint $table) {
            if (!Schema::hasColumn('auditor_records', 'error_margin_percent')) {
                $table->decimal('error_margin_percent', 8, 2)->nullable()->after('weight_diff');
            }
            if (!Schema::hasColumn('auditor_records', 'member_diff')) {
                $table->integer('member_diff')->nullable()->after('error_margin_percent');
            }
            if (!Schema::hasColumn('auditor_records', 'member_error_margin_percent')) {
                $table->decimal('member_error_margin_percent', 8, 2)->nullable()->after('member_diff');
            }
            if (!Schema::hasColumn('auditor_records', 'scale_status')) {
                $table->string('scale_status', 10)->nullable()->after('member_error_margin_percent'); // OK atau NG
            }
            if (!Schema::hasColumn('auditor_records', 'audit_status')) {
                $table->string('audit_status', 10)->nullable()->after('scale_status'); // OK atau NG
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auditor_records', function (Blueprint $table) {
            $table->dropColumn([
                'error_margin_percent',
                'member_diff',
                'member_error_margin_percent',
                'scale_status',
                'audit_status',
            ]);
        });
    }
};

