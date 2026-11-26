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
        Schema::table('so_items', function (Blueprint $table) {
            $table->index(['so_event_id', 'current_step'], 'idx_so_items_event_step');
            $table->index(['so_event_id', 'code_part', 'rack_no'], 'idx_so_items_event_part_rack');
        });

        Schema::table('auditor_records', function (Blueprint $table) {
            $table->index(['so_event_id', 'so_item_id'], 'idx_auditor_event_so_item');
            $table->index(['so_event_id', 'tracking_code'], 'idx_auditor_event_tracking');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_items', function (Blueprint $table) {
            $table->dropIndex('idx_so_items_event_step');
            $table->dropIndex('idx_so_items_event_part_rack');
        });

        Schema::table('auditor_records', function (Blueprint $table) {
            $table->dropIndex('idx_auditor_event_so_item');
            $table->dropIndex('idx_auditor_event_tracking');
        });
    }
};
