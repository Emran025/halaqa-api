<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_request_profiles', function (Blueprint $table): void {
            $table->text('stop_reasons')->nullable()->after('previous_memorization_notes');
        });
    }

    public function down(): void
    {
        Schema::table('registration_request_profiles', function (Blueprint $table): void {
            $table->dropColumn('stop_reasons');
        });
    }
};
