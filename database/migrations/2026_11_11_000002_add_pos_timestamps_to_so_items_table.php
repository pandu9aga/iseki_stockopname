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
            if (!Schema::hasColumn('so_items', 'pos1_at')) {
                $table->timestamp('pos1_at')->nullable()->after('pos1_count');
            }
            if (!Schema::hasColumn('so_items', 'pos2_at')) {
                $table->timestamp('pos2_at')->nullable()->after('pos2_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_items', function (Blueprint $table) {
            $table->dropColumn(['pos1_at', 'pos2_at']);
        });
    }
};

