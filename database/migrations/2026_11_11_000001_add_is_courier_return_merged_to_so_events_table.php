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
        Schema::table('so_events', function (Blueprint $table) {
            $table->boolean('is_courier_return_merged')->default(false)->after('total_parts_snapshot')
                ->comment('Jika true, kurir dan pengembali adalah peran yang sama/bisa merangkap');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_events', function (Blueprint $table) {
            $table->dropColumn('is_courier_return_merged');
        });
    }
};

