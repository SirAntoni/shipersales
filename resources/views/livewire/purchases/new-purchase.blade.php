<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Nueva Compra
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <span wire:loading>
                        <x-base.button
                            class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                            variant="primary"
                            disabled="true" >
                                <i class="fas fa-spinner animate-spin"></i> Calculando..
                        </x-base.button>
                    </span>
                    <span wire:loading.remove>
                       <x-base.button
                           class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                           variant="primary"
                           wire:click="save"
                       >
                            <i class="fa-solid fa-floppy-disk mr-2"></i>
                            Guardar compra
                        </x-base.button>
                     </span>
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
                                    <div class="col-span-12 sm:col-span-6 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <x-base.form-label for="provider">
                                                Proveedor
                                            </x-base.form-label>
                                            <x-base.form-select
                                                aria-label=".form-select-lg"
                                                id="provider"
                                                wire:model="provider"
                                            >
                                                <option value="">Selecciona un proveedor</option>

                                                @foreach($providers as $provider)
                                                <option value="{{$provider->id}}">{{$provider->name}}</option>
                                                @endforeach
                                            </x-base.form-select>
                                            @error('provider')
                                            <div class="p-1 text-red-600">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>


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
                                            <div class="p-1 text-red-600">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>
                                    <div class="col-span-12 sm:col-span-4 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <x-base.form-label for="document">
                                                Número de factura
                                            </x-base.form-label>
                                            <x-base.form-input
                                                id="document"
                                                type="text"
                                                placeholder="Ingresa número de factura"
                                                wire:model="document"
                                            />
                                            @error('document')
                                            <div class="p-1 text-red-600">
                                                {{$message}}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>

                                    <div class="col-span-12 sm:col-span-5 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <x-base.form-label for="passenger">
                                                Pasajero
                                            </x-base.form-label>
                                            <x-base.form-input
                                                id="passenger"
                                                type="text"
                                                placeholder="Ingresa nombre del pasajero"
                                                wire:model="passenger"
                                            />
                                            @error('passenger')
                                            <div class="p-1 text-red-600">
                                                {{$message}}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>


                                    @auth
                                        @if (auth()->id() === 1)
                                            <div
                                                class="col-span-12 sm:col-span-3 flex flex-col gap-3.5 px-5 sm:pt-1 pt-10  md:pt-10 pb-4">
                                                <div>
                                                    <x-base.form-switch>
                                                        <x-base.form-switch.input
                                                            id="checkbox-switch-7"
                                                            type="checkbox"
                                                            wire:model="status"
                                                            wire:change="updateStatus"
                                                        />
                                                        <x-base.form-switch.label for="checkbox-switch-7">
                                                            Finalizado
                                                        </x-base.form-switch.label>
                                                    </x-base.form-switch>
                                                </div>

                                            </div>
                                        @endif
                                    @endauth
                                    <div class="col-span-12 sm:col-span-9 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <label>Agregar Articulo</label>

                                            <div class="mt-2" wire:ignore>
                                                <x-base.tom-select
                                                    id="tomArticles"
                                                    class="w-full"
                                                    data-placeholder="Selecciona el articulo a agregar"
                                                    wire:model.live="articleSelected"
                                                >
                                                </x-base.tom-select>
                                            </div>
                                            @error('articlesSelected')
                                            <div class="p-1 text-red-600">
                                                {{$message}}
                                            </div>
                                            @enderror
                                        </div>


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
                                                                wire:input.debounce.1000ms="updateTotal({{ $index }})"
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
                                                                wire:input.debounce.1000ms="updateTotal({{ $index }})"
                                                                class="w-15 text-center border rounded"
                                                            >
                                                        </div>
                                                    </x-base.table.td>
                                                    <x-base.table.td
                                                        class="border-dashed py-4 text-right dark:bg-darkmode-600">
                                                        <div class="whitespace-nowrap font-medium">
                                                            <span wire:loading>
                                                                <i class="fas fa-spinner animate-spin"></i> Calculando..
                                                            </span>
                                                            <span wire:loading.remove>
                                                                $ {{$article['total']}}
                                                            </span>

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
                                    <span wire:loading>
                                                                <i class="fas fa-spinner animate-spin"></i> Calculando..
                                                            </span>
                                    <span wire:loading.remove>
                                                                 $ {{ number_format($this->granSubtotal, 2) }}
                                                            </span>

                                </div>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="text-slate-500">IGV:</div>
                                <div class="w-20 font-medium text-slate-600 sm:w-52">
                                    <span wire:loading>
                                                                <i class="fas fa-spinner animate-spin"></i> Calculando..
                                                            </span>
                                    <span wire:loading.remove>
                                                                  $ {{ number_format($this->granTax, 2) }}
                                                            </span>

                                </div>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="text-slate-500">Total:</div>
                                <div class="w-20 font-medium text-slate-600 sm:w-52">
                                    <span wire:loading>
                                                                <i class="fas fa-spinner animate-spin"></i> Calculando..
                                                            </span>
                                    <span wire:loading.remove>
                                                                  $ {{ number_format($this->granTotal, 2) }}
                                                            </span>

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

<script>
    document.addEventListener('DOMContentLoaded', function () {

        new TomSelect('#tomArticles', {
            valueField: 'value',
            labelField: 'text',
            searchField: 'text',
            maxItems: 1,
            create: false,
            render: {
                option: function(data, escape) {

                    let label = escape(data.text);

                    // 2) Busca el número de stock ("stock: 12")
                    const match = data.text.match(/stock:\s*(\d+)/);
                    if (match) {
                        const stock = parseInt(match[1], 10);
                        const cls   = stock > 10 ? 'mark-green' : 'mark-red';
                        // 3) Reemplaza esa parte por un <span> coloreado
                        label = label.replace(
                            /stock:\s*\d+/,
                            `stock: <span class="${cls}">${stock}</span>`
                        );
                    }

                    return `<div>${label}</div>`;
                },
                item: function(data, escape) {
                    let label = escape(data.text);
                    const match = data.text.match(/stock:\s*(\d+)/);
                    if (match) {
                        const stock = parseInt(match[1], 10);
                        const cls   = stock > 10 ? 'mark-green' : 'mark-red';
                        label = label.replace(
                            /stock:\s*\d+/,
                            `stock: <span class="${cls}">${stock}</span>`
                        );
                    }
                    return `<div>${label}</div>`;
                }
            },
            load: function (query, callback) {
                if (!query.length) return callback();

                @this.
                call('searchArticles', query)
                    .then(data => callback(data))
                    .catch(() => callback());
            },
            onItemAdd: function(value, $item) {
                this.clear();
                @this.set('articleSelected', value);
            }
        });

    });


</script>
