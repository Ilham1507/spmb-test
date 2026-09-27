@props(['title' => 'Bingung mengisi?', 'message'])

<details class="group bg-sky-50/50 hover:bg-sky-50 border border-sky-200/60 rounded-2xl mb-6 shadow-sm overflow-hidden cursor-pointer open:bg-sky-50 transition-all duration-300">
    <summary class="flex items-center gap-3 p-4 text-sm text-sky-800 font-bold select-none list-none marker:hidden">
        <div class="w-6 h-6 rounded-full bg-sky-200 text-sky-700 flex items-center justify-center shrink-0 group-open:bg-sky-500 group-open:text-white transition-colors">
            <span class="group-open:hidden font-black text-xs">?</span>
            <svg class="w-4 h-4 hidden group-open:block" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </div>
        {{ $title }}
    </summary>
    <div class="px-4 pb-4 pt-1 text-xs text-sky-700 leading-relaxed ml-3 sm:ml-[3.25rem] opacity-90">
        {!! $message !!}
    </div>
</details>
<style>
details > summary::-webkit-details-marker {
  display: none;
}
details > summary {
  list-style: none;
}
</style>
