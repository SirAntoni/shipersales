<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Ventas
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        onclick="window.location.href='{{ route('sales.create') }}'"
                    >
                        <div class="px-2"><i class="fa-solid fa-plus"></i></div>
                        Nueva venta
                    </x-base.button>


                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        wire:click="refresh()"
                    >
                        <div class="px-2"><i class="fa-solid fa-arrows-rotate"></i></div>
                        refrescar
                    </x-base.button>

                </div>
            </div>
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">
                    <div class="grid grid-cols-12">

                        {{-- Modal Anular Venta --}}
                        <div
                            x-data="{ showDeleteModal: false }"
                            x-on:open-delete-modal.window="showDeleteModal = true"
                            x-on:close-delete-modal.window="showDeleteModal = false"
                            x-on:keydown.escape.window="showDeleteModal = false; $wire.set('sectionDelete', false)"
                        >
                            <div
                                x-show="showDeleteModal"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="fixed inset-0 flex items-center justify-center"
                                style="z-index: 1050; display: none;"
                            >
                                {{-- Backdrop --}}
                                <div
                                    class="fixed inset-0 bg-black/50"
                                    x-on:click="showDeleteModal = false; $wire.set('sectionDelete', false)"
                                ></div>

                                {{-- Panel --}}
                                <div
                                    class="relative bg-white dark:bg-darkmode-600 rounded-lg shadow-xl w-[420px] max-h-[90vh] overflow-y-auto p-6"
                                    style="z-index: 1051;"
                                    x-on:click.stop
                                >
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-base font-medium">Anular venta - {{ $saleDeleteSelect }}</h3>
                                        <button
                                            x-on:click="showDeleteModal = false; $wire.set('sectionDelete', false)"
                                            class="text-slate-400 hover:text-slate-600"
                                        >
                                            <i class="fa-solid fa-xmark text-lg"></i>
                                        </button>
                                    </div>

                                    <x-base.alert class="flex items-center mb-4" variant="danger">
                                        <i class="fa-solid fa-circle-exclamation mr-2"></i>
                                        ¿Está seguro que desea anular la venta? Si es así, indicar un motivo.
                                    </x-base.alert>

                                    <div class="mb-3">
                                        <x-base.form-label>Motivo</x-base.form-label>
                                        <x-base.form-select class="mb-1" wire:model.live="motive">
                                            <option value="">Selecciona una opción</option>
                                            <option value="Venta mal realizada">Venta mal realizada</option>
                                            <option value="Duplicado">Duplicado</option>
                                            <option value="Devolución">Devolución</option>
                                            <option value="Cancelado por cliente">Cancelado por cliente</option>
                                            <option value="Otros">Otros</option>
                                        </x-base.form-select>
                                        @error('motive')
                                            <div class="text-sm text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    @if($sectionMoreDetails)
                                        <div class="mb-3">
                                            <x-base.form-label>Más detalles</x-base.form-label>
                                            <x-base.form-textarea class="mb-1" wire:model.live="motiveDetail"></x-base.form-textarea>
                                            @error('motiveDetail')
                                                <div class="text-sm text-red-600">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    @endif

                                    <div class="flex justify-end gap-2 mt-4">
                                        <x-base.button
                                            variant="secondary"
                                            x-on:click="showDeleteModal = false; $wire.set('sectionDelete', false)"
                                        >
                                            Cancelar
                                        </x-base.button>
                                        <x-base.button
                                            variant="danger"
                                            x-on:click="showDeleteModal = false; setTimeout(() => { $wire.call('deleteSale') }, 200)"
                                        >
                                            Anular venta
                                        </x-base.button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center justify-end">
                        <div>
                            <div class="relative mr-2">
                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-arrow-up-1-9"></i>
                                <x-base.litepicker
                                    id="datepicker"
                                    class="rounded-[0.5rem] pl-9 sm:w-60"
                                    data-single-mode="true"
                                    wire:model.live="date"
                                    placeholder="Ingresa un rango de fechas"
                                />
                            </div>
                        </div>
                        <div>
                            <div class="relative mr-2">

                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                                <x-base.form-input
                                    class="rounded-[0.5rem] pl-9 sm:w-64"
                                    type="text"
                                    placeholder="Buscar..."
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
                        </div>

                        <div>
                            <div class="relative mr-2">

                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-filter"></i>
                                <x-base.form-select
                                    class="rounded-[0.5rem] pl-9 sm:w-64"
                                    wire:model.live="status"
                                >
                                    <option value="">Todos</option>
                                    <option value="1">Pendiente</option>
                                    <option value="2">Completado</option>
                                    <option value="3">Observados</option>
                                    <option value="4">Cuadre</option>

                                </x-base.form-select>
                            </div>
                        </div>

                        <div>
                            <div class="relative mr-2">

                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-arrow-up-1-9"></i>
                                <x-base.form-select
                                    class="rounded-[0.5rem] pl-9 sm:w-35"
                                    type="text"
                                    placeholder="Buscar..."
                                    wire:model.live="limit"
                                >
                                    <option value="15">15</option>
                                    <option value="30">30</option>
                                    <option value="40">40</option>
                                    <option value="100">100</option>

                                </x-base.form-select>
                            </div>
                        </div>


                    </div>
                    <div class="overflow-auto xl:overflow-visible text-sm">
                        <x-base.table class="border-b border-slate-200/60 ">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Creación
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Usuario
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Cliente
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Fecha
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Total
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Cant.
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Contacto
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        M. pago
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        N.Orden
                                    </x-base.table.td>

                                    <x-base.table.td
                                        class="w-36 border-t border-slate-200/60 bg-slate-50 text-center font-medium text-slate-500"
                                    >
                                        Action
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @if($sales->count() > 0 )
                                    @foreach ($sales as $sale)
                                        <x-base.table.tr class="[&_td]:last:border-b-0 ">
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600 text">

                                                {{ $sale->created_at->format("d-m-Y H:i:s")}}

                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">

                                                {{ $sale->user->name}}

                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">

                                                <x-base.tippy
                                                    as="x-base.button-sm"
                                                    :variant="$sale->btnDetails"
                                                    size="sm"
                                                    :content="$sale->htmlDetails"
                                                    wire:click="questionStatus({{$sale->id}})">
                                                    {{\Illuminate\Support\Str::words($sale->client->name,2,'')}}

                                                </x-base.tippy>


                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">

                                                {{ $sale->date }}

                                            </x-base.table.td-sale>
                                            <td>
                                                @if($editingSaleId === $sale->id)

                                                    <x-base.form-input
                                                        class="rounded-[0.5rem] pl-9 sm:w-32"
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="Buscar..."
                                                        wire:model.defer="newTotal"
                                                    />
                                                    @error('newTotal')
                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                @else
                                                    {{ number_format((float)($sale->total ?? 0), 2) }}
                                                    {{-- Si tu campo es total_amount, usa $sale->total_amount --}}
                                                @endif
                                            </td>
                                            <x-base.table.td-sale class="text-center border-dashed dark:bg-darkmode-600"
                                            >
                                                {{ $sale->saleDetails->sum('quantity') }}

                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale
                                                class="border-dashed dark:bg-darkmode-600 {{(!is_null($sale->webhook_imported) ? 'text-warning':'')}}">

                                                {{ $sale->contact->name }}

                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">

                                                {{ $sale->paymentMethod->name }}

                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                                {{ $sale->number }}
                                            </x-base.table.td-sale>

                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                <div class="flex items-center justify-center">
                                                    {{-- Botones visibles --}}
                                                    @can('update')
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="success"
                                                            size="sm"
                                                            class="mr-2"
                                                            content="Ver detalle"
                                                            wire:click="edit({{$sale->id}})">
                                                            <i class="text-white fa-solid fa-eye"></i>
                                                        </x-base.tippy>
                                                    @endcan
                                                    <x-base.tippy
                                                        as="x-base.button-sm"
                                                        variant="{{ !is_null($sale->document?->id) ? 'success' : 'dark' }}"
                                                        size="sm"
                                                        class="mr-2"
                                                        content="Ver PDF"
                                                        wire:click="verPDF({{$sale->id}})">
                                                        <i class="text-white fa-solid fa-file-pdf"></i>
                                                    </x-base.tippy>

                                                    @php
                                                        $canInlineEdit = $sale->status !== \App\Models\Sale::SALE_CANCELED
                                                            && $sale->saleDetails->count() === 1
                                                            && (int)$sale->saleDetails->first()->quantity === 1;
                                                    @endphp

                                                    @if($canInlineEdit && $editingSaleId === $sale->id)
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="success"
                                                            size="sm"
                                                            class="mr-2"
                                                            content="Guardar"
                                                            wire:click="saveEditTotal({{ $sale->id }})">
                                                            <i class="text-white fa-solid fa-floppy-disk"></i>
                                                        </x-base.tippy>
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="danger"
                                                            size="sm"
                                                            class="mr-2"
                                                            content="Cancelar"
                                                            wire:click="cancelEditTotal">
                                                            <i class="text-white fa-solid fa-x"></i>
                                                        </x-base.tippy>
                                                    @endif

                                                    {{-- Dropdown de más acciones --}}
                                                    <div class="relative" x-data="{ open: false }">
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="secondary"
                                                            size="sm"
                                                            content="Más acciones"
                                                            x-on:click="open = !open">
                                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                                        </x-base.tippy>

                                                        <div
                                                            x-show="open"
                                                            x-on:click.outside="open = false"
                                                            x-transition:enter="transition ease-out duration-100"
                                                            x-transition:enter-start="opacity-0 scale-95"
                                                            x-transition:enter-end="opacity-100 scale-100"
                                                            x-transition:leave="transition ease-in duration-75"
                                                            x-transition:leave-start="opacity-100 scale-100"
                                                            x-transition:leave-end="opacity-0 scale-95"
                                                            class="absolute right-0 mt-1 w-48 bg-white dark:bg-darkmode-600 rounded-md shadow-lg border border-slate-200 dark:border-darkmode-400 py-1"
                                                            style="z-index: 50; display: none;"
                                                        >
                                                            {{-- Emitir comprobante --}}
                                                            <button
                                                                class="flex items-center w-full px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-darkmode-400 {{ !is_null($sale->document?->id) ? 'opacity-50 cursor-not-allowed' : '' }}"
                                                                wire:click="newDocument({{$sale->id}})"
                                                                {{ !is_null($sale->document?->id) ? 'disabled' : '' }}
                                                                x-on:click="open = false"
                                                            >
                                                                <i class="fa-solid fa-file-circle-plus mr-2 text-primary"></i>
                                                                Emitir comprobante
                                                            </button>

                                                            @if($canInlineEdit && $editingSaleId !== $sale->id)
                                                                {{-- Editar total --}}
                                                                <button
                                                                    class="flex items-center w-full px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-darkmode-400"
                                                                    wire:click="startEditTotal({{ $sale->id }})"
                                                                    x-on:click="open = false"
                                                                >
                                                                    <i class="fa-solid fa-edit mr-2 text-success"></i>
                                                                    Editar total
                                                                </button>
                                                            @endif

                                                            {{-- Marcar como devolución --}}
                                                            <button
                                                                class="flex items-center w-full px-3 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-darkmode-400"
                                                                wire:click="markAsReturn({{$sale->id}})"
                                                                x-on:click="open = false"
                                                            >
                                                                <i class="fa-solid fa-rotate-left mr-2 text-pending"></i>
                                                                Marcar devolución
                                                            </button>

                                                            @can('delete')
                                                                {{-- Anular venta --}}
                                                                <button
                                                                    class="flex items-center w-full px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-slate-100 dark:hover:bg-darkmode-400"
                                                                    wire:click="delete({{$sale->id}})"
                                                                    x-on:click="open = false"
                                                                >
                                                                    <i class="fa-solid fa-xmark mr-2"></i>
                                                                    Anular venta
                                                                </button>
                                                            @endcan
                                                        </div>
                                                    </div>

                                                </div>
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    @endforeach
                                @else
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="10"
                                                         class=" text-center border-dashed dark:bg-darkmode-600">
                                            No se encontrarón resultados.
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endif
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                    <div class="m-4">
                        {{$sales->links()}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center">
        <x-base.notification
            class="flex"
            id="success-notification-content"
        >
            <x-base.lucide
                class="text-success"
                icon="CheckCircle"
            />
            <div class="ml-4 mr-4">
                <div class="font-medium">Venta actualizada</div>
                <div class="mt-1 text-slate-500">
                    El registro fue actualizado con éxito.
                </div>
            </div>
        </x-base.notification>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const pickerFilter = new Litepicker({
            element: document.getElementById('datepicker'),
            autoApply: false,
            singleMode: false,
            numberOfColumns: 2,
            numberOfMonths: 2,
            dropdowns: {
                minYear: 2020,
                maxYear: null,
                months: true,
                years: true,
            },
        });

        pickerFilter.on('selected', (startDate, endDate) => {
            @this.
            set('startDate', startDate.format('YYYY-MM-DD'));
            @this.
            set('endDate', endDate.format('YYYY-MM-DD'));
        });

    });
</script>


