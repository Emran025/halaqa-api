<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mistake_types')) {
            Schema::create('mistake_types', function (Blueprint $table): void {
                $table->unsignedTinyInteger('id')->primary();
                $table->string('code', 30)->unique();
                $table->string('label_ar', 100);
                $table->string('label_en', 100);
                $table->unsignedTinyInteger('sort_order');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mistake_types');
    }
};
