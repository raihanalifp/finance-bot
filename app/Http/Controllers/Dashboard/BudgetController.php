<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreMonthlyBudgetRequest;
use App\Models\MonthlyBudget;
use App\Services\Security\AuditLogService;
use Illuminate\Http\RedirectResponse;

class BudgetController extends Controller
{
    public function store(StoreMonthlyBudgetRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $data = $request->validated();

        $budget = MonthlyBudget::query()->updateOrCreate(
            [
                'user_id' => auth()->id(),
                'category_id' => $data['category_id'] ?? null,
                'year' => (int) $data['year'],
                'month' => (int) $data['month'],
            ],
            [
                'amount' => $data['amount'],
                'currency' => strtoupper($data['currency']),
                'is_active' => true,
                'notes' => $data['notes'] ?? null,
            ]
        );

        $auditLogService->record(
            $budget->wasRecentlyCreated ? AuditAction::Created : AuditAction::Updated,
            entity: $budget,
            newValues: $budget->only(['user_id', 'category_id', 'year', 'month', 'amount', 'currency', 'is_active']),
            context: ['source' => 'dashboard_budget_form'],
            userId: auth()->id(),
        );

        return back()->with('status', 'Budget bulanan berhasil disimpan.');
    }
}
