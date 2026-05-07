<?php
/*
 * File: database/migrations/2026_05_05_040647_create_records_table.php
 */

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
        Schema::create('records', function (Blueprint $table) {
            $table->id('Id_Record');
            $table->string('Code_Part');
            $table->string('Name_Part');
            $table->string('Code_Rack');
            $table->string('Area');
            $table->string('No_Card');
            $table->string('Location');
            $table->string('NIK');
            $table->dateTime('Time_Record');
            $table->double('Count_Record');
            $table->text('Photo_Record')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('records');
    }
};
