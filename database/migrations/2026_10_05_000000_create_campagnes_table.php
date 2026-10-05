<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campagnes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organisateur_id')->constrained('users')->restrictOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->text('objectif');
            $table->string('lieu');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->enum('statut', ['brouillon', 'publiee', 'en_cours', 'terminee', 'annulee'])->default('brouillon');
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campagnes');
    }
};
