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
        Schema::create('member_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 50)->unique();
            $table->enum('active_role', ['courier', 'scale', 'pos1', 'pos2', 'returner'])->default('courier');
            $table->string('scale_partner_nik', 50)->nullable();
            $table->foreignId('scale_id')->nullable()->constrained('scales')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_sessions');
    }
};

