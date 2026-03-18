<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Dashboard de Inventario
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <div class="relative">
                        <x-base.lucide
                            class="absolute inset-y-0 left-0 z-10 my-auto ml-3 h-4 w-4 stroke-[1.3] group-[.mode--light]:!text-slate-200"
                            icon="Users"
                        />
                        <x-base.form-select
                            wire:model.live="userId"
                            class="rounded-[0.5rem] pl-9 group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:bg-chevron-white group-[.mode--light]:!text-slate-200 sm:w-44"
                        >
                            <option class="text-black" value="">Todos los usuarios</option>
                            @foreach($users as $u)
                                <option class="text-black" value="{{ $u->id }}">{{ $u->name }}</option>
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
                            <option class="text-black" value="">Mes</option>
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
                            @foreach(range(date('Y'), 2020) as $y)
                                <option class="text-black" value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </x-base.form-select>
                    </div>

                </div>

            </div>


        </div>

        <div class="col-span-12 sm:colspan-12">
            <div>
                <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                    <div class="text-base font-medium group-[.mode--light]:text-white">Promedio de llenado de inventario (Seg) - Usuarios
                    </div>

                </div>
                <div class="box box--stacked mt-3.5 p-5">

                    <div class="mb-1 mt-10">
                        <x-report-bar-chart-5 classReport="topUsersInventory" height="h-[400px]"/>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-span-12 sm:colspan-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium">
                    Sesiones de inventario ({{ $month }}/{{ $year }})
                </div>
            </div>

            <div class="box box--stacked mt-3.5">
                <div class="overflow-auto">
                    <x-base.table class="border-b border-slate-200/60 text-sm">
                        <x-base.table.thead>
                            <x-base.table.tr>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Usuario</x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Fecha (count_date)</x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Segundos</x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Inicio</x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Fin</x-base.table.td>
                                <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Nota</x-base.table.td>
                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>
                            @forelse($userSessions as $s)
                                <x-base.table.tr class="[&_td]:last:border-b-0">
                                    <x-base.table.td class="border-dashed dark:bg-darkmode-600 ">
                                        {{ $s->user_name }}
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                        {{ \Carbon\Carbon::parse($s->count_date)->format('Y-m-d') }}
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                        {{ (int)$s->duration_sec }}
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                        {{ $s->started_at ? \Carbon\Carbon::parse($s->started_at)->format('Y-m-d H:i:s') : '—' }}
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                        {{ $s->finished_at ? \Carbon\Carbon::parse($s->finished_at)->format('Y-m-d H:i:s') : '—' }}
                                    </x-base.table.td>
                                    <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                        {{ $s->note ?? '—' }}
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @empty
                                <x-base.table.tr>
                                    <x-base.table.td colspan="6" class="text-center border-dashed dark:bg-darkmode-600">
                                        No hay información para los filtros seleccionados.
                                    </x-base.table.td>
                                </x-base.table.tr>
                            @endforelse
                        </x-base.table.tbody>
                    </x-base.table>
                </div>
            </div>
        </div>



    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        window.addEventListener('dashboard-report', event => {

            setTimeout(() => {
                createIcons({
                    icons,
                    "stroke-width": 1.5,
                    nameAttr: "data-lucide",
                });
                const $topUsersInventory = $(".topUsersInventory");
                if ($topUsersInventory.length) {
                    $topUsersInventory.each(function () {
                        const ctx = this.getContext("2d");

                        console.log(event.detail[0][9]);
                        // Verifica si ya hay un gráfico asociado al canvas
                        const existingChart = Chart.getChart(ctx);
                        if (existingChart) {
                            console.log("Destruyendo gráfico existente...");
                            existingChart.destroy();
                        }

                        // Ahora creamos el nuevo gráfico
                        const newChart = new Chart(ctx, {
                            type: "bar",
                            data: {
                                labels:event.detail[0][0]['labels'],
                                datasets: [
                                    {
                                        label: "Promedio (Seg)",
                                        categoryPercentage: 0.4,
                                        barPercentage: 0.8,
                                        borderRadius: 2,
                                        data: event.detail[0][0]['times'],
                                        borderWidth: 1,
                                        borderColor: getColor("primary", 0.7),
                                        backgroundColor: getColor("primary", 0.35),
                                    }
                                ],
                            },
                            options: {
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                    },
                                },
                                scales: {
                                    x: {
                                        ticks: {
                                            color: getColor("slate.500", 0.7),
                                        },
                                        grid: {
                                            display: false,
                                        },
                                        border: {
                                            display: false,
                                        },
                                    },
                                    y: {
                                        ticks: {
                                            autoSkipPadding: 15,
                                            color: getColor("slate.500", 0.9),
                                            beginAtZero: true,
                                        },
                                        grid: {
                                            color: getColor("slate.200", 0.7),
                                        },
                                        border: {
                                            display: false,
                                        },
                                    },
                                },
                            },
                        });

                        // Opcional: Vigilar cambios en las variables CSS para actualizar los colores del gráfico
                        helper.watchCssVariables(
                            "html",
                            ["color-primary", "color-success"],
                            (newValues) => {
                                newChart.data.datasets[0].borderColor = getColor("primary", 0.7);
                                newChart.data.datasets[0].backgroundColor = getColor("primary", 0.35);
                                newChart.update();
                            }
                        );
                    });
                }

            }, 100);
        });
    });


</script>
