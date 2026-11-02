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
        Schema::create('so_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('so_item_id')->constrained('so_items')->cascadeOnDelete();
            $table->foreignId('history_id')->nullable()->constrained('so_count_histories')->nullOnDelete();
            $table->enum('pos_type', ['POS1', 'POS2', 'SCALE', 'COURIER', 'RETURN']);
            $table->string('photo_path', 255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('so_photos');
    }
};

