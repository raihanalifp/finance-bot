<?php

namespace App\Services\Categories;

use App\DTOs\Categories\CategoryResolutionData;
use App\Enums\CategoryMemorySource;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\CategoryMemory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CategoryMemoryService
{
    private const AUTO_SELECT_THRESHOLD = 85;
    private const MIN_KEYWORD_LENGTH = 3;

    public function resolve(User $user, string $description, TransactionType $type): CategoryResolutionData
    {
        if ($type !== TransactionType::Expense) {
            return CategoryResolutionData::unresolved('Category memory hanya diterapkan untuk expense pada fase ini.');
        }

        $normalized = $this->normalize($description);

        if ($normalized === '') {
            return CategoryResolutionData::unresolved('Deskripsi kosong.');
        }

        $exactMemory = CategoryMemory::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->where('normalized_phrase', $normalized)
            ->where('is_active', true)
            ->first();

        if ($exactMemory && $exactMemory->category) {
            $confidence = min(100, max($exactMemory->confidence_score, 90) + min($exactMemory->match_count, 5));
            $this->recordMatch($exactMemory, $confidence);

            return new CategoryResolutionData(
                category: $exactMemory->category,
                confidenceScore: $confidence,
                reason: "Pola yang sama pernah dipilih sebagai {$exactMemory->category->name}.",
                strategy: 'memory_exact_phrase',
                memory: $exactMemory,
                requiresConfirmation: $confidence < self::AUTO_SELECT_THRESHOLD,
            );
        }

        $memories = CategoryMemory::query()
            ->with('category')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('type', $type))
            ->orderByDesc('confidence_score')
            ->orderByDesc('match_count')
            ->limit(100)
            ->get();

        $best = $this->bestKeywordMatch($memories, $normalized);

        if (! $best) {
            return CategoryResolutionData::unresolved('Belum ada memory kategori yang cocok.');
        }

        [$memory, $confidence, $matchedKeywords] = $best;
        $this->recordMatch($memory, $confidence);

        return new CategoryResolutionData(
            category: $memory->category,
            confidenceScore: $confidence,
            reason: 'Cocok dengan keyword memory: '.implode(', ', $matchedKeywords).'.',
            strategy: 'memory_keyword_overlap',
            memory: $memory,
            requiresConfirmation: $confidence < self::AUTO_SELECT_THRESHOLD,
        );
    }

    public function learn(User $user, Category $category, string $description, array $metadata = []): CategoryMemory
    {
        $normalized = $this->normalize($description);
        $keywords = $this->keywords($normalized);

        $memory = CategoryMemory::query()->firstOrNew([
            'user_id' => $user->id,
            'normalized_phrase' => $normalized,
        ]);

        $memory->fill([
            'category_id' => $category->id,
            'phrase' => $description,
            'keywords' => $keywords,
            'confidence_score' => $memory->exists ? min(100, $memory->confidence_score + 5) : 85,
            'match_count' => $memory->exists ? $memory->match_count + 1 : 1,
            'source' => CategoryMemorySource::UserConfirmed,
            'last_matched_at' => now(),
            'is_active' => true,
            'metadata' => array_merge($memory->metadata ?? [], $metadata),
        ]);

        $memory->save();

        return $memory;
    }

    public function normalize(string $description): string
    {
        return Str::of($description)
            ->lower()
            ->replaceMatches('/[^a-z0-9\pL\s]/u', ' ')
            ->squish()
            ->toString();
    }

    /** @return array<int, string> */
    private function keywords(string $normalized): array
    {
        $stopWords = ['dan', 'di', 'ke', 'dari', 'yang', 'untuk', 'the', 'a', 'an'];

        return array_values(array_unique(array_filter(
            explode(' ', $normalized),
            fn (string $word): bool => mb_strlen($word) >= self::MIN_KEYWORD_LENGTH && ! in_array($word, $stopWords, true)
        )));
    }

    private function bestKeywordMatch(Collection $memories, string $normalized): ?array
    {
        $inputKeywords = $this->keywords($normalized);
        $best = null;

        foreach ($memories as $memory) {
            $memoryKeywords = $memory->keywords ?? [];
            $matchedKeywords = array_values(array_intersect($inputKeywords, $memoryKeywords));

            if ($matchedKeywords === []) {
                continue;
            }

            $coverage = count($matchedKeywords) / max(count($memoryKeywords), 1);
            $inputCoverage = count($matchedKeywords) / max(count($inputKeywords), 1);
            $confidence = (int) min(95, round(($memory->confidence_score * 0.55) + ($coverage * 25) + ($inputCoverage * 20)));

            if (! $best || $confidence > $best[1]) {
                $best = [$memory, $confidence, $matchedKeywords];
            }
        }

        return $best;
    }

    private function recordMatch(CategoryMemory $memory, int $confidence): void
    {
        $memory->update([
            'confidence_score' => max($memory->confidence_score, $confidence),
            'match_count' => $memory->match_count + 1,
            'last_matched_at' => now(),
        ]);
    }
}
