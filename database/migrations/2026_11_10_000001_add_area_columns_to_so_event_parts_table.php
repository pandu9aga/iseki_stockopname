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
        Schema::table('so_event_parts', function (Blueprint $table) {
            $table->string('area_letter', 20)->nullable()->after('rack_no');
            $table->string('area_no', 50)->nullable()->after('area_letter');

            $table->index(['so_event_id', 'area_no']);
            $table->index(['so_event_id', 'area_letter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_event_parts', function (Blueprint $table) {
            $table->dropIndex(['so_event_id', 'area_no']);
            $table->dropIndex(['so_event_id', 'area_letter']);
            $table->dropColumn(['area_letter', 'area_no']);
        });
    }
};

