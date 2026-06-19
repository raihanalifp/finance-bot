<?php

namespace App\DTOs\Categories;

use App\Models\Category;
use App\Models\CategoryMemory;

final readonly class CategoryResolutionData
{
    public function __construct(
        public ?Category $category,
        public int $confidenceScore,
        public string $reason,
        public string $strategy,
        public ?CategoryMemory $memory = null,
        public bool $requiresConfirmation = true,
    ) {}

    public static function unresolved(string $reason = 'Kategori belum dapat dipastikan.'): self
    {
        return new self(
            category: null,
            confidenceScore: 0,
            reason: $reason,
            strategy: 'manual_fallback',
            requiresConfirmation: true,
        );
    }
}
