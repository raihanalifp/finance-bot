<?php

use App\Enums\CategoryMemorySource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Stores learned phrase and keyword-to-category mappings from user confirmations.
     * Design: Keeps deterministic category memory separate from categories so it can evolve into AI-assisted
     * classification later; confidence, match_count, and source allow safe fallback to manual confirmation.
     */
    public function up(): void
    {
        Schema::create('category_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('phrase');
            $table->string('normalized_phrase');
            $table->json('keywords')->nullable();
            $table->unsignedTinyInteger('confidence_score')->default(70);
            $table->unsignedInteger('match_count')->default(1);
            $table->enum('source', array_column(CategoryMemorySource::cases(), 'value'))->default(CategoryMemorySource::UserConfirmed->value);
            $table->timestamp('last_matched_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'normalized_phrase']);
            $table->index(['user_id', 'is_active', 'confidence_score']);
            $table->index(['user_id', 'category_id', 'match_count']);
            $table->index('last_matched_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_memories');
    }
};
