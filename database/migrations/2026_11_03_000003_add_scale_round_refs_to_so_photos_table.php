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
        Schema::table('so_photos', function (Blueprint $table) {
            $table->foreignId('scale_round_id')
                ->nullable()
                ->after('history_id')
                ->constrained('so_scale_rounds')
                ->nullOnDelete();
            $table->enum('photo_role', ['ROUND', 'FINAL', 'LABEL_PROOF'])
                ->default('ROUND')
                ->after('pos_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('scale_round_id');
            $table->dropColumn('photo_role');
        });
    }
};
