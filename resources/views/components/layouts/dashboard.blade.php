<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title ?? 'Finance Bot' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    </script>
</head>
<body class="min-h-full bg-slate-50 text-slate-950 antialiased dark:bg-[#070b16] dark:text-white">
    @php
        $authUser = auth()->user();
        $navigation = [
            ['label' => 'Dashboard', 'route' => 'dashboard.index', 'icon' => 'grid'],
            ['label' => 'Transactions', 'route' => 'dashboard.transactions', 'icon' => 'card'],
            ['label' => 'Categories', 'route' => 'dashboard.categories', 'icon' => 'tag'],
            ['label' => 'Budget', 'route' => 'dashboard.budget', 'icon' => 'wallet'],
            ['label' => 'Reports', 'route' => 'dashboard.reports', 'icon' => 'chart'],
            ['label' => 'Settings', 'route' => 'dashboard.settings', 'icon' => 'settings'],
            ['label' => 'Telegram Users', 'route' => 'dashboard.telegram-users.index', 'icon' => 'settings'],
            ['label' => 'Drafts', 'route' => 'dashboard.transaction-drafts.index', 'icon' => 'card'],
            ['label' => 'Logs', 'route' => 'dashboard.transaction-logs.index', 'icon' => 'chart'],
        ];
    @endphp

    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute left-1/2 top-0 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-indigo-300/30 blur-3xl dark:bg-indigo-500/20"></div>
        <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl dark:bg-cyan-500/10"></div>
        <div class="absolute bottom-0 left-0 h-80 w-80 rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"></div>
    </div>

    <div class="mx-auto flex min-h-screen w-full max-w-[1600px]">
        <aside class="sticky top-0 hidden h-screen w-72 shrink-0 border-r border-white/70 bg-white/70 px-5 py-6 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.03] lg:block">
            <a href="{{ route('dashboard.index') }}" class="flex items-center gap-3 rounded-3xl px-2">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-xl shadow-indigo-500/20 dark:bg-white dark:text-slate-950">FB</span>
                <span>
                    <span class="block text-lg font-black tracking-tight">Finance Bot</span>
                    <span class="block text-xs font-medium text-slate-500 dark:text-slate-400">Personal cashflow OS</span>
                </span>
            </a>

            <nav class="mt-10 space-y-2">
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}" @class([
                        'group flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition',
                        'bg-slate-950 text-white shadow-lg shadow-slate-950/10 dark:bg-white dark:text-slate-950' => request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'),
                        'text-slate-600 hover:bg-white hover:text-slate-950 dark:text-slate-400 dark:hover:bg-white/10 dark:hover:text-white' => ! (request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*')),
                    ])>
                        <x-dashboard.icon :name="$item['icon']" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="absolute bottom-6 left-5 right-5 rounded-[2rem] border border-white/70 bg-gradient-to-br from-slate-950 to-indigo-950 p-5 text-white shadow-2xl dark:border-white/10">
                <p class="text-sm font-bold">Telegram input aktif</p>
                <p class="mt-1 text-xs text-white/60">Catat transaksi langsung dari chat tanpa membuka dashboard.</p>
                <div class="mt-4 rounded-2xl bg-white/10 px-3 py-2 text-xs font-semibold">nasi padang 15k</div>
            </div>
        </aside>

        <main class="min-w-0 flex-1 px-4 pb-28 pt-4 sm:px-6 lg:px-8 lg:pb-8">
            <header class="sticky top-3 z-30 mb-6 rounded-[1.75rem] border border-white/70 bg-white/75 px-4 py-3 shadow-xl shadow-slate-200/40 backdrop-blur-2xl dark:border-white/10 dark:bg-slate-950/60 dark:shadow-black/20">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.28em] text-indigo-500">{{ $eyebrow ?? 'Personal Finance' }}</p>
                        <h1 class="mt-1 text-xl font-black tracking-tight sm:text-2xl">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="theme-toggle" class="rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:-translate-y-0.5 dark:border-white/10 dark:bg-white/10 dark:text-white">Mode</button>
                        @auth
                            <div class="hidden text-right sm:block">
                                <p class="text-sm font-black leading-tight text-slate-900 dark:text-white">{{ $authUser->name }}</p>
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $authUser->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-2xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700 shadow-sm transition hover:-translate-y-0.5 dark:border-rose-400/20 dark:bg-rose-400/10 dark:text-rose-200">Logout</button>
                            </form>
                        @endauth
                        <div class="hidden rounded-2xl bg-slate-950 px-4 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-950 sm:block">June 2026</div>
                    </div>
                </div>
            </header>

            {{ $slot }}
        </main>
    </div>

    <nav class="fixed inset-x-3 bottom-3 z-40 rounded-[1.75rem] border border-white/70 bg-white/90 p-2 shadow-2xl shadow-slate-950/10 backdrop-blur-2xl dark:border-white/10 dark:bg-slate-950/90 lg:hidden">
        <div class="grid grid-cols-6 gap-1">
            @foreach ($navigation as $item)
                <a href="{{ route($item['route']) }}" @class([
                    'flex flex-col items-center gap-1 rounded-2xl px-2 py-2 text-[10px] font-bold transition',
                    'bg-slate-950 text-white dark:bg-white dark:text-slate-950' => request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*'),
                    'text-slate-500 dark:text-slate-400' => ! (request()->routeIs($item['route']) || request()->routeIs($item['route'].'.*')),
                ])>
                    <x-dashboard.icon :name="$item['icon']" class="h-4 w-4" />
                    <span class="truncate">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.getElementById('theme-toggle')?.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark')
            localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        })
    </script>
    @stack('scripts')
</body>
</html>
