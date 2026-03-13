{{-- Dashboard chart rendering logic for Livewire 'dashboard-report' event --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {

        window.addEventListener('dashboard-report', event => {

            setTimeout(() => {
                createIcons({
                    icons,
                    "stroke-width": 1.5,
                    nameAttr: "data-lucide",
                });

                const data = event.detail[0];

                /**
                 * Helper: crea (o recrea) un bar chart en todos los canvas que coincidan con el selector.
                 */
                function renderBarChart(selector, labels, datasets, extraOpts = {}) {
                    const $els = $(selector);
                    if (!$els.length) return;

                    $els.each(function () {
                        const ctx = this.getContext("2d");
                        const existing = Chart.getChart(ctx);
                        if (existing) existing.destroy();

                        const chart = new Chart(ctx, {
                            type: extraOpts.type || "bar",
                            data: { labels, datasets },
                            options: {
                                maintainAspectRatio: false,
                                plugins: { legend: { display: true } },
                                scales: extraOpts.hideScales ? undefined : {
                                    x: {
                                        ticks: { color: getColor("slate.500", 0.7) },
                                        grid:  { display: false },
                                        border:{ display: false },
                                    },
                                    y: {
                                        ticks: {
                                            stepSize: extraOpts.stepSize || undefined,
                                            autoSkipPadding: 15,
                                            color: getColor("slate.500", 0.9),
                                            beginAtZero: true,
                                        },
                                        grid:  { color: getColor("slate.200", 0.7) },
                                        border:{ display: false },
                                    },
                                },
                                ...(extraOpts.chartOptions || {}),
                            },
                        });

                        if (!extraOpts.skipWatch) {
                            helper.watchCssVariables(
                                "html",
                                ["color-primary", "color-success"],
                                () => {
                                    chart.data.datasets.forEach((ds, i) => {
                                        const color = i === 0 ? "primary" : "success";
                                        ds.borderColor = getColor(color, 0.7);
                                        ds.backgroundColor = getColor(color, 0.35);
                                    });
                                    chart.update();
                                }
                            );
                        }
                    });
                }

                /**
                 * Helper: crea un par de datasets (primary + success) con config estandar.
                 */
                function dualDatasets(label1, data1, label2, data2) {
                    return [
                        {
                            label: label1,
                            categoryPercentage: 0.4, barPercentage: 0.8, borderRadius: 2,
                            data: data1, borderWidth: 1,
                            borderColor: getColor("primary", 0.7),
                            backgroundColor: getColor("primary", 0.35),
                        },
                        {
                            label: label2,
                            categoryPercentage: 0.4, barPercentage: 0.8, borderRadius: 2,
                            data: data2, borderWidth: 1,
                            borderColor: getColor("success", 0.7),
                            backgroundColor: getColor("success", 0.35),
                        },
                    ];
                }

                // Ventas semanales
                renderBarChart(
                    ".getSalesChartData",
                    data[6]['labels'],
                    dualDatasets("Ganacias S/.", data[6]['totals'], "Cantidad", data[6]['counts'])
                );

                // Top 10 productos por ganancia
                renderBarChart(
                    ".top10ProductsByProfit",
                    data[8]['labels'],
                    dualDatasets("Ganacias S/.", data[8]['profits'], "Margen %.", data[8]['percents'])
                );

                // Margen por proveedor
                renderBarChart(
                    ".margenGananciaProveedor",
                    data[0].map(item => item.provider_name),
                    dualDatasets(
                        "Ganacias S/.", data[0].map(item => item.total_ganancia),
                        "Margen %", data[0].map(item => item.margen_promedio)
                    )
                );

                // Margen por contacto
                renderBarChart(
                    ".margenGananciaContacto",
                    data[1].map(item => item.contact_name),
                    dualDatasets(
                        "Cantidad", data[1].map(item => item.total_qty),
                        "Ganancia S/", data[1].map(item => item.total)
                    )
                );

                // Margen por categoria
                renderBarChart(
                    ".margenGananciaCategoria",
                    data[2].map(item => item.category_name),
                    dualDatasets(
                        "Cantidad", data[2].map(item => item.total_qty),
                        "Ganancia S/", data[2].map(item => item.total)
                    )
                );

                // Margen por departamento
                renderBarChart(
                    ".margenGananciaDepartment",
                    data[3].map(item => item.department_name),
                    dualDatasets(
                        "Cantidad", data[3].map(item => item.total_qty),
                        "Ganancia S/", data[3].map(item => item.total)
                    ),
                    { stepSize: 10 }
                );

                // Margen por distrito
                renderBarChart(
                    ".margenGananciaDistrict",
                    data[4].map(item => item.district_name),
                    dualDatasets(
                        "Cantidad", data[4].map(item => item.total_qty),
                        "Ganancia S/", data[4].map(item => item.total)
                    ),
                    { stepSize: 10 }
                );

                // Pie chart: Ganancia vs Ventas total
                renderBarChart(
                    ".gananciaVentaTotal",
                    ['Ganancia S/.', 'Ventas S/.'],
                    [{
                        data: [data[5][0]['total_ganancias'], data[5][0]['total_ventas']],
                        borderWidth: 1,
                        borderColor: getColor("primary", 0.7),
                        backgroundColor: [getColor("primary", 0.35), getColor("success", 0.35)]
                    }],
                    {
                        type: "pie",
                        hideScales: true,
                        skipWatch: true,
                        chartOptions: {
                            plugins: { legend: { display: true } }
                        }
                    }
                );

                // Line chart: Revenue por anio y mes
                const $charRevenueAndAmount = $(".getChartRevenueAndAmount");
                if ($charRevenueAndAmount.length) {
                    $charRevenueAndAmount.each(function () {
                        const ctx = this.getContext("2d");
                        const existing = Chart.getChart(ctx);
                        if (existing) existing.destroy();

                        const rawData = data[7];
                        const meses = [
                            "enero","febrero","marzo","abril",
                            "mayo","junio","julio","agosto",
                            "septiembre","octubre","noviembre","diciembre"
                        ];
                        const anios = Object.keys(rawData)
                            .map(Number).sort((a, b) => b - a).map(String);

                        const palette = [
                            "primary","success","warning","danger","info","rose","cyan","orange"
                        ];

                        const datasets = anios.map((year, idx) => {
                            const colorName = palette[idx % palette.length];
                            return {
                                label: year,
                                data: meses.map(m => rawData[year][m] ?? 0),
                                categoryPercentage: 0.4, barPercentage: 0.8,
                                borderRadius: 2, borderWidth: 1,
                                borderColor: getColor(colorName, 0.7),
                                backgroundColor: getColor(colorName, 0.35),
                            };
                        });

                        new Chart(ctx, {
                            type: "line",
                            data: { labels: meses, datasets },
                            options: {
                                maintainAspectRatio: false,
                                plugins: { legend: { display: true } },
                                scales: {
                                    x: {
                                        ticks: { color: getColor("slate.500", 0.7) },
                                        grid:  { display: false },
                                        border:{ display: false },
                                    },
                                    y: {
                                        ticks: {
                                            stepSize: 10, autoSkipPadding: 15,
                                            color: getColor("slate.500", 0.9),
                                            beginAtZero: true,
                                        },
                                        grid:  { color: getColor("slate.200", 0.7) },
                                        border:{ display: false },
                                    },
                                },
                            },
                        });
                    });
                }

            }, 100);
        });
    });
</script>
