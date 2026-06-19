<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AuditAction;
use App\Enums\TransactionType;
use App\Http\Controllers\Concerns\AuthorizesOwnedResources;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Security\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use AuthorizesOwnedResources;

    public function index(): View
    {
        return view('dashboard.categories.index', [
            'categories' => Category::query()->where('user_id', auth()->id())->orderBy('type')->orderBy('sort_order')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.categories.form', ['category' => new Category(['type' => TransactionType::Expense, 'is_active' => true])]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $data = $this->validated($request);
        $data['user_id'] = auth()->id();
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);

        $category = Category::query()->create($data);
        $auditLogService->record(AuditAction::Created, $category, newValues: $category->toArray(), userId: auth()->id());

        return redirect()->route('dashboard.categories')->with('status', 'Category created successfully.');
    }

    public function show(Category $category): View
    {
        $this->authorizeOwner($category);

        return view('dashboard.categories.show', ['category' => $category->loadCount('transactions', 'memories')]);
    }

    public function edit(Category $category): View
    {
        $this->authorizeOwner($category);

        return view('dashboard.categories.form', ['category' => $category]);
    }

    public function update(Request $request, Category $category, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($category);

        $oldValues = $category->toArray();
        $data = $this->validated($request, $category);
        $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
        $category->update($data);

        $auditLogService->record(AuditAction::Updated, $category, oldValues: $oldValues, newValues: $category->fresh()->toArray(), userId: auth()->id());

        return redirect()->route('dashboard.categories')->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($category);

        if ($category->transactions()->exists()) {
            return back()->withErrors(['category' => 'Category cannot be deleted because it has transactions.']);
        }

        $oldValues = $category->toArray();
        $category->delete();
        $auditLogService->record(AuditAction::Deleted, $category, oldValues: $oldValues, userId: auth()->id());

        return redirect()->route('dashboard.categories')->with('status', 'Category deleted successfully.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $slug = Str::slug((string) ($request->input('slug') ?: $request->input('name')));
        $request->merge(['slug' => $slug]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->where('user_id', auth()->id())->where('type', $request->input('type'))->ignore($category?->id)],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'icon' => ['nullable', 'string', 'max:64'],
            'color' => ['nullable', 'string', 'max:32'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
