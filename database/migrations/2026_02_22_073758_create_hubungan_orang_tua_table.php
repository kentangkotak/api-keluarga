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
        Schema::create('hubungan_orang_tua', function (Blueprint $table) {
            $table->id();

            $table->foreignId('anak_id')
                ->constrained('anggota_keluarga')
                ->cascadeOnDelete();

            $table->foreignId('orang_tua_id')
                ->constrained('anggota_keluarga')
                ->cascadeOnDelete();

            $table->enum('peran', ['ayah', 'ibu']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hubungan_orang_tua');
    }
};
