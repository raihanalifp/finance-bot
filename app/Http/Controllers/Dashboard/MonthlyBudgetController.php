<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AuditAction;
use App\Enums\TransactionType;
use App\Http\Controllers\Concerns\AuthorizesOwnedResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\StoreMonthlyBudgetRequest;
use App\Models\Category;
use App\Models\MonthlyBudget;
use App\Services\Budgets\BudgetUsageService;
use App\Services\Security\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MonthlyBudgetController extends Controller
{
    use AuthorizesOwnedResources;

    public function index(BudgetUsageService $budgetUsageService): View
    {
        return view('dashboard.budget.index', [
            'budgets' => MonthlyBudget::query()->with('category')->where('user_id', auth()->id())->latest('year')->latest('month')->paginate(20),
            'budgetUsages' => $budgetUsageService->activeUsages(auth()->id()),
            'expenseCategories' => $this->expenseCategories(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.budget.form', [
            'budget' => new MonthlyBudget(['year' => now()->year, 'month' => now()->month, 'currency' => 'IDR', 'is_active' => true]),
            'expenseCategories' => $this->expenseCategories(),
        ]);
    }

    public function store(StoreMonthlyBudgetRequest $request, AuditLogService $auditLogService): RedirectResponse
    {
        $budget = MonthlyBudget::query()->create([...$request->validated(), 'user_id' => auth()->id(), 'is_active' => true]);
        $auditLogService->record(AuditAction::Created, $budget, newValues: $budget->toArray(), userId: auth()->id());

        return redirect()->route('dashboard.budget')->with('status', 'Budget created successfully.');
    }

    public function show(MonthlyBudget $budget, BudgetUsageService $budgetUsageService): View
    {
        $this->authorizeOwner($budget);

        return view('dashboard.budget.show', ['budget' => $budget->load('category'), 'usage' => $budgetUsageService->usage($budget)]);
    }

    public function edit(MonthlyBudget $budget): View
    {
        $this->authorizeOwner($budget);

        return view('dashboard.budget.form', ['budget' => $budget, 'expenseCategories' => $this->expenseCategories()]);
    }

    public function update(StoreMonthlyBudgetRequest $request, MonthlyBudget $budget, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($budget);

        $oldValues = $budget->toArray();
        $budget->update([...$request->validated(), 'is_active' => true]);
        $auditLogService->record(AuditAction::Updated, $budget, oldValues: $oldValues, newValues: $budget->fresh()->toArray(), userId: auth()->id());

        return redirect()->route('dashboard.budget')->with('status', 'Budget updated successfully.');
    }

    public function destroy(MonthlyBudget $budget, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($budget);

        $oldValues = $budget->toArray();
        $budget->delete();
        $auditLogService->record(AuditAction::Deleted, $budget, oldValues: $oldValues, userId: auth()->id());

        return redirect()->route('dashboard.budget')->with('status', 'Budget deleted successfully.');
    }

    private function expenseCategories()
    {
        return Category::query()->where('user_id', auth()->id())->where('type', TransactionType::Expense)->where('is_active', true)->orderBy('sort_order')->get();
    }
}
