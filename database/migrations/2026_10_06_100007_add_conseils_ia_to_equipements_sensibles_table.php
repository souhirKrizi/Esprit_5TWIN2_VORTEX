<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipements_sensibles', function (Blueprint $table) {
            $table->json('conseils_ia')->nullable()->after('description');
            $table->timestamp('conseils_ia_le')->nullable()->after('conseils_ia');
        });
    }

    public function down(): void
    {
        Schema::table('equipements_sensibles', function (Blueprint $table) {
            $table->dropColumn(['conseils_ia', 'conseils_ia_le']);
        });
    }
};
