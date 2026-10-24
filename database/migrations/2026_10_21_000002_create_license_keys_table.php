<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_keys', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('plan', 60)->nullable();
            $table->unsignedInteger('days')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->string('note')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('kundli_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kundli_settings');
        Schema::dropIfExists('license_keys');
    }
};
