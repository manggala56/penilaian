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
        Schema::create('upload_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email')->index();
            $table->string('ip_address', 45)->index();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upload_histories');
    }
};
