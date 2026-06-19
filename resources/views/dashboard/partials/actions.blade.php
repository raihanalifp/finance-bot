@props(['show', 'edit', 'destroy'])
<div class="flex flex-wrap gap-2">
    <a href="{{ $show }}" class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-black dark:bg-white/10">View</a>
    <a href="{{ $edit }}" class="rounded-xl bg-indigo-500/10 px-3 py-2 text-xs font-black text-indigo-600 dark:text-indigo-300">Edit</a>
    <form method="POST" action="{{ $destroy }}" onsubmit="return confirm('Delete this item?')">
        @csrf
        @method('DELETE')
        <button class="rounded-xl bg-rose-500/10 px-3 py-2 text-xs font-black text-rose-600 dark:text-rose-300">Delete</button>
    </form>
</div>
