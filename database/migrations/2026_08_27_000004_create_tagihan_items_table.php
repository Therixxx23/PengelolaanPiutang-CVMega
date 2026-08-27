<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tagihan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_tagihan')->constrained('tagihan', 'id_tagihan')->cascadeOnDelete();
            $table->string('kode_barang')->nullable();
            $table->text('nama_barang');
            $table->string('kelas')->nullable();
            $table->string('spesifikasi')->nullable();
            $table->string('satuan')->nullable();
            $table->string('jenis_barang')->nullable();
            $table->string('kategori')->nullable();
            $table->string('sub_kategori')->nullable();
            $table->string('kode_supplier')->nullable();
            $table->string('nama_supplier')->nullable();
            $table->decimal('harga_jual', 14, 2)->default(0);
            $table->integer('qty_bruto')->default(0);
            $table->decimal('nilai_bruto', 14, 2)->default(0);
            $table->string('persen_diskon', 10)->nullable();
            $table->decimal('nilai_diskon', 14, 2)->default(0);
            $table->decimal('nilai_netto', 14, 2)->default(0);
            $table->integer('qty_retur')->default(0);
            $table->decimal('nilai_retur', 14, 2)->default(0);
            $table->integer('qty_netto')->default(0);
            $table->decimal('netto_penj', 14, 2)->default(0);
            $table->timestamps();

            $table->index('id_tagihan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tagihan_items');
    }
};
