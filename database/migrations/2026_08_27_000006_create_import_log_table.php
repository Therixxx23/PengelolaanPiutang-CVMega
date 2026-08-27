<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama_file');
            $table->integer('total_baris')->default(0);
            $table->integer('faktur_baru')->default(0);
            $table->integer('faktur_skip')->default(0);
            $table->integer('pelanggan_baru')->default(0);
            $table->enum('status', ['sukses', 'gagal'])->default('sukses');
            $table->text('pesan_error')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_log');
    }
};
