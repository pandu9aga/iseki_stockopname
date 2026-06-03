<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('base_datas', function (Blueprint $table) {
            $table->id('Id_Base_Data');
            $table->string('Code_Part');
            $table->string('Name_Part');
            $table->string('Code_Rack');
            $table->string('Area')->nullable();
            $table->string('Location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('base_datas');
    }
};
