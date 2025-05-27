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
        Schema::create('barang_keluar', function (Blueprint $table) {
            $table->id();
            $table->string('barang_kode', 10);
            $table->foreignId('tempat_id')->constrained('tempat');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('tanggal');
            $table->integer('qty');
            $table->integer('harga');
            $table->integer('jumlah');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        
            $table->foreign('barang_kode')
                  ->references('kode')
                  ->on('barang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_keluar');
    }
};
