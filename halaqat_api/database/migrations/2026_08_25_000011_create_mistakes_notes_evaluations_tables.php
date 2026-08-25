<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mistakes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tracking_detail_id');
            $table->unsignedInteger('ayah_id');
            $table->unsignedSmallInteger('edition_id');
            $table->unsignedSmallInteger('word_index');
            $table->unsignedTinyInteger('mistake_type_id');
            $table->string('source_role', 20);
            $table->string('note', 2000)->nullable();
            $table->uuid('created_by_user_id');
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('tracking_detail_id')->references('uuid')->on('tracking_details')->cascadeOnDelete();
            $table->foreign('ayah_id')->references('id')->on('quran_ayahs')->restrictOnDelete();
            $table->foreign('mistake_type_id')->references('id')->on('mistake_types')->restrictOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->restrictOnDelete();
            $table->index(['edition_id', 'ayah_id', 'word_index']);
        });
        Schema::create('task_notes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('session_task_id');
            $table->uuid('author_id');
            $table->text('note');
            $table->unsignedInteger('ayah_id')->nullable();
            $table->unsignedSmallInteger('edition_id')->nullable();
            $table->unsignedSmallInteger('word_index')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('session_task_id')->references('id')->on('session_tasks')->cascadeOnDelete();
            $table->foreign('author_id')->references('id')->on('users')->restrictOnDelete();
        });
        Schema::create('task_evaluations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('session_task_id');
            $table->uuid('evaluator_id');
            $table->string('evaluator_role', 20);
            $table->unsignedTinyInteger('score');
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['session_task_id', 'evaluator_id']);
            $table->foreign('session_task_id')->references('id')->on('session_tasks')->cascadeOnDelete();
            $table->foreign('evaluator_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_evaluations');
        Schema::dropIfExists('task_notes');
        Schema::dropIfExists('mistakes');
    }
};
