<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiches_conseils', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 150)->unique();
            $table->string('resume', 255);
            $table->text('contenu');
            $table->enum('thematique', ['hydratation', 'economie_energie', 'coupure_courant', 'equipements', 'logement', 'personnes_fragiles']);
            $table->enum('niveau_alerte', ['vert', 'jaune', 'orange', 'rouge']);
            $table->enum('type_equipement', ['refrigerateur', 'appareil_medical', 'climatiseur', 'internet_telephone', 'informatique'])->nullable();
            $table->enum('statut', ['brouillon', 'publiee'])->default('brouillon');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiches_conseils');
    }
};
