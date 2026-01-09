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
        Schema::create('barang_masuk', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('barang_kode', 10);
            $table->integer('qty');
            $table->integer('harga');
            $table->integer('jumlah');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Add user_id column
            
            $table->foreign('barang_kode')
                  ->references('kode')
                  ->on('barang')
                  ->onDelete('cascade');
    
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_masuk');
    }
};