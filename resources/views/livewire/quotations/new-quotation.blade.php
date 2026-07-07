<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Nueva Cotización
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        x-on:click="$dispatch('open-client-modal')"
                    >
                        <i class="fa-solid fa-user-plus mr-2"></i>

                        Nuevo cliente
                    </x-base.button>
                    <span wire:loading>
                        <x-base.button
                            class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                            variant="primary"
                            disabled="true">
                                <i class="fas fa-spinner animate-spin mr-1"></i> Guardando..
                        </x-base.button>
                    </span>
                    <span wire:loading.remove>
                       <x-base.button
                           class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                           variant="primary"
                           wire:click="save"
                       >
                            <i class="fa-solid fa-floppy-disk mr-2"></i>
                            Guardar cotización
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
                                    <div class="-mt-px">datos de la cotización</div>
                                </div>
                                <div class="grid grid-cols-12 pt-4">
                                    <div class="col-span-12 sm:col-span-6 flex flex-col px-5 py-2">
                                        <div>
                                            <label>Cliente</label>
                                            <div class="mt-2" wire:ignore>
                                                <x-base.tom-select
                                                    id="tomClients"
                                                    wire:ignore
                                                    class="w-full"
                                                    data-placeholder="Busque y seleccione un cliente"
                                                    wire:model="client"
                                                >
                                                </x-base.tom-select>
                                            </div>
                                            @error('client')
                                            <div class="p-1 text-red-600">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-span-12 sm:col-span-3 flex flex-col gap-3.5 px-5 py-2">
                                        <div>
                                            <x-base.form-label for="datepicker">
                                                Fecha de la cotización
                                            </x-base.form-label>
                                            <x-base.litepicker
                                                id="datepicker"
                                                class="w-full block"
                                                data-single-mode="true"
                                                wire:model.live="date"
                                            />
                                            @error('date')
                                            <div class="p-1 text-red-600">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-span-12 sm:col-span-3 flex flex-col gap-3.5 px-5 py-2">
                                        <div>
                                            <x-base.form-label for="validDays">
                                                Validez (días)
                                            </x-base.form-label>
                                            <x-base.form-input
                                                id="validDays"
                                                type="number"
                                                min="1"
                                                max="90"
                                                placeholder="Días de validez"
                                                wire:model="validDays"
                                            />
                                            @error('validDays')
                                            <div class="p-1 text-red-600">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-span-12 sm:col-span-6 flex flex-col gap-3.5 px-5 py-2">
                                        <div>
                                            <label>Agregar Articulo</label>
                                            <div class="mt-2" wire:ignore>
                                                <x-base.tom-select
                                                    id="tomArticles"
                                                    class="w-full"
                                                    data-placeholder="Busque y seleccione los articulos a agregar"
                                                    wire:model.live="articleSelected"
                                                >
                                                </x-base.tom-select>
                                            </div>
                                            @error('articlesSelected')
                                            <div class="p-1 text-red-600">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="col-span-12 sm:col-span-3 flex flex-col gap-3.5 px-5 sm:pt-1 pt-10 md:pt-10 pb-4">
                                        <div>
                                            <x-base.form-switch>
                                                <x-base.form-switch.input
                                                    id="checkbox-switch-tax"
                                                    type="checkbox"
                                                    wire:model="tax"
                                                    wire:change="updateTax"
                                                />
                                                <x-base.form-switch.label for="checkbox-switch-tax">
                                                    Aplicar impuesto
                                                </x-base.form-switch.label>
                                            </x-base.form-switch>
                                        </div>
                                    </div>

                                    <div class="col-span-12 flex flex-col gap-3.5 px-5 py-2 pb-5">
                                        <div>
                                            <x-base.form-label for="notes">
                                                Observaciones (se muestran en el PDF)
                                            </x-base.form-label>
                                            <x-base.form-textarea
                                                id="notes"
                                                placeholder="Condiciones de entrega, tiempo de atención, garantía, etc."
                                                wire:model="notes"
                                            ></x-base.form-textarea>
                                            @error('notes')
                                            <div class="p-1 text-red-600">{{ $message }}</div>
                                            @enderror
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
                                                class="border-slate-200/80 bg-slate-50 py-4 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]">
                                                Acción
                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="border-slate-200/80 bg-slate-50 py-4 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]">
                                                Titulo
                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="border-slate-200/80 bg-slate-50 py-4 text-right font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]">
                                                Cantidad
                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="border-slate-200/80 bg-slate-50 py-4 text-right font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]">
                                                Precio
                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="border-slate-200/80 bg-slate-50 py-4 text-right font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]">
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
                                                                <i class="fas fa-spinner animate-spin mr-1"></i> Calculando..
                                                            </span>
                                                            <span wire:loading.remove>
                                                                S/. {{ number_format((float)$article['total'], 2) }}
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
                                    S/. {{ number_format((float)$this->granSubtotal, 2) }}
                                </div>
                            </div>
                            @if($tax)
                                <div class="flex items-center justify-end">
                                    <div class="text-slate-500">IGV:</div>
                                    <div class="w-20 font-medium text-slate-600 sm:w-52">
                                        S/. {{ number_format((float)$this->granTax, 2) }}
                                    </div>
                                </div>
                            @endif
                            <div class="flex items-center justify-end">
                                <div class="text-slate-500">Total:</div>
                                <div class="w-20 font-medium text-slate-600 sm:w-52">
                                    S/. {{ number_format((float)$this->granTotal, 2) }}
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

    <!-- Modal Nuevo Cliente (Alpine.js) -->
    <div
        x-data="{ open: false }"
        x-on:open-client-modal.window="open = true"
        x-on:close-client-modal.window="open = false"
        x-on:keydown.escape.window="open = false"
    >
        <!-- Backdrop -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-[1050] bg-gradient-to-b from-theme-1/50 via-theme-2/50 to-black/50"
            x-on:click="open = false"
            style="display: none;"
        ></div>

        <!-- Panel -->
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-8"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-8"
            class="fixed inset-0 z-[1051] overflow-y-auto"
            style="display: none;"
        >
            <div class="flex min-h-full items-center justify-center p-4" x-on:click.self="open = false">
                <div class="w-[90%] lg:w-[900px] bg-white rounded-md shadow-md dark:bg-darkmode-600" x-on:click.stop>

                    <!-- Header -->
                    <div class="flex items-center px-5 py-3 border-b border-slate-200/60 dark:border-darkmode-400">
                        <h2 class="text-base font-medium mr-auto">Crear cliente</h2>
                    </div>

                    <!-- Body -->
                    <div class="p-5">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12 sm:col-span-4">
                                <x-base.form-label for="document_type">
                                    Tipo de documento
                                </x-base.form-label>
                                <x-base.form-select
                                    id="document_type"
                                    wire:model.live="document_type"
                                >
                                    <option value="">Selecciona una opción</option>
                                    <option value="DNI">DNI</option>
                                    <option value="RUC">RUC</option>
                                    <option value="CE">CE</option>
                                </x-base.form-select>
                                @error('document_type')
                                <div class="p-1 text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <x-base.form-label for="document_number">
                                    Número de documento
                                </x-base.form-label>
                                <div class="flex items-center gap-2">
                                    <x-base.form-input
                                        id="document_number"
                                        class="flex-1"
                                        type="text"
                                        placeholder="Número de documento del cliente"
                                        wire:model="document_number"
                                    />
                                    <span wire:loading wire:target="searchDocument">
                                        <x-base.button class="w-16" variant="primary">
                                            <x-base.loading-icon
                                                class="ml-2 h-4 w-4"
                                                icon="three-dots"
                                                color="white"
                                            />
                                        </x-base.button>
                                    </span>
                                    <span wire:loading.remove wire:target="searchDocument">
                                        <x-base.button
                                            class="w-16 h-[40px]"
                                            variant="primary"
                                            wire:click="searchDocument"
                                        >
                                            <i class="fa-solid fa-magnifying-glass mr-2"></i>
                                        </x-base.button>
                                    </span>
                                </div>
                                @error('document_number')
                                <div class="p-1 text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <x-base.form-label for="name">
                                    Nombre o razón social
                                </x-base.form-label>
                                <x-base.form-input
                                    id="name"
                                    type="text"
                                    placeholder="Nombre o Razón social del cliente."
                                    wire:model="name"
                                />
                                @error('name')
                                <div class="p-1 text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <x-base.form-label for="address">
                                    Dirección
                                </x-base.form-label>
                                <x-base.form-input
                                    id="address"
                                    type="text"
                                    placeholder="Dirección del cliente"
                                    wire:model="address"
                                />
                                @error('address')
                                <div class="p-1 text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <x-base.form-label for="phone">
                                    Teléfono
                                </x-base.form-label>
                                <x-base.form-input
                                    id="phone"
                                    type="text"
                                    placeholder="Teléfono del cliente"
                                    wire:model="phone"
                                />
                                @error('phone')
                                <div class="p-1 text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <x-base.form-label for="email">
                                    Email
                                </x-base.form-label>
                                <x-base.form-input
                                    id="email"
                                    type="text"
                                    placeholder="Email del cliente"
                                    wire:model="email"
                                />
                                @error('email')
                                <div class="p-1 text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <x-base.form-label for="department">
                                    Departamento
                                </x-base.form-label>
                                <x-base.form-select
                                    wire:model.live="departmentSelect"
                                >
                                    <option value="">Selecciona una opción</option>
                                    @foreach($departments as $department)
                                        <option value="{{$department->id}}">{{$department->name}}</option>
                                    @endforeach
                                </x-base.form-select>
                                @error('departmentSelect')
                                <div class="p-1 text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <x-base.form-label for="province">
                                    Provincias
                                </x-base.form-label>
                                <x-base.form-select
                                    wire:model.live="provinceSelect"
                                >
                                    <option value="">Selecciona una opción</option>
                                    @foreach($provinces as $province)
                                        <option value="{{$province->id}}">{{html_entity_decode($province->name)}}</option>
                                    @endforeach
                                </x-base.form-select>
                                @error('provinceSelect')
                                <div class="p-1 text-red-600">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-span-12 sm:col-span-4">
                                <x-base.form-label for="district">
                                    Distrito
                                </x-base.form-label>
                                <x-base.form-select
                                    wire:model.live="districtSelect"
                                >
                                    <option value="">Selecciona una opción</option>
                                    @foreach($districts as $district)
                                        <option value="{{$district->id}}">{{html_entity_decode($district->name)}}</option>
                                    @endforeach
                                </x-base.form-select>
                                @error('districtSelect')
                                <div class="p-1 text-red-600">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end px-5 py-3 border-t border-slate-200/60 dark:border-darkmode-400">
                        <div x-data="{ copied: false }" class="mr-2">
                            <x-base.button
                                type="button"
                                variant="outline-secondary"
                                x-on:click="
                                    const value = document.getElementById('document_number').value;
                                    if (!value) return;
                                    const done = () => { copied = true; setTimeout(() => copied = false, 1500); };
                                    if (navigator.clipboard && window.isSecureContext) {
                                        navigator.clipboard.writeText(value).then(done).catch(() => {});
                                    } else {
                                        const ta = document.createElement('textarea');
                                        ta.value = value;
                                        ta.style.position = 'fixed';
                                        ta.style.opacity = '0';
                                        document.body.appendChild(ta);
                                        ta.focus(); ta.select();
                                        try { document.execCommand('copy'); done(); } catch (e) {}
                                        document.body.removeChild(ta);
                                    }
                                "
                            >
                                <i class="fa-solid mr-2" :class="copied ? 'fa-check text-success' : 'fa-copy'"></i>
                                <span x-text="copied ? 'Copiado' : 'Copiar DNI'"></span>
                            </x-base.button>
                        </div>
                        <x-base.button variant="outline-secondary" class="mr-2" x-on:click="open = false">
                            Cancelar
                        </x-base.button>
                        <x-base.button variant="primary" wire:click="saveClient">
                            Guardar
                        </x-base.button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        const picker = new Litepicker({
            element: document.getElementById('datepicker'),
            autoApply: true,
            singleMode: true
        });

        const tomClients = new TomSelect('#tomClients', {
            valueField: 'value',
            labelField: 'text',
            searchField: 'text',
            maxItems: 1,
            create: false,
            plugins: ['clear_button'],
            load: function (query, callback) {
                if (!query.length) return callback();
                @this.call('searchClients', query)
                    .then(data => callback(data))
                    .catch(() => callback());
            },
            onChange: function (value) {
                @this.set('client', value);
            },
        });

        window.addEventListener('reinitTomSelectClients', event => {
            tomClients.clear();
            tomClients.clear(true);
            @this.set('client', null);
        });

        window.addEventListener('clientCreated', event => {
            const data = event.detail;
            if (!data || !data.value) return;
            tomClients.addOption({ value: data.value, text: data.text });
            tomClients.refreshOptions(false);
            tomClients.addItem(data.value);
        });

        new TomSelect('#tomArticles', {
            valueField: 'value',
            labelField: 'text',
            searchField: 'text',
            maxItems: 1,
            plugins: ['clear_button'],
            create: false,
            loadThrottle: 300,
            load: function (query, callback) {
                if (!query.length) return callback();
                @this.call('searchArticles', query)
                    .then(data => callback(data))
                    .catch(() => callback());
            },
            onItemAdd: function (value, $item) {
                this.clear();
                @this.set('articleSelected', value);
            }
        });

        picker.on('selected', (startDate, endDate) => {
            @this.set('date', startDate.format('YYYY-MM-DD'));
        });

    });
</script>
