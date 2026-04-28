<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col mt-4 gap-y-3 md:mt-0 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Agregar Producto a pedido
                </div>
            </div>

            <div class="mt-3.5 grid grid-cols-12 gap-x-6 gap-y-7 lg:gap-y-12 xl:grid-cols-12">
                <div class="relative flex flex-col col-span-12 gap-y-7">
                    <div class="flex flex-col p-5 box box--stacked">
                        <div class="rounded-[0.6rem] border border-slate-200/60 p-5 dark:border-darkmode-400">
                            <div class="flex items-center border-b border-slate-200/60 pb-5 text-[0.94rem] font-medium dark:border-darkmode-400">
                                <div class="mx-2">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                Información del producto a pedido
                            </div>

                            <div class="mt-5">
                                {{-- Nombre --}}
                                <div class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Nombre del Producto</div>
                                                <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el nombre del producto.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el nombre del producto."
                                            wire:model="title"
                                        />
                                        @error('title') <div class="p-1 text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Detalle --}}
                                <div class="flex-col block pt-5 mt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Detalle del Producto</div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Texto corto opcional.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa detalle del producto."
                                            wire:model="detail"
                                        />
                                        @error('detail') <div class="p-1 text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Producto a pedido (toggle) --}}
                                <div class="flex-col block pt-5 mt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Producto a pedido</div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Si lo desactivas, se guardará como artículo regular.
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
                                    </div>
                                </div>

                                {{-- Descripción --}}
                                <div class="flex-col block pt-5 mt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Descripción del Producto</div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Descripción ampliada opcional.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <x-base.form-textarea
                                            placeholder="Ingresa una descripción del producto."
                                            wire:model="description"
                                        />
                                        @error('description') <div class="p-1 text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Código de barras --}}
                                <div class="flex-col block pt-5 mt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Código de barras</div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Opcional.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el código de barras."
                                            wire:model="barcode"
                                        />
                                        @error('barcode') <div class="p-1 text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Categoría --}}
                                <div class="flex-col block pt-5 mt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Seleccionar categoría</div>
                                                <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <x-base.form-select wire:model="category_id">
                                            <option disabled value="">Selecciona una categoría</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @endforeach
                                        </x-base.form-select>
                                        @error('category_id') <div class="p-1 text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Marca --}}
                                <div class="flex-col block pt-5 mt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Seleccionar marca</div>
                                                <div class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <x-base.form-select wire:model="brand_id">
                                            <option disabled value="">Selecciona una marca</option>
                                            @foreach($brands as $brand)
                                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                                            @endforeach
                                        </x-base.form-select>
                                        @error('brand_id') <div class="p-1 text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Precio compra --}}
                                <div class="flex-col block pt-5 mt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Precio de compra</div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Si no lo ingresas será 0.00.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el precio de compra"
                                            wire:model="purchase_price"
                                        />
                                        @error('purchase_price') <div class="p-1 text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Precio venta principal --}}
                                <div class="flex-col block pt-5 mt-5 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Precio de venta (principal)</div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Será el precio por defecto si no existe precio por contacto.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">
                                        <x-base.form-input
                                            type="text"
                                            placeholder="Ingresa el precio de venta"
                                            wire:model="sale_price"
                                        />
                                        @error('sale_price') <div class="p-1 text-red-600">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                {{-- Precios por contacto --}}
                                <div class="mt-8 rounded-[0.6rem] border border-slate-200/60 p-4">
                                    <div class="text-[0.94rem] font-medium mb-3">
                                        Precios por contacto (opcional)
                                    </div>

                                    <div class="space-y-3">
                                        @foreach ($contactPrices as $i => $row)
                                            <div class="grid grid-cols-12 gap-3 items-center">
                                                <div class="col-span-12 sm:col-span-5">
                                                    <x-base.form-select wire:model.defer="contactPrices.{{ $i }}.contact_id">
                                                        <option value="">Selecciona un contacto</option>
                                                        @foreach($contacts as $c)
                                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                                        @endforeach
                                                    </x-base.form-select>
                                                    @error("contactPrices.$i.contact_id")
                                                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-span-12 sm:col-span-4">
                                                    <x-base.form-input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        placeholder="Precio"
                                                        wire:model.defer="contactPrices.{{ $i }}.price"
                                                    />
                                                    @error("contactPrices.$i.price")
                                                    <div class="text-xs text-red-600 mt-1">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <div class="col-span-6 sm:col-span-2">
                                                    <label class="inline-flex items-center gap-2 text-sm">
                                                        <input type="checkbox"
                                                               wire:model.defer="contactPrices.{{ $i }}.active"
                                                               class="rounded border-slate-300" />
                                                        Activo
                                                    </label>
                                                </div>

                                                <div class="col-span-6 sm:col-span-1 text-right">
                                                    @if(count($contactPrices) > 1)
                                                        <button
                                                            type="button"
                                                            class="text-red-600 hover:text-red-700"
                                                            wire:click="removeContactPriceRow({{ $i }})"
                                                        >
                                                            <i class="fa-solid fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach

                                        <div>
                                            <x-base.button
                                                type="button"
                                                class="border-slate-300/80 bg-white/80"
                                                variant="outline-primary"
                                                wire:click="addContactPriceRow"
                                            >
                                                <i class="fa-solid fa-plus mr-1"></i> Agregar precio por contacto
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
                            onclick="window.location.href='{{ route('on-demand-products.index') }}'"
                        >
                            <div class="px-2"><i class="fa-solid fa-xmark"></i></div>
                            Cancel
                        </x-base.button>

                        <x-base.button
                            class="w-full rounded-[0.5rem] py-2.5 md:w-56"
                            variant="primary"
                            wire:click="save"
                        >
                            <div class="px-2"><i class="fa-solid fa-floppy-disk"></i></div>
                            Guardar
                        </x-base.button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
