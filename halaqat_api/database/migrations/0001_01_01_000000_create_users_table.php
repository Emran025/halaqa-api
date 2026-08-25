<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('role', 20);
            $table->string('username', 60)->nullable()->unique();
            $table->string('name', 120);
            $table->string('email')->unique();
            $table->string('password');
            $table->string('gender', 20);
            $table->date('birth_date');
            $table->string('country', 100);
            $table->string('city', 100);
            $table->string('residence', 200)->nullable();
            $table->string('avatar_path', 500)->nullable();
            $table->string('phone', 30);
            $table->string('phone_zone', 8);
            $table->string('whatsapp_phone', 30)->nullable();
            $table->string('whatsapp_zone', 8)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('email_verified_at')->nullable();
            $table->dateTime('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['role', 'status']);
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
