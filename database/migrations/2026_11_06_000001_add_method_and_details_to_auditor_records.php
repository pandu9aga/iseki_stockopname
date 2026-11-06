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
            if (!Schema::hasColumn('auditor_records', 'method')) {
                $table->string('method', 20)->default('MANUAL')->after('auditor_name'); // MANUAL atau SCALE
            }
            if (!Schema::hasColumn('auditor_records', 'input_1')) {
                $table->integer('input_1')->nullable()->after('method');
            }
            if (!Schema::hasColumn('auditor_records', 'input_2')) {
                $table->integer('input_2')->nullable()->after('input_1');
            }
            if (!Schema::hasColumn('auditor_records', 'scale_id')) {
                $table->unsignedBigInteger('scale_id')->nullable()->after('input_2');
            }
            if (!Schema::hasColumn('auditor_records', 'scale_number')) {
                $table->string('scale_number', 50)->nullable()->after('scale_id');
            }
            if (!Schema::hasColumn('auditor_records', 'sample_qty')) {
                $table->integer('sample_qty')->nullable()->after('scale_number');
            }
            if (!Schema::hasColumn('auditor_records', 'weight_without_sample')) {
                $table->integer('weight_without_sample')->nullable()->after('sample_qty');
            }
            if (!Schema::hasColumn('auditor_records', 'weight_with_sample')) {
                $table->integer('weight_with_sample')->nullable()->after('weight_without_sample');
            }
            if (!Schema::hasColumn('auditor_records', 'weight_calculated')) {
                $table->integer('weight_calculated')->nullable()->after('weight_with_sample');
            }
            if (!Schema::hasColumn('auditor_records', 'weight_diff')) {
                $table->integer('weight_diff')->nullable()->after('weight_calculated');
            }
            if (!Schema::hasColumn('auditor_records', 'photo_paths')) {
                $table->json('photo_paths')->nullable()->after('weight_diff');
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
                'method',
                'input_1',
                'input_2',
                'scale_id',
                'scale_number',
                'sample_qty',
                'weight_without_sample',
                'weight_with_sample',
                'weight_calculated',
                'weight_diff',
                'photo_paths',
            ]);
        });
    }
};

