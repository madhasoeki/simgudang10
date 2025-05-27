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
        Schema::create('status_tempat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tempat_id')->constrained('tempat');
            $table->date('periode_awal');
            $table->date('periode_akhir');
            $table->enum('status', ['loading', 'done'])->default('loading');
            $table->integer('total')->default(0);
            $table->timestamps();
            $table->unique(['tempat_id', 'periode_awal', 'periode_akhir']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_tempat');
    }
};
