<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('juri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('expertise')->nullable();
            $table->integer('max_evaluations')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('can_judge_all_categories')->default(false);
            $table->timestamps();
        });

        // Tabel pivot untuk relasi many-to-many dengan categories
        Schema::create('juri_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('juri_id')->constrained('juri')->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['juri_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('juri_category');
        Schema::dropIfExists('juri');
    }
};
