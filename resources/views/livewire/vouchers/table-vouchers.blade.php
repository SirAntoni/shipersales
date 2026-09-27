<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Comprobantes de pago
                </div>
                <div class="grid grid-cols-2 gap-2 md:ml-auto md:shrink-0 lg:grid-flow-col lg:grid-cols-none lg:auto-cols-fr lg:gap-3 [&>*:last-child:nth-child(odd)]:col-span-2 lg:[&>*:last-child:nth-child(odd)]:col-span-1">
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        onclick="window.location.href='{{ route('vouchers.create') }}'"
                    >
                        <div class="px-2"><i class="fa-solid fa-plus"></i></div>
                        Nuevo comprobante
                    </x-base.button>
                </div>
            </div>
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">
                    {{-- Buscador a todo el ancho de la tabla --}}
                    <div class="p-5">
                        <div class="relative">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                            <x-base.form-input
                                class="rounded-[0.5rem] pl-9"
                                type="text"
                                placeholder="Buscar comprobante..."
                                wire:model.live="search"
                            />
                        </div>
                    </div>
                    <div class="overflow-auto xl:overflow-visible">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        Nombre
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        Serie
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        Número
                                    </x-base.table.td>



                                    <x-base.table.td
                                        class="w-36 border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500"
                                    >
                                        Action
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @if($vouchers->count() > 0)
                                    @foreach ($vouchers as $voucher)
                                        <x-base.table.tr class="[&_td]:last:border-b-0">
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">
                                                {{ $voucher->name }}
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">
                                                {{ $voucher->serie }}
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">
                                                {{ $voucher->number }}
                                            </x-base.table.td>


                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">
                                                <div class="flex items-center justify-center">
                                                    <x-base.button
                                                        class="mr-2"
                                                        variant="success"
                                                        wire:click="edit({{$voucher->id}})"
                                                    >
                                                        <i class="text-white fa-solid fa-pen-to-square"></i>
                                                    </x-base.button>
                                                    <x-base.button
                                                        variant="danger"
                                                        wire:click="delete({{$voucher->id}})"
                                                    >
                                                        <i class="text-white fa-solid fa-trash"></i>
                                                    </x-base.button>
                                                </div>
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    @endforeach
                                @else
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="6"
                                                         class="text-center border-dashed py-4 dark:bg-darkmode-600">

                                            No se encontrarón resultados.

                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endif
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                    <div class="m-4">
                        {{$vouchers->links()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
