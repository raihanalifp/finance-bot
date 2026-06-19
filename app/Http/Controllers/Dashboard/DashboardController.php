<?php

namespace App\Http\Controllers\Dashboard;

use App\DTOs\Reports\ReportFilterData;
use App\Http\Controllers\Controller;
use App\Models\MonthlyBudget;
use App\Services\Budgets\BudgetUsageService;
use App\Services\Dashboard\DashboardDataService;
use App\Services\Reports\FinancialReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardDataService $dashboardDataService) {}

    public function dashboard(): View
    {
        return view('dashboard.index', $this->dashboardDataService->overview());
    }

    public function transactions(): View
    {
        return view('dashboard.transactions', $this->dashboardDataService->transactions());
    }

    public function categories(): View
    {
        return view('dashboard.categories', [
            'categories' => $this->dashboardDataService->categories(),
        ]);
    }

    public function budget(BudgetUsageService $budgetUsageService): View
    {
        return view('dashboard.budget', [
            'budgets' => MonthlyBudget::query()->with('category')->where('user_id', auth()->id())->latest('year')->latest('month')->get(),
            'budgetUsages' => $budgetUsageService->activeUsages(auth()->id()),
            'expenseCategories' => $this->dashboardDataService->expenseCategories(),
        ]);
    }

    public function reports(Request $request, FinancialReportService $reportService): View
    {
        return view('dashboard.reports', [
            ...$reportService->build(ReportFilterData::fromRequest($request->query())),
        ]);
    }

    public function settings(): View
    {
        return view('dashboard.settings', [
            'settings' => $this->dashboardDataService->settings(),
        ]);
    }
}
