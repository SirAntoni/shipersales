<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col mt-4 gap-y-3 md:mt-0 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Reportes
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
                                Reporte diario
                            </div>
                            <div class="mt-5">
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Fecha</div>
                                                <div
                                                    class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                El reporte se exportarà con la fecha ingresada.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <div>
                                            <x-base.litepicker
                                                id="datepickerDayli"
                                                placeholder="Ingrese una fecha valida."
                                                class="w-full block"
                                                wire:model.live="date"
                                            />
                                            @error('date')
                                            <div class="p-1 text-red-600">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                    </div>


                                </div>
                            </div>
                            <div class="flex flex-col justify-end gap-3 mt-1 md:flex-row mt-4">
                                <x-base.button
                                    class="w-full rounded-[0.5rem] py-2.5 md:w-56"
                                    variant="primary"
                                    wire:click="reportDayli"
                                >
                                    <div class="px-2">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                    Exportar
                                </x-base.button>
                            </div>
                        </div>
                    </div>


                    <div class="flex flex-col p-5 box box--stacked">
                        <div class="rounded-[0.6rem] border border-slate-200/60 p-5 dark:border-darkmode-400">
                            <div
                                class="flex items-center border-b border-slate-200/60 pb-5 text-[0.94rem] font-medium dark:border-darkmode-400">

                                <div class="mx-2">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                Reporte mensual
                            </div>
                            <div class="mt-5">
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">

                                    <div class="flex-1 w-full mt-3 xl:mt-3">

                                        <div class="grid grid-cols-12 gap-2">

                                            <x-base.form-select
                                                class="col-span-4"
                                                aria-label=".form-select-lg"
                                                wire:model="month"
                                            >
                                                <option value="">Selecciona un mes</option>
                                                <option value="01">Enero</option>
                                                <option value="02">Febrero</option>
                                                <option value="03">Marzo</option>
                                                <option value="04">Abril</option>
                                                <option value="05">Mayo</option>
                                                <option value="06">Junio</option>
                                                <option value="07">Julio</option>
                                                <option value="08">Agosto</option>
                                                <option value="09">Setiembre</option>
                                                <option value="10">Octubre</option>
                                                <option value="11">Noviembre</option>
                                                <option value="12">Diciembre</option>

                                            </x-base.form-select>



                                            <x-base.form-select
                                                class="col-span-4"
                                                aria-label=".form-select-lg"
                                                wire:model="year"
                                            >
                                                <option value="">Selecciona un año</option>
                                                @foreach(range(date('Y'), 2020) as $y)
                                                    <option value="{{ $y }}">{{ $y }}</option>
                                                @endforeach


                                            </x-base.form-select>
                                            <x-base.form-select
                                                class="col-span-4"
                                                aria-label=".form-select-lg"
                                                wire:model="provider"
                                            >
                                                <option value="">Selecciona un proveedor</option>
                                                @foreach($providers as $p)
                                                    <option value="{{$p->id}}">{{$p->name}}</option>
                                                @endforeach


                                            </x-base.form-select>
                                        </div>
                                        @error('month')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                        @error('year')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror

                                        @error('provider')
                                        <div class="p-1 text-red-600">
                                            {{ $message }}
                                        </div>
                                        @enderror
                                    </div>


                                </div>
                            </div>
                            <div class="flex flex-col justify-end gap-3 md:flex-row mt-6">
                                <x-base.button
                                    class="w-full rounded-[0.5rem] py-2.5 md:w-56"
                                    variant="primary"
                                    wire:click="reportMonth"
                                >
                                    <div class="px-2">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                    Exportar
                                </x-base.button>
                            </div>
                        </div>
                    </div>


                    <div class="flex flex-col p-5 box box--stacked">
                        <div class="rounded-[0.6rem] border border-slate-200/60 p-5 dark:border-darkmode-400">
                            <div
                                class="flex items-center border-b border-slate-200/60 pb-5 text-[0.94rem] font-medium dark:border-darkmode-400">

                                <div class="mx-2">
                                    <i class="fa-solid fa-plus"></i>
                                </div>
                                Reporte personalizado
                            </div>
                            <div class="mt-5">
                                <div
                                    class="flex-col block pt-5 mt-5 first:mt-0 first:pt-0 sm:flex xl:flex-row xl:items-center">
                                    <div class="inline-block mb-2 sm:mb-0 sm:mr-5 sm:text-right xl:mr-14 xl:w-60">
                                        <div class="text-left">
                                            <div class="flex items-center">
                                                <div class="font-medium">Rango de fechas</div>
                                                <div
                                                    class="ml-2.5 rounded-md border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-darkmode-300 dark:text-slate-400">
                                                    Required
                                                </div>
                                            </div>
                                            <div class="mt-1.5 text-xs leading-relaxed text-slate-500/80 xl:mt-3">
                                                El reporte de exportará según el rango de fechas elegido.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1 w-full mt-3 xl:mt-0">

                                        <div>
                                            <x-base.litepicker
                                                id="datepickerCustom"
                                                placeholder="Ingrese un rango de fechas valido."
                                                class="w-full block"
                                                data-single-mode="true"
                                            />
                                            @error('startDate')
                                            <div class="p-1 text-red-600">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                            @error('endDate')
                                            <div class="p-1 text-red-600">
                                                {{ $message }}
                                            </div>
                                            @enderror
                                        </div>

                                    </div>


                                </div>
                            </div>
                            <div class="flex flex-col justify-end gap-3 mt-1 md:flex-row mt-4">
                                <x-base.button
                                    class="w-full rounded-[0.5rem] py-2.5 md:w-56"
                                    variant="primary"
                                    wire:click="reportCustom"
                                >
                                    <div class="px-2">
                                        <i class="fa-solid fa-download"></i>
                                    </div>
                                    Exportar
                                </x-base.button>
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
        const pickerDayli = new Litepicker({
            element: document.getElementById('datepickerDayli'),
            autoApply: true,
            singleMode: true,
        });

        const pickerCustom = new Litepicker({
            element: document.getElementById('datepickerCustom'),
            autoApply: false,
            singleMode: false,
            numberOfColumns: 2,
            numberOfMonths: 2,
            dropdowns: {
                minYear: 2020,
                maxYear: null,
                months: true,
                years: true,
            },
        });

        pickerDayli.on('selected', (startDate, endDate) => {
            @this.set('date', startDate.format('YYYY-MM-DD'));
        });


        pickerCustom.on('selected', (startDate, endDate) => {
            @this.set('startDate', startDate.format('YYYY-MM-DD'));
            @this.set('endDate', endDate.format('YYYY-MM-DD'));
        });

    });
</script>
