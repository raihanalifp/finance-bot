<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\TransactionLogStatus;
use App\Http\Controllers\Concerns\AuthorizesOwnedResources;
use App\Http\Controllers\Controller;
use App\Models\TransactionLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionLogController extends Controller
{
    use AuthorizesOwnedResources;

    public function index(): View { return view('dashboard.transaction-logs.index', ['logs' => TransactionLog::query()->where('user_id', auth()->id())->latest()->paginate(30)]); }
    public function create(): View { return view('dashboard.transaction-logs.form', ['log' => new TransactionLog(['status' => TransactionLogStatus::Received])]); }
    public function show(TransactionLog $transactionLog): View { $this->authorizeOwner($transactionLog); return view('dashboard.transaction-logs.show', ['log' => $transactionLog->load('transaction', 'transactionDraft', 'telegramUser')]); }
    public function edit(TransactionLog $transactionLog): View { $this->authorizeOwner($transactionLog); return view('dashboard.transaction-logs.form', ['log' => $transactionLog]); }

    public function store(Request $request): RedirectResponse
    {
        TransactionLog::query()->create([...$this->validated($request), 'user_id' => auth()->id()]);
        return redirect()->route('dashboard.transaction-logs.index')->with('status', 'Log created successfully.');
    }

    public function update(Request $request, TransactionLog $transactionLog): RedirectResponse
    {
        $this->authorizeOwner($transactionLog);

        $transactionLog->update($this->validated($request));
        return redirect()->route('dashboard.transaction-logs.index')->with('status', 'Log updated successfully.');
    }

    public function destroy(TransactionLog $transactionLog): RedirectResponse
    {
        $this->authorizeOwner($transactionLog);

        $transactionLog->delete();
        return redirect()->route('dashboard.transaction-logs.index')->with('status', 'Log deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'update_id' => ['nullable', 'string', 'max:64'],
            'chat_id' => ['nullable', 'string', 'max:64'],
            'message_id' => ['nullable', 'string', 'max:64'],
            'message_text' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(TransactionLogStatus::class)],
            'error_code' => ['nullable', 'string', 'max:100'],
            'error_message' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
