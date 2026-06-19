<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AuditAction;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Http\Controllers\Concerns\AuthorizesOwnedResources;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\Security\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionController extends Controller
{
    use AuthorizesOwnedResources;

    public function index(): View
    {
        return view('dashboard.transactions.index', [
            'transactions' => Transaction::query()
                ->with('category')
                ->where('user_id', auth()->id())
                ->latest('transaction_date')
                ->latest('created_at')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.transactions.form', [
            'transaction' => new Transaction(['transaction_date' => now()->toDateString(), 'currency' => 'IDR']),
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $data = $this->validated($request);
        $userId = auth()->id();

        $transaction = Transaction::query()->create([
            ...$data,
            'user_id' => $userId,
            'source' => TransactionSource::Dashboard,
            'created_by' => $userId,
        ]);

        $auditLogService->record(AuditAction::Created, $transaction, newValues: $transaction->toArray(), userId: $userId);

        return redirect()->route('dashboard.transactions')->with('status', 'Transaction created successfully.');
    }

    public function show(Transaction $transaction): View
    {
        $this->authorizeOwner($transaction);

        return view('dashboard.transactions.show', ['transaction' => $transaction->load('category', 'telegramUser')]);
    }

    public function edit(Transaction $transaction): View
    {
        $this->authorizeOwner($transaction);

        return view('dashboard.transactions.form', [
            'transaction' => $transaction,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Transaction $transaction, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($transaction);

        $oldValues = $transaction->toArray();
        $transaction->update([...$this->validated($request), 'updated_by' => auth()->id()]);

        $auditLogService->record(AuditAction::Updated, $transaction, oldValues: $oldValues, newValues: $transaction->fresh()->toArray(), userId: auth()->id());

        return redirect()->route('dashboard.transactions')->with('status', 'Transaction updated successfully.');
    }

    public function destroy(Transaction $transaction, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($transaction);

        $oldValues = $transaction->toArray();
        $transaction->delete();

        $auditLogService->record(AuditAction::Deleted, $transaction, oldValues: $oldValues, userId: auth()->id());

        return redirect()->route('dashboard.transactions')->with('status', 'Transaction deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', auth()->id())],
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'date_format:H:i'],
        ]);
    }

    private function categories()
    {
        return Category::query()
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->get();
    }
}
