<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->boolean('submission_preference_enabled')->default(true)->after('is_read');
            $table->boolean('role_selection_enabled')->default(true)->after('submission_preference_enabled');
            $table->boolean('rating_enabled')->default(true)->after('role_selection_enabled');
            $table->boolean('suggestions_enabled')->default(true)->after('rating_enabled');

            // Optional: Remove old column if you're fully migrating away from it
            $table->dropColumn('allow_anonymous');
        });
    }

    public function down(): void
    {
        Schema::table('forms', function (Blueprint $table) {
            $table->dropColumn([
                'submission_preference_enabled',
                'role_selection_enabled',
                'rating_enabled',
                'suggestions_enabled',
            ]);

            $table->boolean('allow_anonymous')->default(true);
        });
    }
};