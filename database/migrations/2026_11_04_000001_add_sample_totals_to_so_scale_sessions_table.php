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
        Schema::table('so_scale_sessions', function (Blueprint $table) {
            $table->unsignedInteger('sample_qty')->nullable()->after('round_count');
            $table->decimal('total_without_sample', 14, 4)->nullable()->after('sample_qty');
            $table->decimal('total_with_sample', 14, 4)->nullable()->after('total_without_sample');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_scale_sessions', function (Blueprint $table) {
            $table->dropColumn(['sample_qty', 'total_without_sample', 'total_with_sample']);
        });
    }
};
