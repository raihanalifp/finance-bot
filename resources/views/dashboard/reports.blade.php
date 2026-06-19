<x-layouts.dashboard title="Reports" eyebrow="Analytics Engine">
    @php
        $format = fn ($amount) => \Illuminate\Support\Number::currency((float) $amount, 'IDR', 'id');
        $periods = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'yearly' => 'Tahunan'];
        $largestCategory = $summary->largestCategory;
    @endphp

    <section class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-lg font-black tracking-tight">Financial reporting engine</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Periode {{ $filter->period->label() }} · {{ $filter->startDate->format('d M Y') }} - {{ $filter->endDate->format('d M Y') }}
                </p>
            </div>
            <form class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]" method="GET" action="{{ route('dashboard.reports') }}">
                <select name="period" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none dark:border-white/10 dark:bg-white/10">
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}" @selected($filter->period->value === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input name="date" type="date" value="{{ request('date', now()->toDateString()) }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none dark:border-white/10 dark:bg-white/10">
                <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">Apply</button>
            </form>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-dashboard.stat-card label="Total pemasukan" :value="$format($summary->incomeTotal)" trend="Income" tone="green" />
        <x-dashboard.stat-card label="Total pengeluaran" :value="$format($summary->expenseTotal)" trend="Expense" tone="red" />
        <x-dashboard.stat-card label="Rata-rata harian" :value="$format($summary->averageDailyExpense)" trend="Daily avg" tone="blue" />
        <x-dashboard.stat-card label="Rata-rata bulanan" :value="$format($summary->averageMonthlyExpense)" trend="Monthly avg" tone="slate" />
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_.85fr]">
        <div class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-black">Tren cashflow</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Line chart income, expense, dan net cashflow.</p>
                </div>
                <span @class([
                    'rounded-full px-3 py-1 text-xs font-black',
                    'bg-rose-500/10 text-rose-600 dark:text-rose-300' => $summary->expenseTrend === 'Naik',
                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-300' => $summary->expenseTrend === 'Turun',
                    'bg-slate-500/10 text-slate-600 dark:text-slate-300' => ! in_array($summary->expenseTrend, ['Naik', 'Turun'], true),
                ])>Tren pengeluaran: {{ $summary->expenseTrend }}</span>
            </div>
            <div class="mt-6 h-80"><canvas id="lineReportChart"></canvas></div>
        </div>

        <div class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04]">
            <h2 class="text-lg font-black">Analisis utama</h2>
            <div class="mt-5 space-y-4">
                <div class="rounded-3xl bg-slate-100/80 p-4 dark:bg-white/10">
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Kategori terbesar</p>
                    <p class="mt-2 text-2xl font-black">{{ $largestCategory['name'] ?? 'Belum ada data' }}</p>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $largestCategory ? $format($largestCategory['total']) : 'Rp0' }}</p>
                </div>
                <div class="rounded-3xl bg-slate-100/80 p-4 dark:bg-white/10">
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Net cashflow</p>
                    <p @class(['mt-2 text-2xl font-black', 'text-emerald-600 dark:text-emerald-300' => $summary->netTotal >= 0, 'text-rose-600 dark:text-rose-300' => $summary->netTotal < 0])>{{ $format($summary->netTotal) }}</p>
                </div>
                <div class="rounded-3xl bg-slate-100/80 p-4 dark:bg-white/10">
                    <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">Jumlah transaksi</p>
                    <p class="mt-2 text-2xl font-black">{{ number_format($summary->transactionCount) }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04]">
            <h2 class="text-lg font-black">Kategori terbesar</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Bar chart total pengeluaran per kategori.</p>
            <div class="mt-6 h-80"><canvas id="barReportChart"></canvas></div>
        </div>
        <div class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04]">
            <h2 class="text-lg font-black">Distribusi pengeluaran</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Pie chart proporsi kategori.</p>
            <div class="mt-6 h-80"><canvas id="pieReportChart"></canvas></div>
        </div>
    </section>

    <section class="mt-6 rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04]">
        <h2 class="text-lg font-black">Detail kategori</h2>
        <div class="mt-5 divide-y divide-slate-100 dark:divide-white/10">
            @forelse ($categoryBreakdown as $category)
                <div class="grid gap-3 py-4 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                    <div class="flex items-center gap-3">
                        <span class="h-10 w-10 rounded-2xl" style="background: {{ $category['color'] }}"></span>
                        <div>
                            <p class="font-black">{{ $category['name'] }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">{{ $category['transaction_count'] }} transaksi</p>
                        </div>
                    </div>
                    <p class="text-sm font-bold text-slate-500 dark:text-slate-400">{{ round(($category['total'] / max($summary->expenseTotal, 1)) * 100, 1) }}%</p>
                    <p class="font-black text-rose-600 dark:text-rose-300">{{ $format($category['total']) }}</p>
                </div>
            @empty
                <p class="py-8 text-sm text-slate-500 dark:text-slate-400">Belum ada transaksi expense pada periode ini.</p>
            @endforelse
        </div>
    </section>

    @push('scripts')
        <script>
            const chartGrid = 'rgba(148,163,184,.18)'
            const lineData = @json($lineChart)
            const barData = @json($barChart)
            const pieData = @json($pieChart)

            new Chart(document.getElementById('lineReportChart'), {
                type: 'line',
                data: {
                    labels: lineData.labels,
                    datasets: [
                        { label: 'Income', data: lineData.income, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.10)', tension: .42, fill: true, pointRadius: 0, borderWidth: 3 },
                        { label: 'Expense', data: lineData.expense, borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,.08)', tension: .42, fill: true, pointRadius: 0, borderWidth: 3 },
                        { label: 'Net', data: lineData.net, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.08)', tension: .42, fill: false, pointRadius: 0, borderWidth: 3 },
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { labels: { boxWidth: 10, usePointStyle: true } } }, scales: { x: { grid: { display: false } }, y: { grid: { color: chartGrid } } } }
            })

            new Chart(document.getElementById('barReportChart'), {
                type: 'bar',
                data: { labels: barData.labels, datasets: [{ label: 'Expense', data: barData.values, backgroundColor: barData.colors, borderRadius: 14, maxBarThickness: 54 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: chartGrid } } } }
            })

            new Chart(document.getElementById('pieReportChart'), {
                type: 'pie',
                data: { labels: pieData.labels, datasets: [{ data: pieData.values, backgroundColor: pieData.colors, borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } } } }
            })
        </script>
    @endpush
</x-layouts.dashboard>
