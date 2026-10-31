<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signalements', function (Blueprint $table): void {
            $table->string('ia_image_statut')->default('non_analyse')->after('analyse_at');
            $table->boolean('ia_image_pertinente')->nullable()->after('ia_image_statut');
            $table->unsignedTinyInteger('ia_image_confiance')->nullable()->after('ia_image_pertinente');
            $table->string('ia_image_categorie')->nullable()->after('ia_image_confiance');
            $table->text('ia_image_resume')->nullable()->after('ia_image_categorie');
            $table->timestamp('ia_image_analyse_le')->nullable()->after('ia_image_resume');

            $table->index('ia_image_statut');
        });
    }

    public function down(): void
    {
        Schema::table('signalements', function (Blueprint $table): void {
            $table->dropIndex(['ia_image_statut']);
            $table->dropColumn([
                'ia_image_statut',
                'ia_image_pertinente',
                'ia_image_confiance',
                'ia_image_categorie',
                'ia_image_resume',
                'ia_image_analyse_le',
            ]);
        });
    }
};

