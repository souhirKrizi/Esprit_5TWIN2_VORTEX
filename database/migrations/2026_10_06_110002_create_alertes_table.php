<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('zone_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titre', 150);
            $table->text('description');
            $table->enum('niveau_gravite', ['jaune', 'orange', 'rouge']);
            $table->dateTime('debut_validite');
            $table->dateTime('fin_validite');
            $table->enum('statut', ['brouillon', 'active', 'terminee', 'annulee'])->default('brouillon');
            $table->string('origine', 20)->default('manuelle');
            $table->timestamps();

            $table->index(['zone_id', 'statut', 'debut_validite', 'fin_validite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertes');
    }
};