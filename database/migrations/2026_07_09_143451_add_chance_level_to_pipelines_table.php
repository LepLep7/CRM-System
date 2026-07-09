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
        Schema::table('pipelines', function (Blueprint $table) {
            $table->enum('chance_level', ['low', 'medium', 'high'])->nullable()->after('chance_percent');
        });
    }

    public function down(): void
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropColumn('chance_level');
        });
    }
};
