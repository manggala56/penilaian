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
        Schema::table('participants', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->index(['email']); // Add regular index for performance
        });

        Schema::table('upload_histories', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->unique(['email']);
        });

        Schema::table('upload_histories', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
