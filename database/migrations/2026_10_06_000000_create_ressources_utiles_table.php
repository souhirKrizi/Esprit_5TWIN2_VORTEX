<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ressources_utiles', function (Blueprint $table): void {
            $table->id();
            $table->string('nom');
            $table->string('type', 30);
            $table->string('gouvernorat', 100);
            $table->string('adresse');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('statut', 20)->default('actif');
            $table->timestamps();

            $table->index('gouvernorat');
            $table->index('type');
            $table->index('statut');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ressources_utiles');
    }
};
