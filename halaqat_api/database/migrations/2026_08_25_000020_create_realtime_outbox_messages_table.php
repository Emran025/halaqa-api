<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realtime_outbox_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->uuid('recipient_id');
            $table->string('event_type', 80);
            $table->string('dedupe_key', 64)->unique('uq_realtime_outbox_dedupe_key');
            $table->json('payload');
            $table->unsignedInteger('attempts')->default(0);
            $table->dateTime('last_attempted_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['delivered_at', 'created_at'], 'idx_realtime_outbox_pending');
            $table->index(['session_id', 'recipient_id', 'created_at'], 'idx_realtime_outbox_session_recipient');
            $table->foreign('session_id')->references('id')->on('live_sessions')->cascadeOnDelete();
            $table->foreign('recipient_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realtime_outbox_messages');
    }
};
