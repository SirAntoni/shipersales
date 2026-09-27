<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Comisiones - Total ventas: S/. {{number_format($granTotalMonth,2)}} | Comisión({{$commission}}%): S/. {{number_format($granTotalMonthCommission,2)}}
                </div>
                <div class="grid grid-cols-2 gap-2 md:ml-auto md:shrink-0 lg:grid-flow-col lg:grid-cols-none lg:auto-cols-fr lg:gap-3 [&>*:last-child:nth-child(odd)]:col-span-2 lg:[&>*:last-child:nth-child(odd)]:col-span-1">
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        onclick="window.location.href='{{ route('sales.create') }}'"
                    >
                        <div class="px-2"><i class="fa-solid fa-plus"></i></div>
                        <span class="sm:hidden">Venta</span>
                        <span class="hidden sm:inline">Nueva venta</span>
                    </x-base.button>
                    @can('update')
                        <x-base.button
                            class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                            variant="primary"
                            wire:click="reportCommissions"
                        >
                            <div class="px-2"><i class="fa-solid fa-file-excel"></i></div>
                            <span class="sm:hidden">Exportar</span>
                            <span class="hidden sm:inline">Exportar Excel</span>
                        </x-base.button>
                    @endcan
                </div>
            </div>
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">
                    {{-- Filtros: desde lg una fila (buscador 2fr, usuario 1fr, mes/año/por página fijos); en md el buscador arriba; en celular, apilados --}}
                    <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-12 lg:grid-cols-[minmax(14rem,2fr)_minmax(12rem,1fr)_9rem_8rem_7rem]">
                        <div class="relative md:col-span-12 lg:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                            <x-base.form-input
                                class="rounded-[0.5rem] pl-9"
                                type="text"
                                placeholder="Cliente, orden o producto..."
                                wire:model.live="search"
                            />
                        </div>

                        <div class="relative md:col-span-4 lg:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-user"></i>
                            <x-base.form-select wire:model.live="user" class="rounded-[0.5rem] pl-9">
                                <option value="">Usuario</option>
                                @foreach($users as $user)
                                    <option value="{{$user->id}}">{{$user->name}}</option>
                                @endforeach
                            </x-base.form-select>
                        </div>

                        <div class="relative md:col-span-3 lg:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-calendar"></i>
                            <x-base.form-select wire:model.live="month" class="rounded-[0.5rem] pl-9">
                                <option value="">Mes</option>
                                <option value="01">Enero</option>
                                <option value="02">Febrero</option>
                                <option value="03">Marzo</option>
                                <option value="04">Abril</option>
                                <option value="05">Mayo</option>
                                <option value="06">Junio</option>
                                <option value="07">Julio</option>
                                <option value="08">Agosto</option>
                                <option value="09">Setiembre</option>
                                <option value="10">Octubre</option>
                                <option value="11">Noviembre</option>
                                <option value="12">Diciembre</option>
                            </x-base.form-select>
                        </div>

                        <div class="relative md:col-span-3 lg:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-calendar"></i>
                            <x-base.form-select wire:model.live="year" class="rounded-[0.5rem] pl-9">
                                <option value="">Año</option>
                                @foreach(range(date('Y'), 2020) as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </x-base.form-select>
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
                                        %
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
                                        <x-base.table.tr class="[&_td]:last:border-b-0">
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
                                                    wire:click="changeStatus({{$sale->id}})">
                                                    {{\Illuminate\Support\Str::words($sale->client->name,2,'')}}

                                                </x-base.tippy>


                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">

                                                {{ $sale->date }}

                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">

                                                S/. {{ number_format($sale->total,2) }}

                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">

                                                S/. {{ number_format((($sale->total * $commission) / 100),2) }}

                                            </x-base.table.td-sale>
                                            <x-base.table.td-sale class="text-center border-dashed dark:bg-darkmode-600"
                                            >
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
                                                <div class="flex items-center justify-center">
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
                                                        variant="dark"
                                                        size="sm"
                                                        class="mr-2"
                                                        content="Ver PDF"
                                                        wire:click="verPDF({{$sale->id}})">
                                                        <i class="text-white fa-solid fa-file-pdf"></i>

                                                    </x-base.tippy>

                                                </div>
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    @endforeach
                                @else
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="11"
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

