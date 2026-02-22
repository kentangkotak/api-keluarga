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
       Schema::create('pernikahan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('suami_id')
                ->constrained('anggota_keluarga')
                ->cascadeOnDelete();

            $table->foreignId('istri_id')
                ->constrained('anggota_keluarga')
                ->cascadeOnDelete();

            $table->date('tanggal_nikah')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pernikahan');
    }
};
