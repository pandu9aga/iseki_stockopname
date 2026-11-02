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
        Schema::create('so_items', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_code', 50)->unique();
            $table->unsignedBigInteger('rack_label_id')->nullable();
            $table->string('rack_no', 100);
            $table->string('code_rack', 100)->nullable();
            $table->string('area', 100)->nullable();
            $table->string('cell', 100)->nullable();
            $table->string('code_part', 100);
            $table->string('name_part', 255);
            $table->enum('process_type', ['MANUAL', 'SCALE'])->nullable();

            // Status Siklus
            $table->enum('current_step', [
                'COURIER',
                'READY_TO_COUNT',
                'POS1_DONE',
                'MISMATCH_NG',
                'READY_TO_RETURN',
                'RETURNED'
            ])->default('COURIER');

            // Kurir
            $table->string('courier_nik', 50)->nullable();
            $table->string('courier_photo_part', 255)->nullable();
            $table->string('courier_photo_rack', 255)->nullable();
            $table->timestamp('courier_at')->nullable();

            // Hasil Perhitungan
            $table->integer('final_count')->nullable();
            $table->integer('pos1_count')->nullable();
            $table->integer('pos2_count')->nullable();
            $table->foreignId('scale_id')->nullable()->constrained('scales')->nullOnDelete();
            $table->string('scale_number', 50)->nullable();
            $table->string('scale_user_nik', 50)->nullable();
            $table->string('scale_partner_nik', 50)->nullable();
            $table->timestamp('count_completed_at')->nullable();

            // Pengembalian
            $table->string('return_nik', 50)->nullable();
            $table->string('return_rack_scanned', 100)->nullable();
            $table->string('return_photo', 255)->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('so_items');
    }
};

