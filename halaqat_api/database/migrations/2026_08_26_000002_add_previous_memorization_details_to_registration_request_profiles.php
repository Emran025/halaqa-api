<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_request_profiles', function (Blueprint $table): void {
            $table->json('memorized_surah_ids')->nullable()->after('memorized_juz_count');
            $table->json('last_completed_unit')->nullable()->after('memorized_surah_ids');
        });
    }

    public function down(): void
    {
        Schema::table('registration_request_profiles', function (Blueprint $table): void {
            $table->dropColumn(['memorized_surah_ids', 'last_completed_unit']);
        });
    }
};
