<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user');
            $table->string('status', 20)->default('pending');
            $table->string('phone', 30)->nullable();
            $table->string('sq1')->nullable();
            $table->string('sa1')->nullable();
            $table->string('sq2')->nullable();
            $table->string('sa2')->nullable();
            $table->timestamp('approved_at')->nullable();
        });
        // accounts made by the first package keep working
        DB::table('users')->update(['status' => 'active', 'approved_at' => now()]);
        DB::table('users')->whereRaw('lower(email) = ?', [config('kundli.admin_email')])->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'phone', 'sq1', 'sa1', 'sq2', 'sa2', 'approved_at']);
        });
    }
};
