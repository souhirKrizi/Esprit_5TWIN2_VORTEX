<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pieces_jointes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('signalement_id')->constrained('signalements')->cascadeOnDelete();
            $table->string('chemin_fichier');
            $table->string('nom_original');
            $table->string('type_mime', 100);
            $table->unsignedInteger('taille');
            $table->timestamps();

            $table->index('signalement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pieces_jointes');
    }
};
