<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiches_conseils', function (Blueprint $table) {
            $table->string('image')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('fiches_conseils', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
