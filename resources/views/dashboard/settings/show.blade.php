<x-layouts.dashboard title="Setting Detail" eyebrow="Settings">
    <section class="rounded-[2rem] border border-white/70 bg-white/75 p-5 dark:border-white/10 dark:bg-white/[0.04]">
        <h2 class="text-xl font-black">{{ $setting->key }}</h2>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <div><dt class="text-sm text-slate-500">Group</dt><dd>{{ $setting->group }}</dd></div>
            <div><dt class="text-sm text-slate-500">Type</dt><dd>{{ $setting->type->value }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Value</dt><dd class="break-words">{{ $setting->is_encrypted ? 'Encrypted' : $setting->value }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Description</dt><dd>{{ $setting->description ?: '-' }}</dd></div>
        </dl>
        <a href="{{ route('dashboard.settings') }}" class="mt-6 inline-block rounded-2xl bg-slate-100 px-5 py-3 text-sm font-black dark:bg-white/10">Back</a>
    </section>
</x-layouts.dashboard>
