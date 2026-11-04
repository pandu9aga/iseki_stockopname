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
        Schema::create('so_scale_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('so_scale_sessions')->cascadeOnDelete();
            $table->foreignId('so_item_id')->constrained('so_items')->cascadeOnDelete();
            $table->unsignedInteger('round_no');
            $table->enum('stage', ['SAMPLE', 'FINAL'])->default('SAMPLE');
            $table->decimal('weight_no_sample', 12, 4);
            $table->decimal('weight_with_sample', 12, 4)->nullable();
            $table->integer('sample_qty')->nullable();
            $table->decimal('error_margin_percent', 5, 2)->nullable();
            $table->integer('result_count')->nullable();
            $table->enum('status', ['OK', 'NG', 'PENDING'])->default('PENDING');
            $table->string('photo_path', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'stage', 'round_no'], 'uq_scale_round');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('so_scale_rounds');
    }
};
