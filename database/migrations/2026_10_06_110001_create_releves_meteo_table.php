<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releves_meteo', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('zone_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['observation', 'prevision'])->default('observation');
            $table->dateTime('date_mesure');
            $table->decimal('temperature', 5, 2);
            $table->unsignedTinyInteger('humidite');
            $table->decimal('vitesse_vent', 5, 2)->nullable();
            $table->decimal('indice_uv', 4, 1)->nullable();
            $table->string('source', 100)->default('Saisie manuelle');
            $table->timestamps();

            $table->index(['zone_id', 'date_mesure']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releves_meteo');
    }
};