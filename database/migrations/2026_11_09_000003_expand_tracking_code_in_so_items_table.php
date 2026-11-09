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
            $table->string('tracking_code', 255)->change();
            $table->string('area', 255)->nullable()->change();
            $table->string('cell', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_items', function (Blueprint $table) {
            $table->string('tracking_code', 50)->change();
            $table->string('area', 100)->nullable()->change();
            $table->string('cell', 100)->nullable()->change();
        });
    }
};

