<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['salesperson', 'manager', 'hod', 'admin'])
                ->default('salesperson')->after('password');
            $table->foreignId('department_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->foreignId('team_id')->nullable()->after('department_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['team_id']);
            $table->dropColumn(['role', 'department_id', 'team_id']);
        });
    }
};
