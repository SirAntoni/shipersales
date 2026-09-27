<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col mt-4 gap-y-3 md:mt-0 md:min-h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Agregar Marca
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
                                Información de la marca
                            </div>
                            <div class="mt-5">

                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Nombre de la marca</div>
                                                <div
                                                    class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                Ingresa el nombre de la marca a guardar, Esto servirá para seleccionar al crear un articulo.
                                            </div>
                                        </div>
                                    </div>
                                    <div id="contenedor-categorias" class="flex-1 w-full mt-3 xl:mt-0">
                                        @foreach($names as $index => $name)
                                            <x-base.form-input
                                                class="mb-2"
                                                type="text"
                                                placeholder="Nombre de la marca"
                                                wire:model="names.{{ $index }}"
                                            />
                                            @error('names.'.$index)
                                            <div class="p-1 text-red-600">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        @endforeach
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col justify-end gap-3 mt-1 md:flex-row">
                        <x-base.button
                            class="w-full rounded-[0.5rem] border-slate-300/80 bg-white/80 py-2.5 md:w-56"
                            variant="outline-secondary"
                            onclick="window.location.href='{{ route('brands.index') }}'"
                        >
                            <div class="px-2">
                                <i class="fa-solid fa-xmark"></i>
                            </div>
                            Cancel
                        </x-base.button>
                        <x-base.button
                            class="w-full rounded-[0.5rem] border-slate-300/80 bg-white/80 py-2.5 md:w-56"
                            variant="outline-secondary"
                            wire:click="addField"
                        >
                            <div class="px-2">
                                <i class="fa-solid fa-plus"></i>
                            </div>
                            Agregar Campo
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
