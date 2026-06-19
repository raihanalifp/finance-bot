@if (session('status'))
    <div class="mb-5 rounded-3xl bg-emerald-500/10 p-4 text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="mb-5 rounded-3xl bg-rose-500/10 p-4 text-sm font-bold text-rose-700 dark:text-rose-300">
        <p>Please fix the following errors:</p>
        <ul class="mt-2 list-disc pl-5 font-medium">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
