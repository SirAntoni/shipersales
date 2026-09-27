<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            {{-- Encabezado: título con cronómetro/progreso debajo; botones del mismo ancho en 2x2
                 (celular con etiquetas cortas) y en una fila desde lg --}}
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <div>
                    <div class="text-base font-medium group-[.mode--light]:text-white">
                        Inventario — Ventas del día
                    </div>

                    {{-- Cronómetro + Progreso --}}
                    <div class="mt-1 flex items-center gap-4 whitespace-nowrap text-slate-500 group-[.mode--light]:text-white/80">
                        <span class="text-sm"
                              x-data="{ t: '00:00:00' }"
                              x-init="
                                window.addEventListener('inventory-tick', e => { t = e.detail });
                                window.addEventListener('inventory-stopped', () => { t = '00:00:00' });
                              ">
                            ⏱ <span x-text="t"></span>
                        </span>
                        <span class="text-xs">
                            Completado: {{ $completedRows }} / {{ $totalRows }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2 md:ml-auto md:shrink-0 lg:grid-cols-4 lg:gap-3">
                    {{-- Iniciar inventario --}}
                    <x-base.button
                        variant="success"
                        wire:click="startInventory"
                        :disabled="$editing"
                    >
                        <div class="px-2"><i class="fa-solid fa-play"></i></div>
                        <span class="sm:hidden">Iniciar</span>
                        <span class="hidden sm:inline">Iniciar inventario</span>
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

                    {{-- Guardar (avisa qué falta si el conteo está incompleto) --}}
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        wire:click="saveCounts"
                        :disabled="!$editing"
                    >
                        <div class="px-2"><i class="fa-solid fa-floppy-disk"></i></div>
                        <span class="sm:hidden">Guardar</span>
                        <span class="hidden sm:inline">Guardar Stock</span>
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

            @if(!empty($gapAlerts) && count($gapAlerts))
                <div class="mt-3.5 rounded-[0.6rem] border border-warning/40 bg-warning/10 p-4 text-warning">
                    <div class="font-medium">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        Desfase de kardex detectado en los últimos 7 días ({{ count($gapAlerts) }} artículo{{ count($gapAlerts) > 1 ? 's' : '' }}):
                    </div>
                    <ul class="mt-1 list-disc pl-6 text-sm">
                        @foreach($gapAlerts as $ga)
                            <li>
                                {{ $ga->title }} — gap {{ sprintf('%+d', $ga->gap) }}
                                @if($ga->transit) (tránsito {{ $ga->transit }}) @endif
                                — detectado el {{ $ga->checked_date }}
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-1 text-xs">Revisa el kardex del artículo y la auditoría de cambios de stock para ubicar la operación.</div>
                </div>
            @endif

            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">

                    {{-- Filtros: desde xl una sola fila (buscador y nota crecen); entre md y xl dos filas
                         (buscador + fecha, y nota + rango + exportar); en celular, apilados --}}
                    <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-12 xl:grid-cols-[minmax(14rem,2fr)_11rem_minmax(12rem,1fr)_15rem_auto]">
                        <div class="relative md:col-span-8 xl:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 text-slate-500 fa-solid fa-magnifying-glass"></i>
                            <x-base.form-input
                                class="rounded-[0.5rem] pl-9"
                                type="text"
                                wire:model.live="q"
                                placeholder="Buscar por SKU o título…"
                                :disabled="$editing"
                            />
                        </div>

                        <div class="relative md:col-span-4 xl:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 text-slate-500 fa-solid fa-calendar-day"></i>
                            <x-base.litepicker
                                id="datepicker"
                                class="rounded-[0.5rem] pl-9"
                                data-single-mode="true"
                                wire:model.live="date"
                                placeholder="Selecciona una fecha"
                                :disabled="$editing"
                            />
                        </div>

                        <div class="relative md:col-span-5 xl:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 text-slate-500 fa-solid fa-note-sticky"></i>
                            <x-base.form-input
                                class="rounded-[0.5rem] pl-9"
                                type="text"
                                wire:model.defer="note"
                                placeholder="Nota del conteo"
                                :disabled="!$editing"
                            />
                        </div>

                        {{-- Excel de conteos: un dia o un rango; vacio = la fecha en pantalla --}}
                        <div class="relative md:col-span-4 xl:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 text-slate-500 fa-solid fa-calendar-days"></i>
                            <x-base.litepicker
                                id="datepicker-export"
                                class="rounded-[0.5rem] pl-9"
                                placeholder="Rango a exportar"
                            />
                        </div>
                        <div class="md:col-span-3 xl:col-span-1">
                            <x-base.tippy
                                as="x-base.button"
                                class="w-full whitespace-nowrap"
                                variant="outline-primary"
                                content="Descargar en Excel los conteos guardados del rango elegido o, si no eliges rango, de la fecha en pantalla"
                                wire:click="exportar"
                            >
                                <i class="fa-solid fa-file-excel mr-1"></i>
                                Exportar Excel
                            </x-base.tippy>
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
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">
                                            {{ (int)$r->warehouse_stock }}
                                            @if($r->is_snapshot)
                                                <i class="fa-regular fa-clock text-slate-400 ml-1" title="Stock del día del conteo (histórico)"></i>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">{{ (int)$r->kardex_stock }}</x-base.table.td>

                                        @php $dk = (int)$r->diff_kardex_vs_warehouse; @endphp
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">
                                            <span class="{{ $dk === 0 ? 'text-slate-600' : ($dk > 0 ? 'text-emerald-700' : 'text-red-600') }}">
                                                {{ $dk > 0 ? '+' : '' }}{{ $dk }}
                                            </span>
                                        </x-base.table.td>

                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">{{ (int)$r->sold_today }}</x-base.table.td>

                                        @php
                                            $fisicoDifiere = $r->physical_saved !== null
                                                && (int)$r->physical_saved !== (int)$r->warehouse_stock;
                                        @endphp
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">
                                            <span class="{{ $fisicoDifiere ? 'text-red-600 font-bold' : '' }}">
                                                {{ $r->physical_saved !== null ? (int)$r->physical_saved : '—' }}
                                            </span>
                                        </x-base.table.td>

                                        @php
                                            $inputVal = $physicalStocks[$r->article_id] ?? null;
                                            $inputDifiere = $inputVal !== null && $inputVal !== ''
                                                && (int)$inputVal !== (int)$r->warehouse_stock;
                                        @endphp
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            <x-base.form-input
                                                class="rounded-[0.5rem] w-28 text-right {{ $inputDifiere ? '!text-red-600 !font-bold !border-red-400' : '' }}"
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
            minDate: '{{ \App\Livewire\Inventory\Index::MIN_DATE }}',
            dropdowns: { minYear: 2026, maxYear: null, months: true, years: true },
        });

        pickerFilter.on('selected', (startDate) => {
            @this.set('date', startDate.format('YYYY-MM-DD'));
        });

        // Rango del Excel: sin minDate, hay conteos guardados desde marzo
        const pickerExport = new Litepicker({
            element: document.getElementById('datepicker-export'),
            autoApply: false,
            singleMode: false,
            // 2 meses miden ~560px: en celular no caben, ahí se muestra uno solo
            numberOfColumns: window.innerWidth < 640 ? 1 : 2,
            numberOfMonths: window.innerWidth < 640 ? 1 : 2,
            dropdowns: { minYear: 2026, maxYear: null, months: true, years: true },
        });

        pickerExport.on('selected', (startDate, endDate) => {
            @this.set('exportStart', startDate.format('YYYY-MM-DD'));
            @this.set('exportEnd', endDate.format('YYYY-MM-DD'));
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
