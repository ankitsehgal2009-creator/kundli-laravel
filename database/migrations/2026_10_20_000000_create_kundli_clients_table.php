<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kundli_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('client_key', 64);
            $table->string('name');
            $table->longText('data');
            $table->unsignedBigInteger('updated_ms')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'client_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kundli_clients');
    }
};
