<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catatan_penagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tagihan')->constrained('tagihan', 'id_tagihan')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status_penagihan', [
                'belum_ditagih',
                'sedang_ditagih',
                'janji_bayar',
                'sudah_ditagih',
            ]);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['id_tagihan', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catatan_penagihan');
    }
};
