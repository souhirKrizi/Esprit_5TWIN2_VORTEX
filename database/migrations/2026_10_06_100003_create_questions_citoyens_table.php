<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions_citoyens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiche_conseil_id')->constrained('fiches_conseils')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('question');
            $table->text('reponse')->nullable();
            $table->enum('statut', ['en_attente', 'repondue'])->default('en_attente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions_citoyens');
    }
};
