<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('governorate', 100)->nullable()->after('email');
            $table->boolean('email_alerts')->default(false)->after('password');
            $table->timestamp('terms_accepted_at')->nullable()->after('email_alerts');
            $table->boolean('archived')->default(false)->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['governorate', 'email_alerts', 'terms_accepted_at', 'archived']);
        });
    }
};