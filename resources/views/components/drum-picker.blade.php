@props(['label' => null])

{{-- フォームをクリックすると iOS のドラムピッカー風の日時選択が開く。wire:model には "Y-m-d\TH:i" 形式の文字列が入る --}}
<div
    x-data="{
        value: null,
        open: false,
        itemHeight: 36,
        columns: [
            { key: 'year', suffix: '年' },
            { key: 'month', suffix: '月' },
            { key: 'day', suffix: '日' },
            { key: 'hour', suffix: '時' },
            { key: 'minute', suffix: '分' },
        ],
        weekdays: ['日', '月', '火', '水', '木', '金', '土'],
        parts: { year: 2000, month: 1, day: 1, hour: 0, minute: 0 },
        years: [],
        timers: {},
        els: {},

        init() {
            this.parse(this.value)
            this.buildYears()
            this.$watch('value', (v) => {
                if (v !== this.compose()) {
                    this.parse(v)
                    this.buildYears()
                    this.$nextTick(() => this.scrollAll())
                }
            })
        },

        pad(n) {
            return String(n).padStart(2, '0')
        },

        parse(v) {
            const m = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/.exec(v ?? '')
            const d = new Date()
            this.parts = m
                ? { year: +m[1], month: +m[2], day: +m[3], hour: +m[4], minute: +m[5] }
                : { year: d.getFullYear(), month: d.getMonth() + 1, day: d.getDate(), hour: d.getHours(), minute: 0 }
        },

        compose() {
            const p = this.parts
            return `${p.year}-${this.pad(p.month)}-${this.pad(p.day)}T${this.pad(p.hour)}:${this.pad(p.minute)}`
        },

        // フォームに表示する文字列（例: 2026年10月7日(水) 09:00）
        display() {
            if (!this.value) return ''
            const p = this.parts
            const w = this.weekdays[new Date(p.year, p.month - 1, p.day).getDay()]
            return `${p.year}年${p.month}月${p.day}日(${w}) ${this.pad(p.hour)}:${this.pad(p.minute)}`
        },

        // 年の候補は開く前に一度だけ決める（選択中に動くと表示と値がずれるため）
        buildYears() {
            const base = new Date().getFullYear()
            const from = Math.min(base - 1, this.parts.year)
            const to = Math.max(base + 5, this.parts.year)
            this.years = Array.from({ length: to - from + 1 }, (_, i) => from + i)
        },

        daysInMonth() {
            return new Date(this.parts.year, this.parts.month, 0).getDate()
        },

        options(key) {
            if (key === 'year') return this.years
            if (key === 'month') return Array.from({ length: 12 }, (_, i) => i + 1)
            if (key === 'day') return Array.from({ length: this.daysInMonth() }, (_, i) => i + 1)
            if (key === 'hour') return Array.from({ length: 24 }, (_, i) => i)
            return Array.from({ length: 60 }, (_, i) => i)
        },

        label(key, n) {
            return key === 'year' ? String(n) : this.pad(n)
        },

        toggle() {
            if (this.open) return this.close()
            // 値が空なら現在日時を初期値にする
            if (!this.value) {
                this.parse(null)
                this.buildYears()
                this.value = this.compose()
            }
            this.open = true
            this.$nextTick(() => this.scrollAll())
        },

        close() {
            this.open = false
        },

        scrollAll() {
            this.columns.forEach((c) => this.scrollTo(c.key, false))
        },

        scrollTo(key, smooth = true) {
            const el = this.els[key]
            if (!el) return
            const i = this.options(key).indexOf(this.parts[key])
            el.scrollTo({ top: i * this.itemHeight, behavior: smooth ? 'smooth' : 'instant' })
        },

        // スクロールが止まったら、いちばん中央に近い項目を選択値にする
        onScroll(key) {
            if (!this.open) return
            clearTimeout(this.timers[key])
            this.timers[key] = setTimeout(() => {
                const opts = this.options(key)
                const i = Math.min(opts.length - 1, Math.max(0, Math.round(this.els[key].scrollTop / this.itemHeight)))
                this.select(key, opts[i])
            }, 90)
        },

        select(key, n) {
            this.parts[key] = n
            // 月や年を変えて日が存在しなくなったら末日に寄せる（例: 31日 → 2月なら28日）
            const max = this.daysInMonth()
            if (this.parts.day > max) {
                this.parts.day = max
                this.$nextTick(() => this.scrollTo('day'))
            }
            this.value = this.compose()
        },
    }"
    x-modelable="value"
    {{ $attributes->whereStartsWith('wire:model') }}
    {{ $attributes->whereDoesntStartWith('wire:model')->class('space-y-2') }}
    @keydown.escape="close()"
>
    <div wire:ignore class="relative" @click.outside="close()">
        <flux:input
            :label="$label"
            icon="calendar"
            readonly
            placeholder="日時を選択"
            class="cursor-pointer"
            x-bind:value="display()"
            @click="toggle()"
        />

        <div
            x-show="open"
            x-cloak
            x-transition.opacity.duration.100ms
            class="absolute left-0 z-20 mt-1 w-full min-w-72 rounded-xl border border-zinc-200 bg-white p-2 shadow-lg dark:border-white/10 dark:bg-zinc-900"
        >
            <div class="relative select-none">
                {{-- 中央の選択帯 --}}
                <div
                    class="pointer-events-none absolute inset-x-0 top-1/2 h-9 -translate-y-1/2 rounded-lg bg-zinc-100 dark:bg-white/10"
                ></div>

                <div class="relative flex">
                    <template x-for="col in columns" :key="col.key">
                        <div
                            x-init="els[col.key] = $el"
                            @scroll.passive="onScroll(col.key)"
                            class="h-45 flex-1 snap-y snap-mandatory overflow-y-scroll py-18 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                            style="mask-image: linear-gradient(to bottom, transparent, black 30%, black 70%, transparent)"
                        >
                            <template x-for="n in options(col.key)" :key="col.key + n">
                                <button
                                    type="button"
                                    tabindex="-1"
                                    @click="select(col.key, n); scrollTo(col.key)"
                                    class="flex h-9 w-full snap-center items-center justify-center gap-0.5 text-lg tabular-nums text-zinc-900 dark:text-white"
                                >
                                    <span x-text="label(col.key, n)"></span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400" x-text="col.suffix"></span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mt-2 flex justify-end">
                <flux:button type="button" size="sm" variant="primary" @click="close()">完了</flux:button>
            </div>
        </div>
    </div>

    <flux:error :name="$attributes->wire('model')->value()" />
</div>
