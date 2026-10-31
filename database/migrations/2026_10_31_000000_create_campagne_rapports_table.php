<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagne_rapports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campagne_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('modele');
            $table->json('donnees');
            $table->json('analyse');
            $table->timestamp('genere_le');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagne_rapports');
    }
};
