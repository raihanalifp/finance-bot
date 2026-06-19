<x-layouts.dashboard title="Dashboard" eyebrow="Overview">
    @php
        $format = fn ($amount) => \Illuminate\Support\Number::currency((float) $amount, 'IDR', 'id');
        $topTotal = max(array_sum(array_column($topCategories, 'total')), 1);
    @endphp

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-dashboard.stat-card label="Pemasukan bulan ini" :value="$format($incomeTotal)" trend="Bulan ini" tone="green" />
        <x-dashboard.stat-card label="Pengeluaran bulan ini" :value="$format($expenseTotal)" trend="Bulan ini" tone="red" />
        <x-dashboard.stat-card label="Saldo berjalan" :value="$format($runningBalance)" trend="Real data" tone="blue" />
        <x-dashboard.stat-card label="Savings rate" :value="$savingsRate.'%'" trend="Bulan ini" tone="slate" />
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        <div class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black tracking-tight">Cashflow</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Pemasukan vs pengeluaran 14 hari terakhir.</p>
                </div>
                <div class="flex gap-2 text-xs font-bold">
                    <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-emerald-600 dark:text-emerald-300">Income</span>
                    <span class="rounded-full bg-rose-500/10 px-3 py-1 text-rose-600 dark:text-rose-300">Expense</span>
                </div>
            </div>
            <div class="mt-6 h-72"><canvas id="cashflowChart"></canvas></div>
        </div>

        <div class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20">
            <h2 class="text-lg font-black tracking-tight">Top kategori pengeluaran</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kategori paling aktif bulan ini.</p>
            <div class="mt-6 space-y-4">
                @forelse ($topCategories as $category)
                    @php
                        $percentage = round(((float) $category['total'] / $topTotal) * 100);
                    @endphp
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <div class="flex items-center gap-2 font-bold">
                                <span class="h-3 w-3 rounded-full" style="background: {{ $category['color'] }}"></span>
                                {{ $category['name'] }}
                            </div>
                            <span class="text-slate-500 dark:text-slate-400">{{ $format($category['total']) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 dark:bg-white/10">
                            <div class="h-2 rounded-full" style="width: {{ $percentage }}%; background: {{ $category['color'] }}"></div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
                        Belum ada pengeluaran bulan ini.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="mt-6 rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-black tracking-tight">Recent transactions</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">Aktivitas terbaru dari Telegram dan dashboard.</p>
            </div>
            <a href="{{ route('dashboard.transactions') }}" class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950">Lihat semua</a>
        </div>
        <div class="mt-5 divide-y divide-slate-100 dark:divide-white/10">
            @forelse ($recentTransactions as $transaction)
                <div class="flex items-center justify-between gap-4 py-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-sm font-black text-white" style="background: {{ $transaction->category->color ?? '#64748b' }}">{{ strtoupper(substr($transaction->description, 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="truncate font-bold">{{ $transaction->description }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $transaction->category->name ?? 'Uncategorized' }} · {{ $transaction->transaction_date->format('d M Y') }}</p>
                        </div>
                    </div>
                    <div @class(['shrink-0 text-right font-black', 'text-emerald-600 dark:text-emerald-300' => $transaction->type->value === 'income', 'text-rose-600 dark:text-rose-300' => $transaction->type->value === 'expense'])>
                        {{ $transaction->type->value === 'income' ? '+' : '-' }}{{ $format($transaction->amount) }}
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 dark:border-white/10 dark:text-slate-400">
                    Belum ada transaksi. Tambahkan transaksi dari Telegram atau halaman Transactions.
                </div>
            @endforelse
        </div>
    </section>

    @push('scripts')
        <script>
            new Chart(document.getElementById('cashflowChart'), {
                type: 'line',
                data: {
                    labels: @json($cashflow['labels']),
                    datasets: [
                        { label: 'Income', data: @json($cashflow['income']), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', tension: .45, fill: true, pointRadius: 0, borderWidth: 3 },
                        { label: 'Expense', data: @json($cashflow['expense']), borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,.10)', tension: .45, fill: true, pointRadius: 0, borderWidth: 3 },
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: 'rgba(148,163,184,.18)' } } } }
            })
        </script>
    @endpush
</x-layouts.dashboard>
