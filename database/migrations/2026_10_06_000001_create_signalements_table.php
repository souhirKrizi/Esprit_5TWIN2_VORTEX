<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signalements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ressource_utile_id')->nullable()->constrained('ressources_utiles')->nullOnDelete();
            $table->string('type_signalement', 20);
            $table->string('type_incident', 40)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->text('commentaire')->nullable();
            $table->string('statut_validation', 20)->default('en_attente');
            $table->text('motif_rejet')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('ressource_utile_id');
            $table->index('type_signalement');
            $table->index('statut_validation');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signalements');
    }
};
