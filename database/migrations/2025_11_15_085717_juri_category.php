<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus constraint foreign key terlebih dahulu
        Schema::table('juri', function (Blueprint $table) {
            // Hapus foreign key constraint
            $table->dropForeign(['category_id']);

            // Setelah foreign key dihapus, baru hapus unique constraint
            $table->dropUnique(['user_id', 'category_id']);

            // Hapus kolom category_id
            $table->dropColumn('category_id');
        });

        // Buat tabel pivot untuk many-to-many
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

        Schema::table('juri', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->unique(['user_id', 'category_id']);
        });
    }
};
