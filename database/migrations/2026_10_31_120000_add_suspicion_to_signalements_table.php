<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signalements', function (Blueprint $table): void {
            $table->unsignedTinyInteger('score_suspicion')->nullable()->after('motif_rejet');
            $table->string('niveau_suspicion')->nullable()->after('score_suspicion');
            $table->json('motifs_suspicion')->nullable()->after('niveau_suspicion');
            $table->foreignId('doublon_de_id')->nullable()->after('motifs_suspicion')
                ->constrained('signalements')->nullOnDelete();
            $table->string('analyse_ia_statut')->default('non_analyse')->after('doublon_de_id');
            $table->timestamp('analyse_at')->nullable()->after('analyse_ia_statut');

            $table->index('niveau_suspicion');
            $table->index('doublon_de_id');
        });
    }

    public function down(): void
    {
        Schema::table('signalements', function (Blueprint $table): void {
            $table->dropIndex(['niveau_suspicion']);
            $table->dropIndex(['doublon_de_id']);
            $table->dropForeign(['doublon_de_id']);
            $table->dropColumn([
                'score_suspicion',
                'niveau_suspicion',
                'motifs_suspicion',
                'doublon_de_id',
                'analyse_ia_statut',
                'analyse_at',
            ]);
        });
    }
};

