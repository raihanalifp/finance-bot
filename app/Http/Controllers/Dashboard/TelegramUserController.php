<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AuditAction;
use App\Http\Controllers\Concerns\AuthorizesOwnedResources;
use App\Http\Controllers\Controller;
use App\Models\TelegramUser;
use App\Services\Security\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TelegramUserController extends Controller
{
    use AuthorizesOwnedResources;

    public function index(): View { return view('dashboard.telegram-users.index', ['telegramUsers' => TelegramUser::query()->where('user_id', auth()->id())->latest()->paginate(20)]); }
    public function create(): View { return view('dashboard.telegram-users.form', ['telegramUser' => new TelegramUser(['is_authorized' => true])]); }
    public function show(TelegramUser $telegramUser): View { $this->authorizeOwner($telegramUser); return view('dashboard.telegram-users.show', ['telegramUser' => $telegramUser]); }
    public function edit(TelegramUser $telegramUser): View { $this->authorizeOwner($telegramUser); return view('dashboard.telegram-users.form', ['telegramUser' => $telegramUser]); }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $telegramUser = TelegramUser::query()->create([...$this->validated($request), 'user_id' => auth()->id()]);
        $auditLogService->record(AuditAction::Created, $telegramUser, newValues: $telegramUser->toArray(), userId: auth()->id());
        return redirect()->route('dashboard.telegram-users.index')->with('status', 'Telegram user created successfully.');
    }

    public function update(Request $request, TelegramUser $telegramUser, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($telegramUser);

        $oldValues = $telegramUser->toArray();
        $telegramUser->update($this->validated($request));
        $auditLogService->record(AuditAction::Updated, $telegramUser, oldValues: $oldValues, newValues: $telegramUser->fresh()->toArray(), userId: auth()->id());
        return redirect()->route('dashboard.telegram-users.index')->with('status', 'Telegram user updated successfully.');
    }

    public function destroy(TelegramUser $telegramUser, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($telegramUser);

        $oldValues = $telegramUser->toArray();
        $telegramUser->delete();
        $auditLogService->record(AuditAction::Deleted, $telegramUser, oldValues: $oldValues, userId: auth()->id());
        return redirect()->route('dashboard.telegram-users.index')->with('status', 'Telegram user deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'telegram_chat_id' => ['required', 'string', 'max:64'],
            'telegram_user_id' => ['nullable', 'string', 'max:64'],
            'username' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'is_authorized' => ['nullable', 'boolean'],
        ]) + ['is_authorized' => $request->boolean('is_authorized')];
    }
}
