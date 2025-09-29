<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-1">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Inventario
                </div>
            </div>
        </div>

        <div class="col-span-12 mb-8">
            <div class="box box--stacked mt-3.5 flex flex-col p-5 sm:p-6">
                <div class="col-span-12 relative mb-4 mt-7 rounded-[0.6rem] border border-slate-200/80 dark:border-darkmode-400">
                    <div class="absolute left-0 -mt-2 ml-4 bg-white px-3 text-xs uppercase text-slate-500">
                        <div class="-mt-px">Buscar Producto</div>
                    </div>

                    <div class="grid grid-cols-12 pt-4">
                        <div class="col-span-12 sm:col-span-12 flex flex-col gap-3.5 px-5 py-2">

                            {{-- Valor controlado por Livewire (fuera del modal) --}}
                            <div class="text-xs text-slate-500">
                                Valor Livewire: <span class="font-medium">{{$search}}</span>
                            </div>

                            <x-base.button
                                class="w-20"
                                data-tw-toggle="modal"
                                data-tw-target="#header-footer-modal-preview"
                                href="#"
                                as="a"
                                variant="primary"
                            >
                                Show Modal
                            </x-base.button>

                            {{-- Evitamos que Livewire re-renderice el modal (y así no se duplica) --}}
                            <x-base.dialog id="header-footer-modal-preview" wire:ignore wire:key="header-footer-modal">
                                <x-base.dialog.panel>
                                    <x-base.dialog.title>
                                        <h2 class="mr-auto text-base font-medium">
                                            Broadcast Message
                                        </h2>

                                        {{-- Botón cerrar (header) --}}
                                        <button
                                            type="button"
                                            class="ml-2 flex items-center justify-center rounded-md p-2 hover:bg-slate-100 dark:hover:bg-darkmode-300"
                                            data-tw-dismiss="modal"
                                            aria-label="Close"
                                            title="Cerrar"
                                        >
                                            <x-base.lucide class="h-5 w-5" icon="X" />
                                        </button>
                                    </x-base.dialog.title>

                                    <x-base.dialog.description class="grid grid-cols-12 gap-4 gap-y-3">
                                        <div class="col-span-12 sm:col-span-6">
                                            <x-base.form-label for="modal-form-1">From</x-base.form-label>

                                            {{-- Vista previa local (opcional) dentro del modal --}}
                                            <div class="text-xs text-slate-500 mb-1">
                                                Valor en modal:
                                                <span class="font-medium" id="modal-search-preview"></span>
                                            </div>

                                            <x-base.form-input
                                                id="modal-form-1"
                                                type="text"
                                                placeholder="example@gmail.com"
                                                oninput="(function(el){
                        // Actualiza propiedad Livewire desde fuera del árbol (teleportado)
                        if (window.Livewire && Livewire.dispatch) {
                            Livewire.dispatch('modal-search-input', { value: el.value });
                        }
                        // Previsualización local
                        var p = document.getElementById('modal-search-preview');
                        if (p) p.textContent = el.value;
                    })(this)"
                                            />
                                        </div>

                                        <div class="col-span-12 sm:col-span-6">
                                            <x-base.form-label for="modal-form-2">To</x-base.form-label>
                                            <x-base.form-input id="modal-form-2" type="text" placeholder="example@gmail.com" />
                                        </div>

                                        <div class="col-span-12 sm:col-span-6">
                                            <x-base.form-label for="modal-form-3">Subject</x-base.form-label>
                                            <x-base.form-input id="modal-form-3" type="text" placeholder="Important Meeting" />
                                        </div>

                                        <div class="col-span-12 sm:col-span-6">
                                            <x-base.form-label for="modal-form-4">Has the Words</x-base.form-label>
                                            <x-base.form-input id="modal-form-4" type="text" placeholder="Job, Work, Documentation" />
                                        </div>

                                        <div class="col-span-12 sm:col-span-6">
                                            <x-base.form-label for="modal-form-5">Doesn't Have</x-base.form-label>
                                            <x-base.form-input id="modal-form-5" type="text" placeholder="Job, Work, Documentation" />
                                        </div>

                                        <div class="col-span-12 sm:col-span-6">
                                            <x-base.form-label for="modal-form-6">Size</x-base.form-label>
                                            <x-base.form-select id="modal-form-6">
                                                <option>10</option>
                                                <option>25</option>
                                                <option>35</option>
                                                <option>50</option>
                                            </x-base.form-select>
                                        </div>
                                    </x-base.dialog.description>

                                    <x-base.dialog.footer>
                                        <x-base.button
                                            class="mr-1 w-20"
                                            data-tw-dismiss="modal"
                                            type="button"
                                            variant="outline-secondary"
                                        >
                                            Cancel
                                        </x-base.button>

                                        {{-- "Send" dispara acción Livewire (equivalente a wire:click) y cierra el modal --}}
                                        <x-base.button
                                            class="w-20"
                                            type="button"
                                            variant="primary"
                                            onclick="Livewire.dispatch('modal-send')"
                                            data-tw-dismiss="modal"
                                        >
                                            Send
                                        </x-base.button>
                                    </x-base.dialog.footer>
                                </x-base.dialog.panel>
                            </x-base.dialog>


                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
