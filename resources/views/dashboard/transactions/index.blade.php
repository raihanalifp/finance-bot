<x-layouts.dashboard title="Transactions" eyebrow="CRUD">
    @php
        $format = fn ($amount) => \Illuminate\Support\Number::currency((float) $amount, 'IDR', 'id');
    @endphp
    <section class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04]">
        @include('dashboard.partials.flash')
        <div class="flex items-center justify-between gap-4"><div><h2 class="text-lg font-black">Transactions</h2><p class="text-sm text-slate-500 dark:text-slate-400">Create, update, and delete financial transactions.</p></div><a href="{{ route('dashboard.transactions.create') }}" class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950">New transaction</a></div>
        <div class="mt-5 overflow-hidden rounded-3xl border border-slate-100 dark:border-white/10">
            @forelse ($transactions as $transaction)
                <div class="grid gap-3 border-b border-slate-100 p-4 last:border-0 dark:border-white/10 lg:grid-cols-[1fr_auto_auto_auto] lg:items-center">
                    <div><p class="font-black">{{ $transaction->description }}</p><p class="text-sm text-slate-500 dark:text-slate-400">{{ $transaction->category->name ?? 'Uncategorized' }} · {{ $transaction->transaction_date->format('d M Y') }}</p></div>
                    <span class="text-sm font-bold">{{ $transaction->type->value }}</span>
                    <span class="font-black">{{ $format($transaction->amount) }}</span>
                    <x-dashboard.partials.actions :show="route('dashboard.transactions.show', $transaction)" :edit="route('dashboard.transactions.edit', $transaction)" :destroy="route('dashboard.transactions.destroy', $transaction)" />
                </div>
            @empty
                <div class="p-8 text-center text-sm text-slate-500 dark:text-slate-400">No transactions yet.</div>
            @endforelse
        </div>
        <div class="mt-5">{{ $transactions->links() }}</div>
    </section>
</x-layouts.dashboard>
