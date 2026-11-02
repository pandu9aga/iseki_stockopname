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
        Schema::create('auditor_records', function (Blueprint $table) {
            $table->id();
            $table->string('rack_no', 100);
            $table->string('code_part', 100)->nullable();
            $table->string('auditor_name', 100);
            $table->integer('audited_count');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditor_records');
    }
};

