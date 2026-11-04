<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu item boleh memiliki beberapa sesi (hitungan ulang).
     * Unique index pada so_item_id harus diganti menjadi index biasa.
     *
     * Catatan: index unique tersebut dipakai oleh foreign key, jadi FK
     * dilepas dulu lalu dipasang kembali setelah index biasa dibuat.
     */
    public function up(): void
    {
        Schema::table('so_scale_sessions', function (Blueprint $table) {
            $table->dropForeign(['so_item_id']);
            $table->dropUnique('so_scale_sessions_so_item_id_unique');
            $table->index('so_item_id');
            $table->foreign('so_item_id')->references('id')->on('so_items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('so_scale_sessions', function (Blueprint $table) {
            $table->dropForeign(['so_item_id']);
            $table->dropIndex('so_scale_sessions_so_item_id_index');
            $table->unique('so_item_id');
            $table->foreign('so_item_id')->references('id')->on('so_items')->cascadeOnDelete();
        });
    }
};
