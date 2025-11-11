<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Inventario — Ventas del día
                </div>

                {{-- Cronómetro + Progreso --}}
                <div class="ml-6 flex items-center gap-3">
                    <span class="text-sm text-white"
                          x-data="{ t: '00:00:00' }"
                          x-init="
                            window.addEventListener('inventory-tick', e => { t = e.detail });
                            window.addEventListener('inventory-stopped', () => { t = '00:00:00' });
                          ">
                        ⏱ <span x-text="t"></span>
                    </span>
                    <span class="text-xs text-white">
                        Completado: {{ $completedRows }} / {{ $totalRows }}
                    </span>
                </div>

                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    {{-- Iniciar inventario --}}
                    <x-base.button
                        variant="success"
                        wire:click="startInventory"
                        :disabled="$editing"
                    >
                        <div class="px-2"><i class="fa-solid fa-play"></i></div>
                        Iniciar inventario
                    </x-base.button>

                    {{-- Refrescar --}}
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        wire:click="loadRows"
                    >
                        <div class="px-2"><i class="fa-solid fa-arrows-rotate"></i></div>
                        Refrescar
                    </x-base.button>

                    {{-- Guardar (bloqueado hasta completar TODO) --}}
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        wire:click="saveCounts"
                        :disabled="!$editing || $completedRows !== $totalRows"
                    >
                        <div class="px-2"><i class="fa-solid fa-floppy-disk"></i></div>
                        Guardar Stock
                    </x-base.button>

                    {{-- (Opcional) Cancelar --}}
                    <x-base.button
                        variant="secondary"
                        wire:click="cancelInventory"
                        :disabled="!$editing"
                    >
                        <div class="px-2"><i class="fa-solid fa-stop"></i></div>
                        Cancelar
                    </x-base.button>
                </div>
            </div>

            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">

                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center justify-end">
                        <div>
                            <div class="relative mr-2">
                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 text-slate-500 fa-solid fa-magnifying-glass"></i>
                                <x-base.form-input
                                    class="rounded-[0.5rem] pl-9 sm:w-72"
                                    type="text"
                                    wire:model.live="q"
                                    placeholder="Buscar por SKU o título…"
                                    :disabled="$editing"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="relative mr-2">
                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-arrow-up-1-9"></i>
                                <x-base.litepicker
                                    id="datepicker"
                                    class="rounded-[0.5rem] pl-9 sm:w-60"
                                    data-single-mode="true"
                                    wire:model.live="date"
                                    placeholder="Selecciona una fecha"
                                    :disabled="$editing"
                                />
                            </div>
                        </div>

                        <div>
                            <x-base.form-input
                                class="rounded-[0.5rem] sm:w-64"
                                type="text"
                                wire:model.defer="note"
                                placeholder="Nota opcional para conteos…"
                                :disabled="!$editing"
                            />
                        </div>
                    </div>

                    <div class="overflow-auto xl:overflow-visible text-sm">
                        <x-base.table class="border-b border-slate-200/60 ">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">SKU</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Nombre</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Stock Almacén</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Stock Kardex</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Dif, K</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Vendidos ({{ \Carbon\Carbon::parse($date)->format('d-m-Y') }})
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Físico Guardado</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Stock Físico</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Acción</x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>

                            <x-base.table.tbody>
                                @forelse ($rows as $r)
                                    <x-base.table.tr class="[&_td]:last:border-b-0" wire:key="row-{{ $r->article_id }}">
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">{{ $r->sku }}</x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">{{ $r->title }}</x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">{{ (int)$r->warehouse_stock }}</x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">{{ (int)$r->kardex_stock }}</x-base.table.td>

                                        @php $dk = (int)$r->diff_kardex_vs_warehouse; @endphp
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">
                                            <span class="{{ $dk === 0 ? 'text-slate-600' : ($dk > 0 ? 'text-emerald-700' : 'text-red-600') }}">
                                                {{ $dk > 0 ? '+' : '' }}{{ $dk }}
                                            </span>
                                        </x-base.table.td>

                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">{{ (int)$r->sold_today }}</x-base.table.td>

                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">
                                            {{ $r->physical_saved !== null ? (int)$r->physical_saved : '—' }}
                                        </x-base.table.td>

                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            <x-base.form-input
                                                class="rounded-[0.5rem] w-28 text-right"
                                                type="number" min="0" step="1"
                                                wire:key="phys-{{ $r->article_id }}"
                                                wire:model.live.debounce.300ms="physicalStocks.{{ $r->article_id }}"
                                                placeholder="—"
                                                :disabled="!$editing"
                                            />
                                            @error('physicalStocks.'.$r->article_id)
                                            <div class="text-red-600 text-xs mt-1">{{ $message }}</div>
                                            @enderror
                                        </x-base.table.td>

                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            <div class="flex items-center justify-center">
                                                @can('update')
                                                    <x-base.tippy
                                                        as="x-base.button-sm" variant="success" size="sm" class="mr-2"
                                                        content="Limpiar" wire:key="clr-{{ $r->article_id }}"
                                                        wire:click="clearPhysical({{ $r->article_id }})"
                                                        :disabled="!$editing"
                                                    >
                                                        <i class="text-white fa-solid fa-eraser"></i>
                                                    </x-base.tippy>
                                                @endcan
                                                <x-base.tippy
                                                    as="x-base.button-sm" variant="primary" size="sm" class="mr-2"
                                                    content="Actualizar almacén" wire:key="upd-{{ $r->article_id }}"
                                                    wire:click="updateWarehouseStock({{ $r->article_id }})"
                                                    :disabled="!$editing || !isset($physicalStocks[$r->article_id]) || $physicalStocks[$r->article_id] === null || $physicalStocks[$r->article_id] === ''"
                                                >
                                                    <i class="fa-solid fa-arrow-rotate-right"></i>
                                                </x-base.tippy>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @empty
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="10" class=" text-center border-dashed dark:bg-darkmode-600">
                                            No se encontrarón resultados.
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforelse
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Cronómetro + Litepicker --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const pickerFilter = new Litepicker({
            element: document.getElementById('datepicker'),
            autoApply: false,
            singleMode: true,
            numberOfColumns: 1,
            numberOfMonths: 1,
            dropdowns: { minYear: 2020, maxYear: null, months: true, years: true },
        });

        pickerFilter.on('selected', (startDate) => {
            @this.set('date', startDate.format('YYYY-MM-DD'));
        });

        // ===== Cronómetro =====
        let tickTimer = null;

        function formatHMS(sec) {
            const h = Math.floor(sec / 3600).toString().padStart(2,'0');
            const m = Math.floor((sec % 3600) / 60).toString().padStart(2,'0');
            const s = Math.floor(sec % 60).toString().padStart(2,'0');
            return `${h}:${m}:${s}`;
        }

        window.addEventListener('inventory-started', (e) => {
            const startedAt = new Date(e.detail.startedAt || e.detail);
            if (tickTimer) clearInterval(tickTimer);
            tickTimer = setInterval(() => {
                const now = new Date();
                const diff = Math.max(0, Math.floor((now - startedAt) / 1000));
                const hms = formatHMS(diff);
                window.dispatchEvent(new CustomEvent('inventory-tick', { detail: hms }));
            }, 1000);
        });

        window.addEventListener('inventory-stopped', () => {
            if (tickTimer) clearInterval(tickTimer);
            tickTimer = null;
            window.dispatchEvent(new CustomEvent('inventory-tick', { detail: '00:00:00' }));
        });
    });
</script>
