<?php

namespace App\Services\Transactions;

use App\DTOs\Categories\CategoryResolutionData;
use App\DTOs\Transactions\ParsedTransactionData;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use App\Services\Categories\CategoryMemoryService;

class TransactionCategoryResolver
{
    public function __construct(private readonly CategoryMemoryService $categoryMemoryService) {}

    public function resolve(User $user, ParsedTransactionData $parsed): CategoryResolutionData
    {
        $explicitCategory = $this->resolveExplicitCategory($user, $parsed);

        if ($explicitCategory) {
            return new CategoryResolutionData(
                category: $explicitCategory,
                confidenceScore: 95,
                reason: "Kategori dipilih dari input sebagai {$explicitCategory->name}.",
                strategy: 'explicit_category_slug',
                requiresConfirmation: false,
            );
        }

        if ($parsed->type === TransactionType::Expense) {
            $memoryResolution = $this->categoryMemoryService->resolve(
                $user,
                $parsed->description ?? '',
                $parsed->type,
            );

            if ($memoryResolution->category && ! $memoryResolution->requiresConfirmation) {
                return $memoryResolution;
            }

            $ruleCategory = $this->resolveExpenseCategory($user, $parsed->description ?? '');

            if ($ruleCategory) {
                return new CategoryResolutionData(
                    category: $ruleCategory,
                    confidenceScore: 80,
                    reason: "Cocok dengan rule keyword bawaan untuk {$ruleCategory->name}.",
                    strategy: 'rule_keyword',
                    requiresConfirmation: false,
                );
            }

            return $memoryResolution;
        }

        $category = Category::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::Income)
            ->where(function ($query): void {
                $query->where('slug', 'salary')->orWhere('slug', 'other-income');
            })
            ->orderByRaw("case when slug = 'salary' then 0 else 1 end")
            ->first();

        if (! $category) {
            return CategoryResolutionData::unresolved('Kategori income default belum tersedia.');
        }

        return new CategoryResolutionData(
            category: $category,
            confidenceScore: 75,
            reason: "Transaksi terdeteksi sebagai income, memakai kategori {$category->name}.",
            strategy: 'income_default',
            requiresConfirmation: false,
        );
    }

    public function choiceCategories(User $user): array
    {
        $preferredSlugs = ['food-drink', 'transport', 'shopping', 'entertainment', 'other-expense'];

        $categories = Category::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::Expense)
            ->whereIn('slug', $preferredSlugs)
            ->get()
            ->keyBy('slug');

        return array_values(array_filter(array_map(
            fn (string $slug): ?Category => $categories->get($slug),
            $preferredSlugs
        )));
    }

    private function resolveExpenseCategory(User $user, string $description): ?Category
    {
        $text = strtolower($description);
        $keywordMap = [
            'food-drink' => ['kopi', 'makan', 'nasi', 'minum', 'sarapan', 'siang', 'malam', 'warung', 'resto'],
            'transport' => ['parkir', 'grab', 'gojek', 'bensin', 'tol', 'transport', 'ojek'],
            'shopping' => ['belanja', 'beli', 'mall', 'shopee', 'tokopedia'],
            'entertainment' => ['film', 'netflix', 'game', 'hiburan', 'bioskop'],
            'bills' => ['subscription', 'langganan', 'tagihan', 'listrik', 'air', 'internet'],
        ];

        foreach ($keywordMap as $slug => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return Category::query()
                        ->where('user_id', $user->id)
                        ->where('type', TransactionType::Expense)
                        ->where('slug', $slug)
                        ->first();
                }
            }
        }

        return null;
    }

    private function resolveExplicitCategory(User $user, ParsedTransactionData $parsed): ?Category
    {
        if (! $parsed->categorySlug || ! $parsed->type) {
            return null;
        }

        return Category::query()
            ->where('user_id', $user->id)
            ->where('type', $parsed->type)
            ->where('is_active', true)
            ->where('slug', $parsed->categorySlug)
            ->first();
    }

    public function fallbackExpenseCategory(User $user): ?Category
    {
        return Category::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::Expense)
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('slug', 'other-expense')->orWhere('slug', 'food-drink');
            })
            ->orderByRaw("case when slug = 'other-expense' then 0 else 1 end")
            ->first();
    }
}
