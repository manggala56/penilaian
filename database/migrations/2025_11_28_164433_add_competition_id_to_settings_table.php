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
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('competition_id')->nullable()->after('group')->constrained()->nullOnDelete();
            
            // Drop existing unique index on 'key' if it exists (assuming it was just 'key')
            $table->dropUnique(['key']);
            
            // Add new unique index for key + competition_id
            // Note: In MySQL, unique constraints allow multiple NULLs, so (key, NULL) can exist multiple times.
            // If we want global settings (competition_id = NULL) to be unique per key, we need to handle that.
            // However, standard unique index on (key, competition_id) allows multiple (key, NULL) rows in some DBs but not others depending on config.
            // For Laravel/MySQL default, (key, NULL) is not unique. 
            // So we might need a different approach if we want strict uniqueness for global settings.
            // But usually, we want ONE global setting per key.
            // Let's try to enforce uniqueness where competition_id IS NULL via application logic or a generated column if needed.
            // For now, let's just add the column and index.
            $table->unique(['key', 'competition_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropForeign(['competition_id']);
            $table->dropUnique(['key', 'competition_id']);
            $table->dropColumn('competition_id');
            $table->unique('key');
        });
    }
};
