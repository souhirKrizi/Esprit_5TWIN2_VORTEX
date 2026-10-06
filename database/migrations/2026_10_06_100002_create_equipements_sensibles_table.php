<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipements_sensibles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nom', 80);
            $table->enum('type', ['refrigerateur', 'appareil_medical', 'climatiseur', 'internet_telephone', 'informatique']);
            $table->enum('sensibilite', ['chaleur', 'coupure', 'les_deux']);
            $table->unsignedSmallInteger('autonomie_heures')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipements_sensibles');
    }
};
