<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Compra #{{$number}}
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        onclick="window.location.href='{{ route('purchases.index') }}'"
                    >
                        <i class="fa-solid fa-cart-shopping mr-2"></i>

                        Ir a compras
                    </x-base.button>
                    @php
                        // Fuera del atributo a proposito: un "<" dentro de los
                        // atributos de un componente x-* rompe el parser de Blade.
                        $sinProductos = $activasEnPantalla < 1;
                    @endphp
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200 {{ $sinProductos ? 'opacity-50 cursor-not-allowed' : '' }}"
                        variant="primary"
                        wire:click="save"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        :disabled="$sinProductos"
                        title="{{ $sinProductos ? 'La compra debe conservar al menos un producto' : 'Guardar los cambios de la compra' }}"
                    >
                        <i class="fa-solid fa-floppy-disk mr-2"></i>

                        Guardar Compra
                    </x-base.button>
                </div>
            </div>

            <div class="mt-3.5 grid grid-cols-12 gap-x-6 gap-y-10">

                <div class="col-span-12 xl:col-span-12">
                    <div class="box box--stacked flex flex-col p-5 sm:p-14">
                        <div class="grid grid-cols-12">

                            <div
                                class="col-span-12 relative mb-4 mt-7 rounded-[0.6rem] border border-slate-200/80 dark:border-darkmode-400 pb-4">
                                <div class="absolute left-0 -mt-2 ml-4 bg-white px-3 text-xs uppercase text-slate-500">
                                    <div class="-mt-px">datos de la compra</div>
                                </div>
                                <div class="grid grid-cols-12 pt-4">
                                    <div class="col-span-12 sm:col-span-6 flex flex-col px-5 py-2">

                                        <div>

                                            <x-base.form-label for="provider">
                                                Proveedor
                                            </x-base.form-label>
                                            <x-base.form-select
                                                id="provider"
                                                type="text"
                                                wire:model="provider"
                                            >
                                                @foreach($providers as $p)
                                                    <option value="{{$p->id}}">{{$p->name}}</option>
                                                @endforeach
                                            </x-base.form-select>
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
                                                disabled
                                            >
                                                <option value="">Selecciona un tipo de documento</option>
                                                <option value="FACTURA">Factura</option>
                                                <option value="BOLETA">Boleta</option>
                                                <option value="TICKET">Ticket</option>
                                            </x-base.form-select>
                                            @error('voucher_type')
                                            <div class="p-1 text-red-600">
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
                                                disabled
                                            />
                                            @error('document')
                                            <div class="p-1 text-red-600">
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
                                                disabled
                                            />
                                            @error('passenger')
                                            <div class="p-1 text-red-600">
                                                {{$message}}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>

                                </div>
                            </div>
                        </div>
                        @if($anulada)
                            <div class="mt-10 rounded border-l-4 border-slate-400 bg-slate-50 p-4 text-slate-600">
                                <p class="font-bold">Compra anulada</p>
                                <p class="text-sm">
                                    Al anularla su stock ya se descontó del inventario, así que sus productos
                                    no se pueden modificar.
                                </p>
                            </div>
                        @elseif($sinProductos)
                            <div class="mt-10 rounded border-l-4 border-red-500 bg-red-50 p-4 text-red-800">
                                <p class="font-bold">La compra se quedaría sin productos</p>
                                <p class="text-sm">
                                    Restaura al menos uno para poder guardar. Si lo que quieres es dejar la
                                    compra sin efecto, anúlala desde el listado de compras.
                                </p>
                            </div>
                        @elseif($hayCambios)
                            <div class="mt-10 rounded border-l-4 border-amber-500 bg-amber-50 p-4 text-amber-800">
                                <p class="font-bold">Hay cambios sin guardar</p>
                                <p class="text-sm">
                                    Los productos marcados se quitarán (o se volverán a incluir) y su stock se
                                    ajustará cuando pulses <strong>Guardar Compra</strong>. Si sales de esta
                                    pantalla sin guardar, no se aplicará nada.
                                </p>
                            </div>
                        @endif

                        <div class="mt-10 rounded-[0.6rem] border border-slate-200/80">
                            <div class="overflow-auto xl:overflow-visible">
                                <x-base.table>
                                    <x-base.table.thead>
                                        <x-base.table.tr>
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
                                            @if($esSuperAdmin)
                                                <x-base.table.td
                                                    class="border-slate-200/80 bg-slate-50 py-4 text-center font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]"
                                                >
                                                    Acción
                                                </x-base.table.td>
                                            @endif
                                        </x-base.table.tr>
                                    </x-base.table.thead>
                                    <x-base.table.tbody>
                                        @if(!empty($articlesSelected))
                                            @foreach($articlesSelected as $article)
                                                @php
                                                    // Estado que ve el usuario: el guardado, invertido si hay
                                                    // un cambio pendiente de guardar.
                                                    $quitada = $article['marcado'] ? !$article['eliminado'] : $article['eliminado'];
                                                    $pendiente = $article['marcado'];
                                                @endphp
                                                <x-base.table.tr
                                                    class="[&_td]:last:border-b-0 {{ $quitada ? 'bg-slate-50/50' : '' }}">

                                                    <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">
                                                        <div
                                                            class="whitespace-nowrap {{ $quitada ? 'line-through text-slate-400' : '' }}">
                                                            {{$article['title']}}
                                                        </div>
                                                        @if($quitada)
                                                            <div class="mt-1 flex items-center gap-2 whitespace-nowrap">
                                                                <span
                                                                    class="rounded-full px-2 py-0.5 text-xs font-medium {{ $pendiente ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700' }}">
                                                                    {{ $pendiente ? 'SE QUITARÁ AL GUARDAR' : 'QUITADO' }}
                                                                </span>
                                                                @if(!$pendiente && $article['deleted_at'])
                                                                    <span class="text-xs text-slate-400">
                                                                        {{ $article['deleted_by'] ? 'por '.$article['deleted_by'].' · ' : '' }}{{ $article['deleted_at'] }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @elseif($pendiente)
                                                            <div class="mt-1 whitespace-nowrap">
                                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                                                    SE RESTAURARÁ AL GUARDAR
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </x-base.table.td>
                                                    <x-base.table.td
                                                        class="border-dashed py-4 text-right dark:bg-darkmode-600">
                                                        <div class="whitespace-nowrap {{ $quitada ? 'line-through text-slate-400' : '' }}">
                                                            {{$article['quantity']}}
                                                        </div>
                                                    </x-base.table.td>
                                                    <x-base.table.td
                                                        class="border-dashed py-4 text-right dark:bg-darkmode-600">
                                                        <div class="whitespace-nowrap {{ $quitada ? 'line-through text-slate-400' : '' }}">
                                                            $ {{number_format($article['price'],2)}}
                                                        </div>
                                                    </x-base.table.td>
                                                    <x-base.table.td
                                                        class="border-dashed py-4 text-right dark:bg-darkmode-600">
                                                        <div class="whitespace-nowrap font-medium {{ $quitada ? 'line-through text-slate-400' : '' }}">
                                                            $ {{number_format($article['total'],2)}}
                                                        </div>
                                                        @if($quitada)
                                                            <div class="whitespace-nowrap text-xs text-slate-400">
                                                                {{ $pendiente ? 'dejará de sumar al guardar' : 'no suma al total' }}
                                                            </div>
                                                        @endif
                                                    </x-base.table.td>
                                                    @if($esSuperAdmin)
                                                        <x-base.table.td
                                                            class="border-dashed py-4 text-center dark:bg-darkmode-600">
                                                            <div class="flex items-center justify-center">
                                                                @if(!$puedeQuitar)
                                                                    <x-base.button
                                                                        variant="danger"
                                                                        size="sm"
                                                                        class="opacity-50 cursor-not-allowed"
                                                                        disabled
                                                                        title="La compra está anulada: sus productos ya no se pueden modificar"
                                                                    >
                                                                        <i class="text-white fa-solid fa-lock"></i>
                                                                    </x-base.button>
                                                                @elseif($quitada)
                                                                    <x-base.button
                                                                        variant="secondary"
                                                                        size="sm"
                                                                        title="Volver a incluir el producto en la compra"
                                                                        wire:click="toggleProducto({{ $article['detail_id'] }})"
                                                                    >
                                                                        <i class="fa-solid fa-rotate-left"></i>
                                                                    </x-base.button>
                                                                @else
                                                                    <x-base.button
                                                                        variant="danger"
                                                                        size="sm"
                                                                        title="Quitar producto de la compra"
                                                                        wire:click="toggleProducto({{ $article['detail_id'] }})"
                                                                    >
                                                                        <i class="text-white fa-solid fa-trash"></i>
                                                                    </x-base.button>
                                                                @endif
                                                            </div>
                                                        </x-base.table.td>
                                                    @endif
                                                </x-base.table.tr>
                                            @endforeach
                                        @else
                                            <x-base.table.tr class="[&_td]:last:border-b-0">
                                                <x-base.table.td colspan="{{ $esSuperAdmin ? 5 : 4 }}"
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
                            <div class="mt-5 text-slate-500">© {{ date('Y') }} Hecho con <i class="fa-solid fa-heart"></i> | ©
                                InventraShop.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
