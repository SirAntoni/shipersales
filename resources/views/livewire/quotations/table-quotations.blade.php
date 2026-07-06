<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Cotizaciones
                </div>
            </div>
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center">
                        <div>
                            <x-base.button
                                variant="primary"
                                onclick="window.location.href='{{ route('quotations.create') }}'"
                            >
                                <i class="fa-solid fa-plus mr-2"></i>
                                Nueva cotización
                            </x-base.button>
                        </div>

                        <div class="flex items-center gap-2 sm:ml-auto">
                            <x-base.form-select
                                class="rounded-[0.5rem] sm:w-44"
                                wire:model.live="status"
                            >
                                <option value="">Todos los estados</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="aceptada">Aceptada</option>
                                <option value="rechazada">Rechazada</option>
                            </x-base.form-select>

                            <div class="relative mr-2">
                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                                <x-base.form-input
                                    class="rounded-[0.5rem] pl-9 sm:w-64"
                                    type="text"
                                    placeholder="Buscar cotización o cliente..."
                                    wire:model.live="search"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="overflow-auto xl:overflow-visible text-sm">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Fecha
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Número
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Cliente
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Vendedor
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Monto
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Válida hasta
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Estado
                                    </x-base.table.td>
                                    <x-base.table.td class="w-44 border-t border-slate-200/60 bg-slate-50 text-center font-medium text-slate-500">
                                        Acciones
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @if($quotations->count() > 0)
                                    @foreach($quotations as $quotation)
                                        @php
                                            $vencida = $quotation->valid_until
                                                && $quotation->status === 'pendiente'
                                                && \Carbon\Carbon::parse($quotation->valid_until)->isPast();
                                        @endphp
                                        <x-base.table.tr class="[&_td]:last:border-b-0">
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                {{ \Carbon\Carbon::parse($quotation->date)->format('d-m-Y') }}
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                <span class="bg-blue-100 text-white-800 text-xs font-medium me-2 px-2.5 p-1 rounded-full">{{ $quotation->number }}</span>
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                {{ $quotation->client->name ?? '—' }}
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                {{ $quotation->user->name ?? '—' }}
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                S/. {{ number_format($quotation->total, 2) }}
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d-m-Y') : '—' }}
                                                @if($vencida)
                                                    <span class="bg-slate-200 text-slate-600 text-xs font-medium px-2 py-0.5 rounded-full">vencida</span>
                                                @endif
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                @if($quotation->status === 'aceptada')
                                                    <span class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">Aceptada</span>
                                                @elseif($quotation->status === 'rechazada')
                                                    <span class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">Rechazada</span>
                                                @else
                                                    <span class="bg-amber-100 text-amber-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-amber-900 dark:text-amber-300">Pendiente</span>
                                                @endif
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                <div class="flex items-center justify-center">
                                                    <a href="{{ route('quotations.pdf', $quotation->id) }}" target="_blank" class="mr-2">
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="dark"
                                                            size="sm"
                                                            content="Ver PDF de la cotización">
                                                            <i class="text-white fa-solid fa-file-pdf"></i>
                                                        </x-base.tippy>
                                                    </a>

                                                    @if($quotation->status === 'pendiente')
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="success"
                                                            size="sm"
                                                            class="mr-2"
                                                            content="Marcar como aceptada"
                                                            wire:click="changeStatus({{ $quotation->id }}, 'aceptada')">
                                                            <i class="text-white fa-solid fa-check"></i>
                                                        </x-base.tippy>

                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="warning"
                                                            size="sm"
                                                            class="mr-2"
                                                            content="Marcar como rechazada"
                                                            wire:click="changeStatus({{ $quotation->id }}, 'rechazada')">
                                                            <i class="text-white fa-solid fa-ban"></i>
                                                        </x-base.tippy>
                                                    @endif

                                                    @can('delete')
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="danger"
                                                            size="sm"
                                                            content="Eliminar cotización"
                                                            wire:click="delete({{ $quotation->id }})">
                                                            <i class="text-white fa-solid fa-trash"></i>
                                                        </x-base.tippy>
                                                    @endcan
                                                </div>
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    @endforeach
                                @else
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="8" class="text-center border-dashed dark:bg-darkmode-600">
                                            No se encontraron resultados.
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endif
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                    <div class="m-4">
                        {{ $quotations->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
