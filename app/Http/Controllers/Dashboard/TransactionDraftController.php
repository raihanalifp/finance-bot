<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\TransactionDraftStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Http\Controllers\Concerns\AuthorizesOwnedResources;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\TransactionDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionDraftController extends Controller
{
    use AuthorizesOwnedResources;

    public function index(): View { return view('dashboard.transaction-drafts.index', ['drafts' => TransactionDraft::query()->with('category', 'telegramUser')->where('user_id', auth()->id())->latest()->paginate(20)]); }
    public function create(): View { return view('dashboard.transaction-drafts.form', ['draft' => new TransactionDraft(['status' => TransactionDraftStatus::Pending, 'source' => TransactionSource::Dashboard, 'currency' => 'IDR']), 'categories' => $this->categories(), 'telegramUsers' => $this->telegramUsers()]); }
    public function show(TransactionDraft $transactionDraft): View { $this->authorizeOwner($transactionDraft); return view('dashboard.transaction-drafts.show', ['draft' => $transactionDraft->load('category', 'telegramUser', 'transaction')]); }
    public function edit(TransactionDraft $transactionDraft): View { $this->authorizeOwner($transactionDraft); return view('dashboard.transaction-drafts.form', ['draft' => $transactionDraft, 'categories' => $this->categories(), 'telegramUsers' => $this->telegramUsers()]); }

    public function store(Request $request): RedirectResponse
    {
        TransactionDraft::query()->create([...$this->validated($request), 'user_id' => auth()->id()]);
        return redirect()->route('dashboard.transaction-drafts.index')->with('status', 'Draft created successfully.');
    }

    public function update(Request $request, TransactionDraft $transactionDraft): RedirectResponse
    {
        $this->authorizeOwner($transactionDraft);

        $transactionDraft->update($this->validated($request));
        return redirect()->route('dashboard.transaction-drafts.index')->with('status', 'Draft updated successfully.');
    }

    public function destroy(TransactionDraft $transactionDraft): RedirectResponse
    {
        $this->authorizeOwner($transactionDraft);

        $transactionDraft->delete();
        return redirect()->route('dashboard.transaction-drafts.index')->with('status', 'Draft deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'telegram_user_id' => ['nullable', Rule::exists('telegram_users', 'id')->where('user_id', auth()->id())],
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', auth()->id())],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'amount' => ['nullable', 'numeric', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['nullable', 'string', 'max:500'],
            'transaction_date' => ['nullable', 'date'],
            'transaction_time' => ['nullable', 'date_format:H:i'],
            'source' => ['required', Rule::enum(TransactionSource::class)],
            'raw_text' => ['required', 'string', 'max:5000'],
            'confidence_score' => ['nullable', 'integer', 'between:0,100'],
            'status' => ['required', Rule::enum(TransactionDraftStatus::class)],
            'expires_at' => ['nullable', 'date'],
        ]);
    }

    private function categories()
    {
        return Category::query()->where('user_id', auth()->id())->orderBy('type')->get();
    }

    private function telegramUsers()
    {
        return TelegramUser::query()->where('user_id', auth()->id())->get();
    }
}
