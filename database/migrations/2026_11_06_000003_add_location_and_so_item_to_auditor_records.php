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
            if (!Schema::hasColumn('auditor_records', 'so_item_id')) {
                $table->unsignedBigInteger('so_item_id')->nullable()->after('so_event_id');
            }
            if (!Schema::hasColumn('auditor_records', 'location')) {
                $table->string('location', 50)->nullable()->after('code_part');
            }
            if (!Schema::hasColumn('auditor_records', 'cell')) {
                $table->string('cell', 50)->nullable()->after('location');
            }
            if (!Schema::hasColumn('auditor_records', 'tracking_code')) {
                $table->string('tracking_code', 255)->nullable()->after('cell');
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
                'so_item_id',
                'location',
                'cell',
                'tracking_code',
            ]);
        });
    }
};

