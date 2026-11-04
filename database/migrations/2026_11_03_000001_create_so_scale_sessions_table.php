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
        Schema::create('so_scale_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('so_item_id')->unique()->constrained('so_items')->cascadeOnDelete();
            $table->string('member_nik', 50);
            $table->string('partner_nik', 50);
            $table->foreignId('scale_id')->nullable()->constrained('scales')->nullOnDelete();
            $table->enum('status', ['DRAFT', 'FINAL_WEIGHED', 'PRINTED', 'COMPLETED'])->default('DRAFT');
            $table->unsignedInteger('round_count')->default(0);
            $table->integer('final_count')->nullable();
            $table->decimal('final_margin', 5, 2)->nullable();
            $table->string('label_photo_path', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('so_scale_sessions');
    }
};
