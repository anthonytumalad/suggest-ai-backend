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
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_read')->default(false);
            $table->boolean('submission_preference_enabled')->default(true);
            $table->boolean('role_selection_enabled')->default(true);
            $table->boolean('rating_enabled')->default(true);
            $table->boolean('suggestions_enabled')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('slug');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
