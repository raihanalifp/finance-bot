<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AuditAction;
use App\Enums\SettingType;
use App\Http\Controllers\Concerns\AuthorizesOwnedResources;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Security\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingController extends Controller
{
    use AuthorizesOwnedResources;

    public function index(): View
    {
        return view('dashboard.settings.index', ['settings' => Setting::query()->where('user_id', auth()->id())->orderBy('group')->orderBy('key')->paginate(30)]);
    }

    public function create(): View
    {
        return view('dashboard.settings.form', ['setting' => new Setting(['type' => SettingType::String, 'group' => 'general'])]);
    }

    public function store(Request $request, AuditLogService $auditLogService): RedirectResponse
    {
        $setting = Setting::query()->create([...$this->validated($request), 'user_id' => auth()->id()]);
        $auditLogService->record(AuditAction::Created, $setting, newValues: $setting->toArray(), userId: auth()->id());

        return redirect()->route('dashboard.settings')->with('status', 'Setting created successfully.');
    }

    public function show(Setting $setting): View
    {
        $this->authorizeOwner($setting);

        return view('dashboard.settings.show', ['setting' => $setting]);
    }

    public function edit(Setting $setting): View
    {
        $this->authorizeOwner($setting);

        return view('dashboard.settings.form', ['setting' => $setting]);
    }

    public function update(Request $request, Setting $setting, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($setting);

        $oldValues = $setting->toArray();
        $setting->update($this->validated($request));
        $auditLogService->record(AuditAction::Updated, $setting, oldValues: $oldValues, newValues: $setting->fresh()->toArray(), userId: auth()->id());

        return redirect()->route('dashboard.settings')->with('status', 'Setting updated successfully.');
    }

    public function destroy(Setting $setting, AuditLogService $auditLogService): RedirectResponse
    {
        $this->authorizeOwner($setting);

        $oldValues = $setting->toArray();
        $setting->delete();
        $auditLogService->record(AuditAction::Deleted, $setting, oldValues: $oldValues, userId: auth()->id());

        return redirect()->route('dashboard.settings')->with('status', 'Setting deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'key' => ['required', 'string', 'max:255'],
            'value' => ['nullable', 'string', 'max:10000'],
            'type' => ['required', Rule::enum(SettingType::class)],
            'group' => ['required', 'string', 'max:255'],
            'is_encrypted' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]) + ['is_encrypted' => $request->boolean('is_encrypted')];
    }
}
