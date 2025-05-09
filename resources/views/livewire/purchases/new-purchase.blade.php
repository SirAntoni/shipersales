<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Nueva Compra
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        wire:click="save"
                    >
                        <i class="fa-solid fa-floppy-disk mr-2"></i>

                        Guardar compra
                    </x-base.button>
                </div>
            </div>

            <div class="mt-3.5 grid grid-cols-12 gap-x-6 gap-y-10">

                <div class="col-span-12 xl:col-span-12">
                    <div class="box box--stacked flex flex-col p-5 sm:p-14">
                        <div class="grid grid-cols-12">

                            <div
                                class="col-span-12 relative mb-4 mt-7 rounded-[0.6rem] border border-slate-200/80 dark:border-darkmode-400">
                                <div class="absolute left-0 -mt-2 ml-4 bg-white px-3 text-xs uppercase text-slate-500">
                                    <div class="-mt-px">datos de la compra</div>
                                </div>
                                <div class="grid grid-cols-12 pt-4">
                                    <div class="col-span-12 sm:col-span-6 flex flex-col px-5 py-2">
                                        <x-base.preview>
                                            <div>
                                                <label>Proveedor</label>
                                                <div class="mt-2 " wire:ignore>
                                                    <x-base.tom-select
                                                        wire:ignore
                                                        class="w-full"
                                                        data-placeholder="Selecciona un proveedor"
                                                        wire:model="provider"
                                                    >
                                                        <option value=""></option>
                                                        @foreach($providers as $provider)
                                                            <option value="{{$provider->id}}">{{$provider->name}}
                                                                - {{$provider->document_number}}</option>
                                                        @endforeach


                                                    </x-base.tom-select>

                                                </div>
                                                @error('provider')
                                                <div class="p-1">
                                                    {{$message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </x-base.preview>

                                        <x-base.source>
                                            <x-base.highlight>
                                                <div>
                                                    <label>Basic</label>
                                                    <div>
                                                        <x-base.tom-select
                                                            class="w-full"
                                                            data-placeholder="Selecciona un proveedor"
                                                            wire:model="provider"

                                                        >
                                                            <option value=""></option>
                                                            @foreach($providers as $provider)
                                                                <option value="{{$provider->id}}">{{$provider->name}}
                                                                    - {{$provider->document_number}}</option>
                                                            @endforeach
                                                        </x-base.tom-select>
                                                    </div>
                                                </div>
                                            </x-base.highlight>
                                        </x-base.source>


                                    </div>
                                    <div class="col-span-12 sm:col-span-6 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <x-base.form-label for="voucher_type">
                                                Tipo de documento
                                            </x-base.form-label>
                                            <x-base.form-select
                                                aria-label=".form-select-lg"
                                                id="voucher_type"
                                                wire:model="voucher_type"
                                            >
                                                <option value="">Selecciona un tipo de documento</option>
                                                <option value="Factura">Factura</option>
                                                <option value="Boleta">Boleta</option>
                                                <option value="Ticket">Ticket</option>
                                            </x-base.form-select>
                                            @error('voucher_type')
                                            <div class="p-1">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>
                                    <div class="col-span-12 sm:col-span-6 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <x-base.form-label for="passenger">
                                                Número de factura
                                            </x-base.form-label>
                                            <x-base.form-input
                                                id="document"
                                                type="text"
                                                placeholder="Ingresa número de factura"
                                                wire:model="document"
                                            />
                                            @error('document')
                                            <div class="p-1">
                                                {{$message}}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>
                                    <div class="col-span-12 sm:col-span-6 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <x-base.form-label for="passenger">
                                                Pasajero
                                            </x-base.form-label>
                                            <x-base.form-input
                                                id="document"
                                                type="text"
                                                placeholder="Ingresa nombre del pasajero"
                                                wire:model="passenger"
                                            />
                                            @error('passenger')
                                            <div class="p-1">
                                                {{$message}}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>
                                    <div class="col-span-12 sm:col-span-9 flex flex-col gap-3.5 px-5 py-2">
                                        <x-base.preview>
                                            <div>
                                                <label>Agregar Articulo</label>

                                                <div class="mt-2" wire:ignore>
                                                    <x-base.tom-select
                                                        class="w-full"
                                                        data-placeholder="Selecciona el articulo a agregar"
                                                        wire:model.live="articleSelected"
                                                    >
                                                        <option value=""></option>
                                                        @foreach($articles as $article)
                                                            <option value="{{$article->id}}">{{$article->title}} |
                                                                stock: {{$article->stock}} |
                                                                sku: {{$article->sku}}</option>
                                                        @endforeach


                                                    </x-base.tom-select>
                                                </div>
                                                @error('articlesSelected')
                                                <div class="p-1">
                                                    {{$message}}
                                                </div>
                                                @enderror
                                            </div>
                                        </x-base.preview>
                                        <x-base.source>
                                            <x-base.highlight>
                                                <div>
                                                    <label>Basic</label>

                                                    <div class="mt-2">
                                                        <x-base.tom-select
                                                            class="w-full"
                                                            data-placeholder="Selecciona el articulo a agregar"
                                                            wire:change="articleSelecte"
                                                        >
                                                            <option value=""></option>
                                                            @foreach($articles as $article)
                                                                <option value="{{$article->id}}">{{$article->title}} |
                                                                    stock: {{$article->stock}} |
                                                                    sku: {{$article->sku}}</option>
                                                            @endforeach
                                                        </x-base.tom-select>
                                                    </div>
                                                </div>
                                            </x-base.highlight>
                                        </x-base.source>
                                    </div>
                                    <div
                                        class="col-span-12 sm:col-span-3 flex flex-col gap-3.5 px-5 sm:pt-1 pt-10  md:pt-10 pb-4">
                                        <div>

                                            <x-base.form-switch>
                                                <x-base.form-switch.input
                                                    id="checkbox-switch-7"
                                                    type="checkbox"
                                                    wire:model="tax"
                                                    wire:change="updateTax"
                                                />
                                                <x-base.form-switch.label for="checkbox-switch-7">
                                                    Aplicar impuesto
                                                </x-base.form-switch.label>
                                            </x-base.form-switch>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-10 rounded-[0.6rem] border border-slate-200/80">
                            <div class="overflow-auto xl:overflow-visible">
                                <x-base.table>
                                    <x-base.table.thead>
                                        <x-base.table.tr>
                                            <x-base.table.td
                                                class=" border-slate-200/80 bg-slate-50 py-4 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]"
                                            >
                                                Acción
                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="border-slate-200/80 bg-slate-50 py-4 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]"
                                            >
                                                Titulo
                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="border-slate-200/80 bg-slate-50 py-4 text-right font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]"
                                            >
                                                Cantidad
                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="border-slate-200/80 bg-slate-50 py-4 text-right font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]"
                                            >
                                                Precio
                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="border-slate-200/80 bg-slate-50 py-4 text-right font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]"
                                            >
                                                Total
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    </x-base.table.thead>
                                    <x-base.table.tbody>
                                        @if(!empty($articlesSelected))
                                            @foreach($articlesSelected as $index => $article)
                                                <x-base.table.tr class="[&_td]:last:border-b-0">
                                                    <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">
                                                        <div class="flex items-center justify-start">
                                                            <x-base.button
                                                                variant="danger"
                                                                size="sm"
                                                                wire:click="remove({{$index}})"
                                                            >
                                                                <i class="text-white fa-solid fa-trash"></i>
                                                            </x-base.button>
                                                        </div>
                                                    </x-base.table.td>
                                                    <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">
                                                        <div class="whitespace-nowrap">
                                                            {{$article['title']}}
                                                        </div>
                                                    </x-base.table.td>
                                                    <x-base.table.td
                                                        class="border-dashed py-4 text-right dark:bg-darkmode-600">
                                                        <div class="whitespace-nowrap">
                                                            <input
                                                                type="number"
                                                                min="1"
                                                                step="1"
                                                                wire:model="articlesSelected.{{ $index }}.quantity"
                                                                wire:input="updateTotal({{ $index }})"
                                                                class="w-15 text-center border rounded"
                                                            >
                                                        </div>
                                                    </x-base.table.td>
                                                    <x-base.table.td
                                                        class="border-dashed py-4 text-right dark:bg-darkmode-600">
                                                        <div class="whitespace-nowrap">
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                wire:model="articlesSelected.{{ $index }}.price"
                                                                wire:input="updateTotal({{ $index }})"
                                                                class="w-15 text-center border rounded"
                                                            >
                                                        </div>
                                                    </x-base.table.td>
                                                    <x-base.table.td
                                                        class="border-dashed py-4 text-right dark:bg-darkmode-600">
                                                        <div class="whitespace-nowrap font-medium">
                                                            $ {{$article['total']}}
                                                        </div>
                                                    </x-base.table.td>
                                                </x-base.table.tr>
                                            @endforeach
                                        @else
                                            <x-base.table.tr class="[&_td]:last:border-b-0">
                                                <x-base.table.td colspan="5"
                                                                 class="text-center border-dashed py-4 dark:bg-darkmode-600">
                                                    <div class="whitespace-nowrap">
                                                        No hay articulos seleccionados
                                                    </div>
                                                </x-base.table.td>

                                            </x-base.table.tr>
                                        @endif

                                    </x-base.table.tbody>
                                </x-base.table>
                            </div>
                        </div>

                        <div class="my-10 ml-auto flex flex-col gap-3.5 pr-5 text-right">
                            <div class="flex items-center justify-end">
                                <div class="text-slate-500">Subtotal:</div>
                                <div class="w-20 font-medium text-slate-600 sm:w-52">
                                    $ {{ number_format($this->granSubtotal, 2) }}
                                </div>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="text-slate-500">IGV:</div>
                                <div class="w-20 font-medium text-slate-600 sm:w-52">
                                    $ {{ number_format($this->granTax, 2) }}
                                </div>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="text-slate-500">Total:</div>
                                <div class="w-20 font-medium text-slate-600 sm:w-52">
                                    $ {{ number_format($this->granTotal, 2) }}
                                </div>
                            </div>
                        </div>


                        <div class="-mx-8 border-t border-dashed border-slate-200/80 px-10 pt-6">
                            <div class="mt-5 text-slate-500">©{{$articleSelected}} 2025 Hecho con <i
                                    class="fa-solid fa-heart"></i> | © InventraShop.
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
