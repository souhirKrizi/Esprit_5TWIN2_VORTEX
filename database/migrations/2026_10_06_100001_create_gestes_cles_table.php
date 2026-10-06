<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestes_cles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiche_conseil_id')->constrained('fiches_conseils')->cascadeOnDelete();
            $table->string('intitule', 150);
            $table->enum('moment', ['avant', 'pendant', 'apres']);
            $table->unsignedTinyInteger('ordre')->default(1);
            $table->timestamps();

            $table->unique(['fiche_conseil_id', 'intitule']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gestes_cles');
    }
};
