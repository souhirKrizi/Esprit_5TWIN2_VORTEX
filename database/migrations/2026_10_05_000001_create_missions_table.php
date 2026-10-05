<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campagne_id')->constrained('campagnes')->restrictOnDelete();
            $table->string('titre');
            $table->text('description');
            $table->string('lieu');
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->integer('nombre_places');
            $table->enum('statut', ['ouverte', 'complete', 'en_cours', 'terminee', 'annulee'])->default('ouverte');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
