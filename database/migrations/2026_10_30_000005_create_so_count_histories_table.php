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
        Schema::create('so_count_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('so_item_id')->constrained('so_items')->cascadeOnDelete();
            $table->enum('count_type', ['SCALE', 'POS1', 'POS2']);
            $table->string('member_nik', 50);
            $table->string('partner_nik', 50)->nullable();
            $table->foreignId('scale_id')->nullable()->constrained('scales')->nullOnDelete();

            // Non-Timbangan (Manual)
            $table->integer('input_1')->nullable();
            $table->integer('input_2')->nullable();

            // Timbangan
            $table->decimal('weight_no_sample', 12, 4)->nullable();
            $table->decimal('weight_with_sample', 12, 4)->nullable();
            $table->integer('sample_qty')->nullable();
            $table->decimal('error_margin_percent', 5, 2)->nullable();

            $table->integer('result_count');
            $table->enum('status', ['OK', 'NG']);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('so_count_histories');
    }
};

