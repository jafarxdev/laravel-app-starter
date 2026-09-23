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
        foreach (['roles', 'permissions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('name_fa')->nullable();
                $table->text('description_fa')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['roles', 'permissions'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn(['name_fa', 'description_fa']);
            });
        }
    }
};
