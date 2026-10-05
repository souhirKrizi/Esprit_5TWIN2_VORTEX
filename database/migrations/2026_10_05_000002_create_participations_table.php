<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->text('motivation')->nullable();
            $table->enum('statut', ['en_attente', 'acceptee', 'refusee', 'annulee'])->default('en_attente');
            $table->enum('presence', ['non_renseignee', 'present', 'absent'])->default('non_renseignee');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participations');
    }
};
