<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>Login · Finance Bot</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        }
    </script>
</head>
<body class="min-h-full bg-slate-50 text-slate-950 antialiased dark:bg-[#070b16] dark:text-white">
    <div class="fixed inset-0 -z-10 overflow-hidden">
        <div class="absolute left-1/2 top-0 h-[32rem] w-[32rem] -translate-x-1/2 rounded-full bg-indigo-300/30 blur-3xl dark:bg-indigo-500/20"></div>
        <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl dark:bg-cyan-500/10"></div>
    </div>

    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-md rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-2xl shadow-slate-200/60 backdrop-blur-2xl dark:border-white/10 dark:bg-white/[0.05] dark:shadow-black/30 sm:p-8">
            <div class="mb-8 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-950 text-lg font-black text-white shadow-xl shadow-indigo-500/20 dark:bg-white dark:text-slate-950">FB</div>
                <h1 class="mt-5 text-2xl font-black tracking-tight">Login Finance Bot</h1>
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Masuk untuk mengakses dashboard personal finance.</p>
            </div>

            @if ($errors->any())
                <div class="mb-5 rounded-3xl bg-rose-500/10 p-4 text-sm font-bold text-rose-700 dark:text-rose-300">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="text-sm font-bold text-slate-700 dark:text-slate-300">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-indigo-400 dark:border-white/10 dark:bg-white/10">
                </div>
                <div>
                    <label for="password" class="text-sm font-bold text-slate-700 dark:text-slate-300">Password</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-indigo-400 dark:border-white/10 dark:bg-white/10">
                </div>
                <div class="flex items-center justify-between gap-4">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-600 dark:text-slate-300">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300 text-indigo-600">
                        Remember me
                    </label>
                    <button type="button" id="theme-toggle" class="text-sm font-bold text-indigo-600 dark:text-indigo-300">Toggle mode</button>
                </div>
                <button class="w-full rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:-translate-y-0.5 dark:bg-white dark:text-slate-950">Login</button>
            </form>
        </section>
    </main>

    <script>
        document.getElementById('theme-toggle')?.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark')
            localStorage.theme = document.documentElement.classList.contains('dark') ? 'dark' : 'light'
        })
    </script>
</body>
</html>
