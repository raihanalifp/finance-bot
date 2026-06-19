<x-layouts.dashboard title="Budget" eyebrow="Planning">
    @php
        $format = fn ($amount) => \Illuminate\Support\Number::currency((float) $amount, 'IDR', 'id');
    @endphp
    <section class="grid gap-6 xl:grid-cols-[1fr_.8fr]">
        <div class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04]">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black">Monthly budget</h2><a href="{{ route('dashboard.budgets.create') }}" class="mt-3 inline-block rounded-2xl bg-slate-950 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950">New budget</a>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Atur batas pengeluaran bulanan per kategori. Bot mengirim alert di 80% dan 100%.</p>
                </div>
            </div>

            @if (session('status'))
                <div class="mt-5 rounded-3xl bg-emerald-500/10 p-4 text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            <div class="mt-6 space-y-4">
                @forelse ($budgetUsages as $usage)
                    @php
                        $tone = $usage->percentage >= 100 ? 'from-rose-500 to-red-500' : ($usage->percentage >= 80 ? 'from-amber-400 to-orange-500' : 'from-indigo-500 to-cyan-400');
                    @endphp
                    <div class="rounded-3xl border border-slate-100 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.03]">
                        <div class="mb-3 flex items-start justify-between gap-4">
                            <div>
                                <p class="font-black">{{ $usage->budget->category->name ?? 'Total Budget' }}</p>
                                <p class="text-sm text-slate-500 dark:text-slate-400">{{ str_pad((string) $usage->budget->month, 2, '0', STR_PAD_LEFT) }}/{{ $usage->budget->year }} · {{ $usage->transactionCount }} transaksi</p>
                            </div>
                            <div class="text-right">
                                <p class="font-black">{{ $format($usage->budget->amount) }}</p>
                                @php
                                    $percentageClass = 'text-xs font-black text-slate-500 dark:text-slate-400';
                                    if ($usage->percentage >= 100) {
                                        $percentageClass = 'text-xs font-black text-rose-600 dark:text-rose-300';
                                    } elseif ($usage->percentage >= 80) {
                                        $percentageClass = 'text-xs font-black text-amber-600 dark:text-amber-300';
                                    }
                                @endphp
                                <p class="{{ $percentageClass }}">{{ $usage->percentage }}%</p>
                            </div>
                        </div>
                        <div class="h-3 rounded-full bg-slate-100 dark:bg-white/10"><div class="h-3 rounded-full bg-gradient-to-r {{ $tone }}" style="width: {{ min(100, $usage->percentage) }}%"></div></div>
                        <div class="mt-3 grid gap-2 text-xs font-bold text-slate-500 dark:text-slate-400 sm:grid-cols-3">
                            <span>Terpakai: {{ $format($usage->spentAmount) }}</span>
                            <span>Sisa: {{ $format($usage->remainingAmount) }}</span>
                            <span>Status: {{ $usage->percentage >= 100 ? 'Exceeded' : ($usage->percentage >= 80 ? 'Warning' : 'Safe') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 p-8 text-center dark:border-white/10">
                        <p class="font-black">Belum ada budget bulan ini</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Buat budget untuk Makanan, Transportasi, atau kategori lain.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04]">
                <h2 class="text-lg font-black">Set monthly budget</h2>
                <form method="POST" action="{{ route('dashboard.budget.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div>
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300">Kategori</label>
                        <select name="category_id" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none dark:border-white/10 dark:bg-white/10">
                            <option value="">Total Budget</option>
                            @foreach ($expenseCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm font-bold text-slate-600 dark:text-slate-300">Bulan</label>
                            <input name="month" type="number" min="1" max="12" value="{{ now()->month }}" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none dark:border-white/10 dark:bg-white/10">
                        </div>
                        <div>
                            <label class="text-sm font-bold text-slate-600 dark:text-slate-300">Tahun</label>
                            <input name="year" type="number" value="{{ now()->year }}" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none dark:border-white/10 dark:bg-white/10">
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300">Nominal</label>
                        <input name="amount" type="number" min="1" step="1000" placeholder="2000000" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none dark:border-white/10 dark:bg-white/10">
                    </div>
                    <input type="hidden" name="currency" value="IDR">
                    <div>
                        <label class="text-sm font-bold text-slate-600 dark:text-slate-300">Catatan</label>
                        <textarea name="notes" rows="3" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none dark:border-white/10 dark:bg-white/10" placeholder="Contoh: Budget makan bulan ini"></textarea>
                    </div>
                    <button class="w-full rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">Simpan budget</button>
                </form>
            </div>

            <div class="rounded-[2rem] border border-white/70 bg-gradient-to-br from-slate-950 to-indigo-950 p-6 text-white shadow-2xl dark:border-white/10">
                <p class="text-sm font-bold uppercase tracking-[.25em] text-cyan-300">Telegram Alerts</p>
                <h2 class="mt-4 text-4xl font-black tracking-tight">80% / 100%</h2>
                <p class="mt-3 text-white/65">Setiap transaksi expense dari Telegram akan mengecek budget kategori dan total budget bulan berjalan.</p>
                <div class="mt-8 space-y-3 text-sm font-bold">
                    <div class="rounded-2xl bg-white/10 p-4">⚠️ 80%: Budget hampir habis</div>
                    <div class="rounded-2xl bg-white/10 p-4">🚨 100%: Budget terlampaui</div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.dashboard>
