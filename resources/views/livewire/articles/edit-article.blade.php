<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col mt-4 gap-y-3 md:mt-0 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Agregar Articulo
                </div>
            </div>
            <div class="mt-3.5 grid grid-cols-12 gap-x-6 gap-y-7 lg:gap-y-12 xl:grid-cols-12">
                <div class="relative flex flex-col col-span-12 gap-y-7 lg:col-span-12 xl:col-span-12">
                    <div class="flex flex-col p-5 box box--stacked">
                        <div class="rounded-[0.6rem] border border-slate-200/60 p-5 dark:border-darkmode-400">
                            <div
                                class="flex items-center border-b border-slate-200/60 pb-5 text-[0.94rem] font-medium dark:border-darkmode-400">

                                <div class="mx-2">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                Información del articulo
                            </div>
                            <div class="mt-5">
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Nombre del Articulo</div>
                                                <div
                                                    class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el nombre de la marca a guardar, Esto servirá para seleccionar
                                                al crear un articulo.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el nombre del articulo."
                                            wire:model="title"
                                        />


                                        @error('title')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                </div>
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Detalle del Articulo</div>

                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el nombre de la marca a guardar, Esto servirá para seleccionar
                                                al crear un articulo.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa detalle del articulo."
                                            wire:model="detail"
                                        />


                                        @error('detail')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                </div>
                                {{-- Producto a pedido --}}
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Producto a pedido</div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Si está activo, no aparecerá en Artículos sino en Productos a pedido.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                                            <input
                                                type="checkbox"
                                                wire:model="onDemand"
                                                class="form-check-switch"
                                            />
                                            <span>Marcar como producto a pedido</span>
                                        </label>
                                        @error('onDemand')
                                        <div class="p-1 text-red-600">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Descripción del Articulo</div>

                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el nombre de la marca a guardar, Esto servirá para seleccionar
                                                al crear un articulo.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <x-base.form-textarea
                                            type="text"
                                            placeholder="Ingresa una descripción para del articulo."
                                            wire:model="description"
                                        />


                                        @error('description')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                </div>
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">SKU</div>
                                                <div
                                                    class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el nombre de la marca a guardar, Esto servirá para seleccionar
                                                al crear un articulo.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el SKU del articulo."
                                            wire:model="sku"
                                            disabled
                                        />


                                        @error('sku')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                </div>
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Stock</div>

                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el stock del articulo, si no lo llenas por default será 0.
                                            </div>
                                        </div>
                                    </div>
                                    <div id="contenedor-categorias" class="flex-1 w-full mt-3 xl:mt-0">

                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el stock incial del articulo."
                                            wire:model="stock"
                                            disabled

                                        />


                                        @error('stock')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                </div>
                                {{-- Código de barras --}}
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Código de barras</div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Opcional. Puedes importarlo masivamente desde Excel.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el código de barras."
                                            wire:model="barcode"
                                        />
                                        @error('barcode')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>
                                </div>
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Seleccionar categoría</div>
                                                <div
                                                    class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el nombre de la marca a guardar, Esto servirá para seleccionar
                                                al crear un articulo.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <x-base.form-select wire:model="category_id">
                                            <option disabled selected value="">Selecciona una categoría</option>
                                            @foreach($categories as $category)
                                                <option value="{{$category->id}}">{{$category->name}}</option>
                                            @endforeach
                                        </x-base.form-select>
                                        @error('category_id')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                </div>
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Seleccionar marca</div>
                                                <div
                                                    class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el nombre de la marca a guardar, Esto servirá para seleccionar
                                                al crear un articulo.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <x-base.form-select wire:model="brand_id">
                                            <option disabled selected value="">Selecciona una marca</option>
                                            @foreach($brands as $brand)
                                                <option value="{{$brand->id}}">{{$brand->name}}</option>
                                            @endforeach
                                        </x-base.form-select>
                                        @error('brand_id')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                </div>
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Precio de compra</div>

                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el precio de compra del articulo, si no lo ingresas por default será 0.00
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el precio de compra del producto"
                                            wire:model="purchase_price"

                                        />


                                        @error('purchase_price')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                </div>
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Precio de venta</div>

                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el precio de venta del articulo, si no lo ingresas por default será 0.00
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el precio de venta del producto"
                                            wire:model="sale_price"
                                        />


                                        @error('sale_price')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                    </div>

                                </div>


                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Codigos Marketplace</div>

                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el precio de venta del articulo, si no lo ingresas por default será 0.00
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        @foreach ($rows as $i => $row)
                                            <div class="flex items-center space-x-2">
                                                <x-base.form-select
                                                class="w-30"
                                                wire:model.defer="rows.{{ $i }}.contact_id"
                                                >
                                                    @foreach($contacts as $contact)
                                                        <option value="{{$contact->id}}">{{$contact->name}}</option>
                                                    @endforeach
                                                </x-base.form-select>

                                                <x-base.form-input
                                                    wire:model.defer="rows.{{ $i }}.code"
                                                    class="w-80"
                                                    type="text"
                                                    placeholder="Código"
                                                />

                                                {{-- Botón para eliminar esta fila, sólo si hay más de una --}}
                                                @if(count($rows) > 1)
                                                    <button
                                                        wire:click.prevent="removeRow({{ $i }})"
                                                        class="text-red-500 hover:text-red-700"
                                                    >
                                                        &times;
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach

                                        <div class="flex space-x-2">
                                            <x-base.button
                                                wire:click.prevent="addRow"
                                                class="border-slate-300/80 bg-white/80"
                                                variant="outline-primary"
                                            >
                                                <i class="fa-solid fa-plus mr-1"></i> Agregar fila
                                            </x-base.button>
                                        </div>

                                        @if (session()->has('success'))
                                            <div class="text-green-600">
                                                {{ session('success') }}
                                            </div>
                                        @endif
                                    </div>

                                </div>

                                <div class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-start">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="font-medium">Precios por contacto</div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80">
                                                Si se selecciona el contacto en la venta, se usará este precio en lugar de <b>sale_price</b>.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        @foreach ($priceRows as $i => $row)
                                            <div class="flex items-center space-x-2">
                                                <x-base.form-select class="w-40" wire:model.defer="priceRows.{{ $i }}.contact_id">
                                                    <option value="">-- Contacto --</option>
                                                    @foreach($contacts as $contact)
                                                        <option value="{{ $contact->id }}">{{ $contact->name }}</option>
                                                    @endforeach
                                                </x-base.form-select>

                                                <x-base.form-input
                                                    class="w-32 text-right"
                                                    type="number" step="0.01" min="0"
                                                    wire:model.defer="priceRows.{{ $i }}.price"
                                                    placeholder="0.00"
                                                />

                                                <label class="inline-flex items-center space-x-1 text-sm">
                                                    <input type="checkbox" wire:model.defer="priceRows.{{ $i }}.active">
                                                    <span>Activo</span>
                                                </label>

                                                @if(count($priceRows) > 1)
                                                    <button wire:click.prevent="removePriceRow({{ $i }})" class="text-red-500 hover:text-red-700">
                                                        &times;
                                                    </button>
                                                @endif
                                            </div>
                                        @endforeach

                                        <div class="flex space-x-2">
                                            <x-base.button wire:click.prevent="addPriceRow" class="border-slate-300/80 bg-white/80" variant="outline-primary">
                                                <i class="fa-solid fa-plus mr-1"></i> Agregar precio
                                            </x-base.button>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col justify-end gap-3 mt-1 md:flex-row">
                        <x-base.button
                            class="w-full rounded-[0.5rem] border-slate-300/80 bg-white/80 py-2.5 md:w-56"
                            variant="outline-secondary"
                            onclick="window.location.href='{{ route('articles.index') }}'"
                        >
                            <div class="px-2">
                                <i class="fa-solid fa-xmark"></i>
                            </div>
                            Cancel
                        </x-base.button>
                        <x-base.button
                            class="w-full rounded-[0.5rem] py-2.5 md:w-56"
                            variant="primary"
                            wire:click="save"
                        >
                            <div class="px-2">
                                <i class="fa-solid fa-floppy-disk"></i>
                            </div>
                            Guardar
                        </x-base.button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
