<x-layouts.dashboard :title="$setting->exists ? 'Edit Setting' : 'Create Setting'" eyebrow="Settings">
    <section class="max-w-2xl rounded-[2rem] border border-white/70 bg-white/75 p-5 dark:border-white/10 dark:bg-white/[0.04]">
        @include('dashboard.partials.flash')
        <form method="POST" action="{{ $setting->exists ? route('dashboard.settings.update', $setting) : route('dashboard.settings.store') }}" class="space-y-4">
            @csrf
            @if($setting->exists) @method('PUT') @endif
            <div><label class="text-sm font-bold">Key</label><input name="key" value="{{ old('key', $setting->key) }}" class="mt-2 w-full rounded-2xl border px-4 py-3 dark:bg-white/10"></div>
            <div><label class="text-sm font-bold">Value</label><textarea name="value" rows="4" class="mt-2 w-full rounded-2xl border px-4 py-3 dark:bg-white/10">{{ old('value', $setting->value) }}</textarea></div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="text-sm font-bold">Type</label><select name="type" class="mt-2 w-full rounded-2xl border px-4 py-3 dark:bg-white/10">@foreach(\App\Enums\SettingType::cases() as $type)<option value="{{ $type->value }}" @selected(old('type', $setting->type?->value) === $type->value)>{{ $type->value }}</option>@endforeach</select></div>
                <div><label class="text-sm font-bold">Group</label><input name="group" value="{{ old('group', $setting->group ?? 'general') }}" class="mt-2 w-full rounded-2xl border px-4 py-3 dark:bg-white/10"></div>
            </div>
            <div><label class="text-sm font-bold">Description</label><textarea name="description" rows="3" class="mt-2 w-full rounded-2xl border px-4 py-3 dark:bg-white/10">{{ old('description', $setting->description) }}</textarea></div>
            <label class="flex items-center gap-2 text-sm font-bold"><input type="checkbox" name="is_encrypted" value="1" @checked(old('is_encrypted', $setting->is_encrypted))> Encrypted</label>
            <div class="flex gap-3"><button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">Save</button><a href="{{ route('dashboard.settings') }}" class="rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black dark:bg-white/10">Cancel</a></div>
        </form>
    </section>
</x-layouts.dashboard>
