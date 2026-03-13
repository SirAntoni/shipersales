<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Dashboard
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <div class="relative">
                        <x-base.lucide
                            class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] group-[.mode--light]:!text-slate-200"
                            icon="Users"
                        />

                        <x-base.form-select
                            wire:model.live="provider"
                            class="rounded-[0.5rem] pl-9 group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:bg-chevron-white group-[.mode--light]:!text-slate-200 sm:w-44"
                        >
                            <option value="">Proveedor</option>
                            @foreach($providers as $provider)
                                <option class="text-black" value="{{$provider->id}}">{{$provider->name}}</option>
                            @endforeach
                        </x-base.form-select>
                    </div>
                    <div class="relative">
                        <x-base.lucide
                            class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] group-[.mode--light]:!text-slate-200"
                            icon="List"
                        />
                        <x-base.form-select
                            wire:model.live="category"
                            class="rounded-[0.5rem] pl-9 group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:bg-chevron-white group-[.mode--light]:!text-slate-200 sm:w-44"
                        >
                            <option value="custom-date">Categoría</option>
                            @foreach($categories as $category)
                                <option class="text-black" value="{{$category->id}}">{{$category->name}}</option>
                            @endforeach
                        </x-base.form-select>
                    </div>
                    <div class="relative">
                        <x-base.lucide

                            class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] group-[.mode--light]:!text-slate-200"
                            icon="CalendarCheck2"
                        />
                        <x-base.form-select
                            wire:model.live="month"
                            class="rounded-[0.5rem] pl-9 group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:bg-chevron-white group-[.mode--light]:!text-slate-200 sm:w-44"
                        >
                            <option class="text-black" value="">Todos los meses</option>
                            <option class="text-black" value="01">Enero</option>
                            <option class="text-black" value="02">Febrero</option>
                            <option class="text-black" value="03">Marzo</option>
                            <option class="text-black" value="04">Abril</option>
                            <option class="text-black" value="05">Mayo</option>
                            <option class="text-black" value="06">Junio</option>
                            <option class="text-black" value="07">Julio</option>
                            <option class="text-black" value="08">Agosto</option>
                            <option class="text-black" value="09">Setiembre</option>
                            <option class="text-black" value="10">Octubre</option>
                            <option class="text-black" value="11">Noviembre</option>
                            <option class="text-black" value="12">Diciembre</option>
                        </x-base.form-select>
                    </div>
                    <div class="relative">
                        <x-base.lucide

                            class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] group-[.mode--light]:!text-slate-200"
                            icon="CalendarCheck2"
                        />
                        <x-base.form-select
                            wire:model.live="year"
                            class="rounded-[0.5rem] pl-9 group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:bg-chevron-white group-[.mode--light]:!text-slate-200 sm:w-44"
                        >
                            <option class="text-black" value="">Año</option>
                            <option class="text-black" value="2025">2025</option>
                            <option class="text-black" value="2024">2024</option>
                            <option class="text-black" alue="2023">2023</option>
                            <option class="text-black" value="2022">2022</option>
                            <option class="text-black" value="2021">2021</option>
                        </x-base.form-select>
                    </div>
                    <div class="relative">
                        <x-base.lucide
                            class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] group-[.mode--light]:!text-slate-200"
                            icon="Navigation2"
                        />
                        <x-base.form-select
                            wire:model.live="department"
                            class="rounded-[0.5rem] pl-9 group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:bg-chevron-white group-[.mode--light]:!text-slate-200 sm:w-44"
                        >
                            <option value="custom-date">Departamento</option>
                            @foreach($departments as $department)
                                <option class="text-black" value="{{$department->id}}">{{$department->name}}</option>
                            @endforeach
                        </x-base.form-select>
                    </div>
                    <div class="relative">
                        <x-base.lucide
                            class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] group-[.mode--light]:!text-slate-200"
                            icon="Navigation"
                        />
                        <x-base.form-select
                            wire:model.live="district"
                            class="rounded-[0.5rem] pl-9 group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:bg-chevron-white group-[.mode--light]:!text-slate-200 sm:w-44"
                        >
                            <option value="custom-date">Distrito</option>
                            @if($districts != null)
                                @foreach($districts as $district)
                                    <option class="text-black" value="{{$district->id}}">{{html_entity_decode($district->name)}}</option>
                                @endforeach
                            @endif
                        </x-base.form-select>
                    </div>

                </div>

            </div>


        </div>


        <div class="col-span-12 sm:colspan-12">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium group-[.mode--light]:text-white">Ganancia y cantidad de ventas últimos 7 días
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="getSalesChartData" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-span-12 sm:colspan-12">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium ">Top 10 productos por Ganancia
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="top10ProductsByProfit" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-span-12 sm:colspan-12">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium ">Ganancias por año
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="flex justify-end" >
                        <x-base.form-select
                        class="w-32"
                        wire:ignore
                        wire:model.live="filterChart"
                        >
                            <option value="Ganancia">Ganancia</option>
                            <option value="Cantidad">Cantidad</option>
                        </x-base.form-select>
                    </div>

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="getChartRevenueAndAmount" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Ganancia y margen según proveedor.
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="margenGananciaProveedor" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Ganancia y venta total.
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="gananciaVentaTotal" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>
        <div class="col-span-12">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Top 10 productos más vendidos
                    </div>

                </div>
                <div class="box box--stacked mt-3.5">
                    <div class="overflow-auto xl:overflow-visible">
                        <x-base.table>
                            <x-base.table.thead>
                                <x-base.table.tr>

                                    <x-base.table.td
                                        class="w-56 border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]"
                                    >
                                        Producto
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="truncate border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem] text-center"
                                    >
                                        Cantidad
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="w-32 truncate border-slate-200/80 bg-slate-50 py-5 text-right font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem] text-center"
                                    >
                                        Ganancia
                                    </x-base.table.td>

                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>

                                @if($top10Products->count() > 0)

                                    @foreach($top10Products as $product)

                                        <x-base.table.tr class="[&_td]:last:border-b-0">

                                            <x-base.table.td
                                                class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600"
                                            >

                                                <div class="ml-1.5 whitespace-nowrap">
                                                    {{$product->title}}
                                                </div>

                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                            >
                                                <div class="ml-1.5 whitespace-nowrap">
                                                    {{$product->total_qty}}
                                                </div>
                                            </x-base.table.td>
                                            <x-base.table.td
                                                class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 text-right first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                            >

                                                <div class="ml-1.5 whitespace-nowrap">
                                                  S/.  {{ number_format($product->total, 2) }}
                                                </div>

                                            </x-base.table.td>

                                        </x-base.table.tr>

                                    @endforeach

                                @else
                                    <x-base.table.tr class="[&_td]:last:border-b-0">

                                        <x-base.table.td
                                            colspan="3"
                                            class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                        >

                                            No se encontrarón resultados.

                                        </x-base.table.td>


                                    </x-base.table.tr>

                                @endif


                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                </div>

            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Ganancia y cantidad según Categoría
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="margenGananciaCategoria" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-6 2xl:col-span-6">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium">Ganancia y cantidad según Departamento
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="margenGananciaDepartment" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium group-[.mode--light]:text-white">Ganancia y cantidad según distritos
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="margenGananciaDistrict" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-span-12 sm:colspan-12">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium group-[.mode--light]:text-white">Ganancia y margen según contacto.
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="margenGananciaContacto" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>



    </div>
</div>

@include('partials.dashboard-charts')
