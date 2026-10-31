<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Une seule ligne : le niveau d'alerte choisi par l'admin, en attendant le module d'alertes.
        Schema::create('alerte_en_cours', function (Blueprint $table) {
            $table->id();
            $table->enum('niveau', ['vert', 'jaune', 'orange', 'rouge'])->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerte_en_cours');
    }
};
