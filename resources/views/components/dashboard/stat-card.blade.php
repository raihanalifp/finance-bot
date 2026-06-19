@props(['label', 'value', 'trend' => null, 'tone' => 'slate'])
@php
$tones = [
    'green' => 'from-emerald-500/15 to-teal-500/5 text-emerald-600 dark:text-emerald-300',
    'red' => 'from-rose-500/15 to-orange-500/5 text-rose-600 dark:text-rose-300',
    'blue' => 'from-blue-500/15 to-indigo-500/5 text-blue-600 dark:text-blue-300',
    'slate' => 'from-slate-500/10 to-slate-500/5 text-slate-700 dark:text-slate-200',
];
@endphp
<div class="rounded-[2rem] border border-white/70 bg-white/75 p-5 shadow-xl shadow-slate-200/50 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.04] dark:shadow-black/20">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $label }}</p>
            <p class="mt-3 text-2xl font-black tracking-tight sm:text-3xl">{{ $value }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br {{ $tones[$tone] ?? $tones['slate'] }} px-3 py-2 text-xs font-black">{{ $trend ?? 'Live' }}</div>
    </div>
</div>
