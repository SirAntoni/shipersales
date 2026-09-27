<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Devoluciones
                </div>
                <div class="grid grid-cols-2 gap-2 md:ml-auto md:shrink-0 lg:grid-flow-col lg:grid-cols-none lg:auto-cols-fr lg:gap-3 [&>*:last-child:nth-child(odd)]:col-span-2 lg:[&>*:last-child:nth-child(odd)]:col-span-1">
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        onclick="window.location.href='{{ route('sales.index') }}'"
                    >
                        <i class="fa-solid fa-arrow-left mr-1"></i>
                        Volver a ventas
                    </x-base.button>
                </div>
            </div>
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">
                    {{-- Filtros: desde lg una fila (buscador 2fr, rango 1fr, por página fijo); en md el buscador arriba; en celular, apilados --}}
                    <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-12 lg:grid-cols-[minmax(14rem,2fr)_minmax(14rem,1fr)_7rem]">
                        <div class="relative md:col-span-12 lg:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                            <x-base.form-input
                                class="rounded-[0.5rem] pl-9 {{ $search ? 'pr-9' : '' }}"
                                type="text"
                                placeholder="Cliente, orden o producto..."
                                wire:model.live="search"
                            />
                            @if($search)
                                <i
                                    class="absolute inset-y-0 right-0 z-10 my-auto mr-3.5 h-4 w-4 cursor-pointer text-slate-400 hover:text-slate-600 fa-solid fa-xmark"
                                    wire:click="clearSearch"
                                    title="Borrar búsqueda"
                                ></i>
                            @endif
                        </div>

                        {{-- Rango de fechas: lo maneja el Litepicker del script de abajo (startDate/endDate) --}}
                        <div class="relative md:col-span-10 lg:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-calendar-days"></i>
                            <x-base.litepicker
                                id="datepicker-returns"
                                class="rounded-[0.5rem] pl-9"
                                placeholder="Rango de fechas"
                            />
                        </div>

                        <div class="relative md:col-span-2 lg:col-span-1" title="Registros por página">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-list-ol"></i>
                            <x-base.form-select class="rounded-[0.5rem] pl-9" wire:model.live="limit">
                                <option value="15">15</option>
                                <option value="30">30</option>
                                <option value="40">40</option>
                                <option value="100">100</option>
                            </x-base.form-select>
                        </div>
                    </div>
                    <div class="overflow-auto xl:overflow-visible text-sm">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        F. Marcado
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Usuario
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Cliente
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Fecha venta
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Total
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Cant.
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Contacto
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        M. pago
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        N.Orden
                                    </x-base.table.td>
                                    <x-base.table.td class="w-36 border-t border-slate-200/60 bg-slate-50 text-center font-medium text-slate-500">
                                        Acciones
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @if($sales->count() > 0)
                                    @foreach ($sales as $sale)
                                        <x-base.table.tr class="[&_td]:last:border-b-0">
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                {{ $sale->updated_at->format('Y-m-d H:i') }}
                                            </x-base.table.td>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                                {{ $sale->user->name }}
                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                                <x-base.tippy
                                                    as="x-base.button-sm"
                                                    variant="pending"
                                                    size="sm"
                                                    :content="$sale->htmlDetails">
                                                    {{ \Illuminate\Support\Str::words($sale->client->name, 2, '') }}
                                                </x-base.tippy>
                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                                {{ $sale->date }}
                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                                S/. {{ number_format($sale->total, 2) }}
                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="text-center border-dashed dark:bg-darkmode-600">
                                                {{ $sale->saleDetails->sum('quantity') }}
                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                                {{ $sale->contact->name }}
                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                                {{ $sale->paymentMethod->name }}
                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                                {{ $sale->number }}
                                            </x-base.table.td-sale>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                <div class="flex items-center justify-center gap-2">
                                                    @can('update')
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="success"
                                                            size="sm"
                                                            content="Ver detalle"
                                                            wire:click="edit({{ $sale->id }})">
                                                            <i class="text-white fa-solid fa-eye"></i>
                                                        </x-base.tippy>
                                                    @endcan
                                                    @can('delete')
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="danger"
                                                            size="sm"
                                                            content="Confirmar anulación"
                                                            wire:click="confirmCancel({{ $sale->id }})">
                                                            <i class="text-white fa-solid fa-check"></i>
                                                        </x-base.tippy>
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="secondary"
                                                            size="sm"
                                                            content="Revertir a aprobada"
                                                            wire:click="revertReturn({{ $sale->id }})">
                                                            <i class="fa-solid fa-undo"></i>
                                                        </x-base.tippy>
                                                    @endcan
                                                </div>
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    @endforeach
                                @else
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="10" class="text-center border-dashed dark:bg-darkmode-600">
                                            No hay devoluciones pendientes.
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endif
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                    <div class="m-4">
                        {{ $sales->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const pickerFilter = new Litepicker({
                element: document.getElementById('datepicker-returns'),
                autoApply: false,
                singleMode: false,
                // 2 meses miden ~560px: en celular no caben, ahí se muestra uno solo
                numberOfColumns: window.innerWidth < 640 ? 1 : 2,
                numberOfMonths: window.innerWidth < 640 ? 1 : 2,
                dropdowns: {
                    minYear: 2020,
                    maxYear: null,
                    months: true,
                    years: true,
                },
            });

            pickerFilter.on('selected', (startDate, endDate) => {
                @this.set('startDate', startDate.format('YYYY-MM-DD'));
                @this.set('endDate', endDate.format('YYYY-MM-DD'));
            });
        });
    </script>
</div>
