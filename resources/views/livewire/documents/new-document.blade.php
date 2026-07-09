<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Emitir comprobante
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">

                    <span wire:loading>
                        <x-base.button
                            class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                            variant="primary"
                            disabled="true" >
                                <i class="fas fa-spinner animate-spin mr-1"></i> Emitiendo..
                        </x-base.button>
                    </span>
                    <span wire:loading.remove>
                       <x-base.button
                           class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                           variant="primary"
                           wire:click="save"
                       >
                            <i class="fa-solid fa-floppy-disk mr-2"></i>
                            Generar documento
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
                                    <div class="-mt-px">datos del comprobante</div>
                                </div>
                                <div class="grid grid-cols-12 pt-4 pb-4">


                                    <div class="col-span-12 sm:col-span-6 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <x-base.form-label for="documentType">
                                                Tipo de documento
                                            </x-base.form-label>
                                            <x-base.form-select
                                                aria-label=".form-select-lg"
                                                id="documentType"
                                                wire:model.live="documentType"
                                            >

                                                <option value="">Seleccione tipo de documento.</option>
                                                <option value="2">BOLETA</option>
                                                <option value="1">FACTURA</option>

                                            </x-base.form-select>
                                            @error('documentType')
                                            <div class="p-1">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>
                                    <div class="col-span-12 sm:col-span-6 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <x-base.form-label for="serie">
                                                Serie
                                            </x-base.form-label>
                                            <x-base.form-input
                                                id="serie"
                                                type="text"
                                                placeholder="Serie"
                                                wire:model.live="serie"
                                                disabled
                                            />
                                            @error('serie')
                                            <div class="p-1">
                                                {{$message}}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>
                                    <div class="col-span-12 sm:col-span-3 flex flex-col gap-3.5 px-5 py-2">

                                        <div>
                                            <x-base.form-label for="correlative">
                                                Correlativo
                                            </x-base.form-label>
                                            <x-base.form-input
                                                id="correlative"
                                                type="text"
                                                placeholder="Correlativo"
                                                wire:model="correlative"
                                                disabled
                                            />
                                            @error('correlative')
                                            <div class="p-1">
                                                {{$message}}
                                            </div>
                                            @enderror
                                        </div>


                                    </div>
                                    <div class="col-span-12 sm:col-span-3 flex flex-col gap-3.5 px-5 py-2">
                                        <div>

                                            <x-base.form-label for="datepicker">
                                                Fecha del documento
                                            </x-base.form-label>
                                            {{-- readonly: tipear "08/07/2026" (dd/mm) se interpreta como
                                                 mm/dd en el backend; solo se permite elegir del calendario --}}
                                            <x-base.litepicker
                                                id="datepicker"
                                                class="w-full block"
                                                data-single-mode="true"
                                                wire:model.live="date"
                                                readonly
                                            />

                                            @error('date')
                                            <div class="p-1">
                                                {{ $message }}
                                            </div>
                                            @enderror

                                        </div>


                                    </div>

                                    <div class="col-span-12 sm:col-span-6 flex flex-col px-5 py-2">

                                        <div>
                                            <label>Cliente</label>

                                            <div class="mt-2 " wire:ignore>
                                                <x-base.tom-select
                                                    id="tomClients"
                                                    wire:ignore
                                                    class="w-full"
                                                    data-placeholder="Selecciona un cliente"
                                                    wire:model="client"
                                                >

                                                </x-base.tom-select>

                                            </div>
                                            @error('client')
                                            <div class="p-1">
                                                {{$message}}
                                            </div>
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
                                                                disabled
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
                                                            S./ {{$article['total']}}
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
                                    S/. {{ number_format($this->granSubtotal, 2) }}
                                </div>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="text-slate-500">IGV:</div>
                                <div class="w-20 font-medium text-slate-600 sm:w-52">
                                    S/. {{ number_format($this->granTax, 2) }}
                                </div>
                            </div>
                            <div class="flex items-center justify-end">
                                <div class="text-slate-500">Total:</div>
                                <div class="w-20 font-medium text-slate-600 sm:w-52">
                                    S/. {{ number_format($this->granTotal, 2) }}
                                </div>
                            </div>
                        </div>

                        <div class="col-span-12 flex flex-col gap-3.5 px-5 py-2">

                            <div>
                                <x-base.form-label for="correlative">
                                    Importe en letras
                                </x-base.form-label>
                                <x-base.form-input
                                    id="correlative"
                                    type="text"
                                    placeholder="Correlativo"
                                    wire:model="legends"
                                    disabled
                                />
                                @error('correlative')
                                <div class="p-1">
                                    {{$message}}
                                </div>
                                @enderror
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

<script>
    document.addEventListener('DOMContentLoaded', function () {


        const picker = new Litepicker({
            element: document.getElementById('datepicker'),
            autoApply: true,
            singleMode: true,
            // SUNAT solo acepta emisión dentro de esta ventana (futuras dan 2329)
            minDate: '{{ \Carbon\Carbon::now()->subDays(5)->format('Y-m-d') }}',
            maxDate: '{{ now()->format('Y-m-d') }}'
        });

        new TomSelect('#tomClients', {
            valueField: 'value',
            labelField: 'text',
            searchField: 'text',
            maxItems: 1,
            create: false,
            plugins: ['clear_button'],
            load: function (query, callback) {
                if (!query.length) return callback();

                @this.
                call('searchClients', query)
                    .then(data => callback(data))
                    .catch(() => callback());
            },
            onInitialize() {
                @if(isset($clientSelected) && $clientSelected)
                // agregamos la opción que ya existe
                this.addOption({
                    value: '{{ $clientSelected->id }}',
                    text: '{{ $clientSelected->name }} - {{$clientSelected->document_number}}'
                });
                // la seleccionamos
                this.setValue('{{ $clientSelected->id }}');
                @endif
            },
            onChange: function (value) {
                try {
                    @this.
                    set('client', value);
                } catch (err) {

                }
            },
        });


        picker.on('selected', (startDate, endDate) => {
            console.log('selected picker 1');
            @this.
            set('date', startDate.format('YYYY-MM-DD'));
        });

    });


</script>


