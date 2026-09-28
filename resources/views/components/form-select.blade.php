@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => 'Pilih',
    'required' => false,
    'id' => null,
    'menuClass' => '',
    'forceDown' => false,
    'menuZIndex' => 200,
])

@php
    $buttonId = $id ?? $name . '_button_' . substr(md5($name . spl_object_id((object) $options) . random_int(1, PHP_INT_MAX)), 0, 8);
    $selectedValue = old($name, $value);
    $rawOptions = $options instanceof \Illuminate\Support\Collection ? $options->all() : $options;
    $isListOptions = array_is_list($rawOptions);
    $optionValues = collect($rawOptions)->map(function ($label, $key) use ($isListOptions) {
        if (is_array($label)) {
            return [
                'value' => (string) ($label['value'] ?? $key),
                'label' => (string) ($label['label'] ?? $label['name'] ?? $label['value'] ?? $key),
            ];
        }

        if (is_object($label)) {
            return [
                'value' => (string) ($label->value ?? $label->id ?? $key),
                'label' => (string) ($label->label ?? $label->name ?? $label->nama ?? $label->value ?? $key),
            ];
        }

        return [
            'value' => (string) ($isListOptions ? $label : $key),
            'label' => (string) $label,
        ];
    })->values()->all();
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        openUp: false,
        menuStyle: '',
        selected: @js((string) $selectedValue),
        options: @js($optionValues),
        selectedLabel() {
            const item = this.options.find((option) => option.value === this.selected);
            return item ? item.label : '';
        },
        choose(option) {
            this.selected = typeof option === 'object' ? option.value : option;
            this.open = false;
            this.$dispatch('form-select-changed', { name: @js($name), value: this.selected });
            this.$nextTick(() => {
                this.$el.querySelector('input[type=hidden]')
                    ?.dispatchEvent(new Event('change', { bubbles: true }));
            });
            // On touch screens a delayed click can otherwise reopen the menu
            // after the option beneath the finger has been selected.
            window.setTimeout(() => { this.open = false; }, 0);
        },
        positionMenu() {
            const trigger = this.$refs.trigger;
            if (!trigger) return;

            const rect = trigger.getBoundingClientRect();
            const gap = 8;
            const edge = 12;
            const spaceBelow = window.innerHeight - rect.bottom - edge;
            const spaceAbove = rect.top - edge;
            const maxHeight = window.innerWidth <= 640 ? 176 : 288;
            // The menu is teleported to body, so calculate using its real option height.
            // Reserving the maximum height caused short menus to appear far from the trigger.
            const desiredHeight = Math.min(maxHeight, Math.max(48, this.$refs.menu?.scrollHeight || maxHeight));
            this.openUp = @js($forceDown) ? false : (spaceBelow < desiredHeight && spaceAbove > spaceBelow);
            const available = Math.max(48, this.openUp ? spaceAbove : spaceBelow);
            const height = Math.min(desiredHeight, available);
            const top = this.openUp
                ? Math.max(edge, rect.top - height - gap)
                : Math.min(window.innerHeight - edge - height, rect.bottom + gap);
            const width = Math.min(rect.width, window.innerWidth - edge * 2);
            const tone = getComputedStyle(trigger);
            const accent = tone.getPropertyValue('--portal-accent').trim() || '#0b3b83';
            const deep = tone.getPropertyValue('--portal-accent-deep').trim() || '#07265d';
            const soft = tone.getPropertyValue('--portal-soft').trim() || '#e8f2ff';
            const line = tone.getPropertyValue('--portal-line').trim() || '#cddff5';
            const left = Math.min(Math.max(edge, rect.left), window.innerWidth - edge - width);

            this.menuStyle = `left:${left}px;top:${top}px;width:${width}px;max-height:${height}px;--form-select-accent:${accent};--form-select-deep:${deep};--form-select-soft:${soft};--form-select-line:${line};`;
        },
        toggle() {
            this.open = !this.open;
            if (this.open) this.$nextTick(() => this.positionMenu());
        }
    }"
    @keydown.escape.window="open = false"
    @resize.window="open && positionMenu()"
    @scroll.window="open && positionMenu()"
    @scroll.document.capture="open && positionMenu()"
>
    @if($label)
        <label for="{{ $buttonId }}" class="block text-xs font-semibold text-slate-500 mb-1.5">
            {{ $label }} @if($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    <input type="hidden" name="{{ $name }}" :value="selected || ''" @if($required) required @endif>

    <button
        id="{{ $buttonId }}"
        type="button"
        x-ref="trigger"
        class="group flex min-h-[42px] w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-50/40 focus:outline-none focus:ring-4 focus:ring-sky-500/15 focus:border-sky-500"
        @click="toggle()"
        :class="open ? 'border-sky-500 ring-4 ring-sky-500/15 bg-white' : ''"
    >
        <span class="flex min-w-0 items-center gap-2">
            <span class="flex h-7 w-7 flex-none items-center justify-center rounded-lg bg-sky-50 text-sky-600 group-hover:bg-sky-100">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M8 4h8a2 2 0 012 2v12a2 2 0 01-2 2H8a2 2 0 01-2-2V6a2 2 0 012-2z" />
                </svg>
            </span>
            <span class="truncate" x-text="selectedLabel() || '{{ $placeholder }}'" :class="selected ? 'text-slate-800 font-semibold' : 'text-slate-400'"></span>
        </span>
        <svg class="h-4 w-4 flex-none text-slate-400 transition" :class="open ? 'rotate-180 text-sky-500' : ''" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-cloak
            x-show="open"
            @click.outside="open = false"
            x-ref="menu"
            class="form-select-menu fixed overflow-y-auto rounded-2xl border bg-white p-2 shadow-2xl shadow-slate-900/12 {{ $menuClass }}"
            :class="openUp ? 'origin-bottom' : 'origin-top'"
            :style="`z-index:{{ (int) $menuZIndex }};${menuStyle}`"
        >
            <button type="button" class="form-select-option flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-slate-400" @click="choose('')">
                {{ $placeholder }}
            </button>
            <template x-for="option in options" :key="option.value">
                <button
                    type="button"
                    class="form-select-option flex w-full items-center justify-between rounded-xl px-3 py-2.5 text-left text-sm font-semibold transition"
                    :class="selected === option.value ? 'is-selected' : 'text-slate-700'"
                    @click.stop="choose(option)"
                >
                    <span x-text="option.label"></span>
                    <svg x-show="selected === option.value" class="h-4 w-4 text-sky-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
            </template>
        </div>
    </template>

    @error($name)
        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
    @enderror
</div>
@once
<style>
    .form-select-menu{border-color:var(--form-select-line,#cddff5)!important}.form-select-menu .form-select-option:hover{background:var(--form-select-soft,#e8f2ff)!important;color:var(--form-select-deep,#07265d)!important}.form-select-menu .form-select-option.is-selected{background:var(--form-select-soft,#e8f2ff)!important;color:var(--form-select-deep,#07265d)!important;font-weight:800}.form-select-menu .form-select-option.is-selected svg{color:var(--form-select-accent,#0b3b83)!important}
</style>
@endonce
