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
        // 1. Master Jadwal / Event Stock Opname
        Schema::create('so_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_code', 50)->unique(); // Misal: SO-2026-Q1, SO-2026-NOV
            $table->string('event_name', 150);         // Misal: Stock Opname Semester 2 2026
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['DRAFT', 'ACTIVE', 'CLOSED'])->default('DRAFT');
            $table->integer('total_parts_snapshot')->default(0);
            $table->text('description')->nullable();
            $table->string('created_by', 50)->nullable();
            $table->timestamps();
        });

        // 2. Snapshot Master Part khusus untuk setiap Event SO (copy dari iseki_label)
        Schema::create('so_event_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('so_event_id')->constrained('so_events')->onDelete('cascade');
            $table->unsignedBigInteger('label_part_id')->nullable(); // id asli di iseki_label.rack_part_lists
            $table->string('rack_no', 100);
            $table->string('location', 100)->nullable();
            $table->string('cell', 100)->nullable();
            $table->string('loc_cell_display', 150)->nullable();
            $table->string('item_code', 100);
            $table->string('part_name', 255);
            $table->string('supplier', 100)->nullable();
            $table->string('satuan', 50)->default('PCS');
            $table->string('tracking_code', 255)->index(); // item_code|part_name|rack_no|loc-cell
            $table->integer('pair_index')->default(1);
            $table->integer('total_pairs')->default(1);
            $table->timestamps();

            $table->index(['so_event_id', 'rack_no']);
            $table->index(['so_event_id', 'item_code']);
        });

        // 3. Tambahkan so_event_id ke tabel so_items
        if (Schema::hasTable('so_items') && !Schema::hasColumn('so_items', 'so_event_id')) {
            Schema::table('so_items', function (Blueprint $table) {
                $table->foreignId('so_event_id')->nullable()->after('id')->constrained('so_events')->onDelete('set null');
            });
        }

        // 4. Tambahkan so_event_id ke tabel auditor_records
        if (Schema::hasTable('auditor_records') && !Schema::hasColumn('auditor_records', 'so_event_id')) {
            Schema::table('auditor_records', function (Blueprint $table) {
                $table->foreignId('so_event_id')->nullable()->after('id')->constrained('so_events')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('auditor_records') && Schema::hasColumn('auditor_records', 'so_event_id')) {
            Schema::table('auditor_records', function (Blueprint $table) {
                $table->dropForeign(['so_event_id']);
                $table->dropColumn('so_event_id');
            });
        }

        if (Schema::hasTable('so_items') && Schema::hasColumn('so_items', 'so_event_id')) {
            Schema::table('so_items', function (Blueprint $table) {
                $table->dropForeign(['so_event_id']);
                $table->dropColumn('so_event_id');
            });
        }

        Schema::dropIfExists('so_event_parts');
        Schema::dropIfExists('so_events');
    }
};

