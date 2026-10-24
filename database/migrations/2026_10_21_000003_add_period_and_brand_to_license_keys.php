<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_keys', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable();
            $table->unsignedInteger('period')->nullable();
            $table->string('period_unit', 10)->nullable();
            $table->string('brand_name', 60)->nullable();
            $table->string('brand_logo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('license_keys', function (Blueprint $table) {
            $table->dropColumn(['starts_at', 'period', 'period_unit', 'brand_name', 'brand_logo']);
        });
    }
};
