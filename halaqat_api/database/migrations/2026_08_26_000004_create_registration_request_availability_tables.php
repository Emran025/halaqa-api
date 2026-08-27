<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_request_availability', function (Blueprint $table): void {
            $table->uuid('registration_request_id')->primary();
            $table->string('timezone', 64)->default('UTC');
            $table->unsignedSmallInteger('preferred_session_duration_minutes')->default(30);
            $table->timestamps();
            $table->foreign(
                'registration_request_id',
                'fk_reg_req_avail_request'
            )->references('id')->on('registration_requests')->cascadeOnDelete();
        });

        Schema::create('registration_request_availability_slots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('registration_request_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('available_from');
            $table->time('available_to');
            $table->boolean('is_preferred')->default(false);
            $table->timestamps();
            $table->unique(['registration_request_id', 'day_of_week', 'available_from', 'available_to'], 'uq_registration_availability_slot');
            $table->index(['registration_request_id', 'day_of_week']);
            $table->foreign(
                'registration_request_id',
                'fk_reg_req_avail_slot_request'
            )->references('id')->on('registration_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_request_availability_slots');
        Schema::dropIfExists('registration_request_availability');
    }
};
