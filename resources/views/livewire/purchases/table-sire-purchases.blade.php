<div>
    {{-- Mientras SUNAT prepara el ticket la pantalla lo consulta sola; sin
         ticket pendiente no hay poll. --}}
    @if($pendiente)
        <div wire:poll.5s="revisarDescarga"></div>
    @endif

    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Compras SIRE
                </div>
                <div class="grid grid-cols-2 gap-2 md:ml-auto md:shrink-0 lg:grid-flow-col lg:grid-cols-none lg:auto-cols-fr lg:gap-3 [&>*:last-child:nth-child(odd)]:col-span-2 lg:[&>*:last-child:nth-child(odd)]:col-span-1">
                    <x-base.tippy
                        as="x-base.button"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        content="Traer de SUNAT las compras del periodo elegido (reemplaza lo descargado antes)"
                        wire:click="descargar"
                        wire:loading.attr="disabled"
                        wire:target="descargar"
                        :disabled="(bool) $pendiente"
                    >
                        @if($pendiente)
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                            <span class="sm:hidden">Esperando</span>
                            <span class="hidden sm:inline">Esperando a SUNAT...</span>
                        @else
                            <span wire:loading.remove wire:target="descargar">
                                <i class="fa-solid fa-cloud-arrow-down mr-1"></i>
                                <span class="sm:hidden">Descargar</span>
                                <span class="hidden sm:inline">Descargar de SUNAT</span>
                            </span>
                            <span wire:loading wire:target="descargar">
                                <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                                Pidiendo...
                            </span>
                        @endif
                    </x-base.tippy>
                    <x-base.tippy
                        as="x-base.button"
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        content="Descargar en Excel las compras del filtro actual, con una hoja de comprobantes y otra por proveedor"
                        wire:click="exportar"
                    >
                        <i class="fa-solid fa-file-excel mr-1"></i>
                        <span class="sm:hidden">Exportar</span>
                        <span class="hidden sm:inline">Exportar Excel</span>
                    </x-base.tippy>
                </div>
            </div>
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">
                    {{-- Estado del periodo elegido: de cuando es lo que se ve y si hay algo en camino --}}
                    <div class="border-b border-slate-200/60 px-5 py-3 text-sm">
                        @if($pendiente)
                            <div class="text-slate-600">
                                <i class="fa-solid fa-spinner fa-spin mr-1 text-primary"></i>
                                SUNAT está preparando {{ $periodos[$periodo] ?? $periodo }}
                                (ticket {{ $pendiente->ticket }}, pedido a las {{ $pendiente->created_at->format('H:i') }}).
                                Esta pantalla se actualiza sola; puede tardar unos minutos.
                            </div>
                        @elseif($ultima && $ultima->status === \App\Models\SireDownload::STATUS_ERROR)
                            <div class="text-amber-700">
                                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                                La última descarga ({{ $ultima->created_at->format('d-m-Y H:i') }}) falló: {{ $ultima->message }}
                            </div>
                        @elseif($ultima)
                            <div class="text-slate-600">
                                <i class="fa-solid fa-circle-check mr-1 text-success"></i>
                                Descargado de SUNAT el {{ ($ultima->finished_at ?? $ultima->created_at)->format('d-m-Y H:i') }}
                                @if($ultima->user) por {{ $ultima->user->name }} @endif
                                · {{ $ultima->total_rows }} comprobante{{ $ultima->total_rows === 1 ? '' : 's' }}
                            </div>
                        @else
                            <div class="text-slate-500">
                                <i class="fa-solid fa-circle-info mr-1"></i>
                                {{ $periodos[$periodo] ?? $periodo }} todavía no se descargó de SUNAT.
                            </div>
                        @endif
                        @unless($configurado)
                            <div class="mt-1 text-xs text-amber-700">
                                Faltan las credenciales de SIRE en el sistema: no se puede descargar hasta configurarlas.
                            </div>
                        @endunless
                        @if($delPeriodo['anteriores'] > 0)
                            <div class="mt-1 text-xs text-amber-700">
                                <i class="fa-solid fa-circle-info mr-1"></i>
                                {{ $delPeriodo['anteriores'] }} de los {{ $delPeriodo['cantidad'] }} comprobantes (S/ {{ number_format($delPeriodo['total_anteriores'], 2) }}) se emitieron en meses anteriores: SIRE los propone en este periodo porque todavía están dentro de los 12 meses en que se pueden anotar.
                            </div>
                        @endif
                        <div class="mt-1 text-xs text-slate-400">
                            Es lo que SUNAT tiene registrado como compras del RUC (facturas y notas de proveedores peruanos y declaraciones de importación), con los importes en soles. Cada mes está disponible desde el día 2 del mes siguiente. Haga clic en un comprobante para ver su detalle.
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-12 lg:grid-cols-[minmax(14rem,2fr)_minmax(14rem,1fr)_minmax(12rem,1fr)_7rem]">
                        <div class="relative md:col-span-12 lg:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                            <x-base.form-input
                                class="rounded-[0.5rem] pl-9 {{ $search ? 'pr-9' : '' }}"
                                type="text"
                                maxlength="100"
                                placeholder="RUC, proveedor o comprobante..."
                                wire:model.live="search"
                            />
                            @if($search)
                                <i
                                    class="absolute inset-y-0 right-0 z-10 my-auto mr-3.5 h-4 w-4 cursor-pointer text-slate-400 hover:text-slate-600 fa-solid fa-xmark"
                                    wire:click="$set('search', '')"
                                    title="Borrar búsqueda"
                                ></i>
                            @endif
                        </div>

                        <div class="relative md:col-span-5 lg:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-calendar-days"></i>
                            <x-base.form-select class="rounded-[0.5rem] pl-9" wire:model.live="periodo">
                                @foreach($periodos as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </x-base.form-select>
                        </div>

                        <div class="relative md:col-span-5 lg:col-span-1">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-filter"></i>
                            <x-base.form-select class="rounded-[0.5rem] pl-9" wire:model.live="tipo">
                                <option value="">Todos los tipos</option>
                                @foreach($tipos as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </x-base.form-select>
                        </div>

                        <div class="relative md:col-span-2 lg:col-span-1" title="Registros por página">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-list-ol"></i>
                            <x-base.form-select class="rounded-[0.5rem] pl-9" wire:model.live="perPage">
                                @foreach($opcionesPorPagina as $opcion)
                                    <option value="{{ $opcion }}" @selected((int) $perPage === $opcion)>{{ $opcion }}</option>
                                @endforeach
                            </x-base.form-select>
                        </div>
                    </div>

                    {{-- Vista (comprobantes o resumen por proveedor) y filtro por RUC exacto --}}
                    <div class="flex flex-wrap items-center gap-2 px-5 pb-4">
                        <div class="inline-flex rounded-md border border-slate-200 bg-slate-50 p-0.5">
                            @foreach(['comprobantes' => 'Comprobantes', 'proveedores' => 'Por proveedor'] as $valor => $etiqueta)
                                <button
                                    type="button"
                                    wire:click="$set('vista', '{{ $valor }}')"
                                    class="whitespace-nowrap rounded-md px-2 py-1.5 sm:px-3 {{ ($porProveedores ? 'proveedores' : 'comprobantes') === $valor ? 'bg-white font-medium text-primary shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                                >
                                    {{ $etiqueta }}
                                </button>
                            @endforeach
                        </div>
                        @if($delPeriodo['anteriores'] > 0 || (string) $emision !== '')
                            @php $mesPeriodo = $periodos[$periodo] ?? $periodo; @endphp
                            <div class="inline-flex rounded-md border border-slate-200 bg-slate-50 p-0.5">
                                @foreach([
                                    ''           => ['Todos', 'Todos los comprobantes del periodo'],
                                    'mes'        => ['Del mes', "Solo los emitidos en {$mesPeriodo}"],
                                    'anteriores' => ['Meses anteriores', "Solo los emitidos antes de {$mesPeriodo} que SIRE propone en este periodo"],
                                ] as $valor => [$etiqueta, $ayuda])
                                    <button
                                        type="button"
                                        wire:click="$set('emision', '{{ $valor }}')"
                                        title="{{ $ayuda }}"
                                        class="whitespace-nowrap rounded-md px-2 py-1.5 sm:px-3 {{ (string) $emision === (string) $valor ? 'bg-white font-medium text-primary shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                                    >
                                        {{ $etiqueta }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @if($nombreProveedor !== null)
                            <span class="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-3 py-1 text-xs text-primary">
                                Proveedor: {{ $nombreProveedor }}
                                @if(trim((string) $proveedor, '0') !== '' && $nombreProveedor !== (string) $proveedor)
                                    ({{ $proveedor }})
                                @endif
                                <i class="fa-solid fa-xmark cursor-pointer" wire:click="quitarProveedor" title="Quitar el filtro de proveedor"></i>
                            </span>
                        @endif
                    </div>

                    @if($porProveedores)
                        <div class="overflow-auto text-sm">
                            <x-base.table class="border-b border-slate-200/60">
                                <x-base.table.thead>
                                    <x-base.table.tr>
                                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                            Proveedor
                                        </x-base.table.td>
                                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500">
                                            Comprobantes
                                        </x-base.table.td>
                                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500">
                                            Base imponible (S/)
                                        </x-base.table.td>
                                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500">
                                            IGV (S/)
                                        </x-base.table.td>
                                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500" title="Adquisiciones no gravadas, ISC, ICBPER y otros tributos">
                                            No gravado y otros
                                        </x-base.table.td>
                                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500">
                                            Total
                                        </x-base.table.td>
                                        <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500" title="Parte del total del filtro">
                                            %
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                </x-base.table.thead>
                                <x-base.table.tbody>
                                    @forelse($compras as $fila)
                                        <x-base.table.tr
                                            class="cursor-pointer [&_td]:last:border-b-0 [&:hover_td]:bg-slate-100"
                                            wire:click="verProveedor({{ \Illuminate\Support\Js::from($fila->issuer_document_number) }})"
                                            title="Ver sus comprobantes"
                                        >
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                <div class="font-medium text-primary">{{ $fila->proveedor() ?: '—' }}</div>
                                                @if($fila->rucProveedor())
                                                    <div class="whitespace-nowrap text-xs text-slate-500">{{ $fila->rucProveedor() }}</div>
                                                @endif
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-dashed text-right dark:bg-darkmode-600">
                                                {{ $fila->cantidad }}
                                                @if((int) $fila->notas_credito > 0)
                                                    <div class="text-xs text-red-700">{{ $fila->notas_credito }} nota{{ (int) $fila->notas_credito === 1 ? '' : 's' }} de crédito</div>
                                                @endif
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-dashed text-right dark:bg-darkmode-600">
                                                {{ number_format((float) $fila->taxable_base, 2) }}
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-dashed text-right dark:bg-darkmode-600">
                                                {{ number_format((float) $fila->igv, 2) }}
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-dashed text-right dark:bg-darkmode-600">
                                                {{ number_format((float) $fila->otros, 2) }}
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-dashed text-right font-medium dark:bg-darkmode-600">
                                                S/ {{ number_format((float) $fila->total, 2) }}
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-dashed text-right text-slate-500 dark:bg-darkmode-600">
                                                {{ $resumen['total'] != 0 ? number_format((float) $fila->total / $resumen['total'] * 100, 1) . ' %' : '—' }}
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    @empty
                                        <x-base.table.tr>
                                            <x-base.table.td colspan="7" class="border-dashed text-center dark:bg-darkmode-600">
                                                @if($ultima && $ultima->status === \App\Models\SireDownload::STATUS_DONE && ! $search && ! $tipo && (string) $proveedor === '' && (string) $emision === '')
                                                    SUNAT no tiene compras registradas para este periodo.
                                                @else
                                                    No se encontraron resultados.
                                                @endif
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    @endforelse
                                </x-base.table.tbody>
                                @if($resumen['cantidad'] > 0)
                                    <tfoot>
                                        <x-base.table.tr>
                                            <x-base.table.td colspan="2" class="border-t border-slate-200/60 bg-slate-100 text-right font-bold text-slate-700">
                                                Total ({{ $resumen['proveedores'] }} proveedor{{ $resumen['proveedores'] === 1 ? '' : 'es' }} · {{ $resumen['cantidad'] }} comprobante{{ $resumen['cantidad'] === 1 ? '' : 's' }})
                                                @if($resumen['anteriores'] > 0 && $resumen['anteriores'] < $resumen['cantidad'])
                                                    <div class="text-xs font-medium text-slate-500">
                                                        Del mes: S/ {{ number_format($resumen['total_mes'], 2) }} · Meses anteriores: S/ {{ number_format($resumen['total_anteriores'], 2) }} ({{ $resumen['anteriores'] }})
                                                    </div>
                                                @endif
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-t border-slate-200/60 bg-slate-100 text-right font-bold text-slate-700">
                                                {{ number_format($resumen['base'], 2) }}
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-t border-slate-200/60 bg-slate-100 text-right font-bold text-slate-700">
                                                {{ number_format($resumen['igv'], 2) }}
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-t border-slate-200/60 bg-slate-100 text-right font-bold text-slate-700">
                                                {{ number_format($resumen['otros'], 2) }}
                                            </x-base.table.td>
                                            <x-base.table.td class="whitespace-nowrap border-t border-slate-200/60 bg-slate-100 text-right text-base font-bold text-slate-700">
                                                S/ {{ number_format($resumen['total'], 2) }}
                                            </x-base.table.td>
                                            <x-base.table.td class="border-t border-slate-200/60 bg-slate-100"></x-base.table.td>
                                        </x-base.table.tr>
                                    </tfoot>
                                @endif
                            </x-base.table>
                        </div>
                    @else
                    <div class="overflow-auto text-sm">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Fecha
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Comprobante
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Proveedor
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">
                                        Moneda
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500">
                                        Base imponible (S/)
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500">
                                        IGV (S/)
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500" title="Adquisiciones no gravadas, ISC, ICBPER y otros tributos">
                                        No gravado y otros
                                    </x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500">
                                        Total
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @forelse($compras as $compra)
                                    @php $esNota = in_array($compra->document_type, ['07', '08'], true); @endphp
                                    <x-base.table.tr
                                        class="cursor-pointer [&_td]:last:border-b-0 {{ $compra->document_type === '07' ? '[&_td]:bg-red-50' : '[&:hover_td]:bg-slate-100' }}"
                                        wire:click="verDetalle({{ $compra->id }})"
                                        title="Ver el detalle"
                                    >
                                        <x-base.table.td class="whitespace-nowrap border-dashed dark:bg-darkmode-600">
                                            {{ $compra->issue_date?->format('d-m-Y') ?? '—' }}
                                            @if($compra->issue_date && $compra->issue_date->format('Ym') < $compra->period)
                                                <div class="text-xs text-amber-700" title="Emitido antes del periodo: SIRE lo propone aquí porque todavía se puede anotar">mes anterior</div>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            <div class="whitespace-nowrap font-medium text-primary">{{ $compra->comprobante() }}</div>
                                            <div class="whitespace-nowrap text-xs {{ $compra->document_type === '07' ? 'text-red-700' : 'text-slate-500' }}">
                                                {{ $compra->tipoNombre() }}
                                                @if($esNota && $compra->affected_document)
                                                    · modifica {{ $compra->affected_document }}
                                                @endif
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            <div>{{ $compra->proveedor() ?: '—' }}</div>
                                            @if($compra->rucProveedor())
                                                <div class="whitespace-nowrap text-xs text-slate-500">{{ $compra->rucProveedor() }}</div>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="whitespace-nowrap border-dashed dark:bg-darkmode-600">
                                            {{-- Informativo: los importes ya vienen en soles --}}
                                            {{ $compra->currency }}
                                            @if($compra->currency !== 'PEN' && $compra->exchange_rate)
                                                <div class="text-xs text-slate-500">T.C. {{ number_format((float) $compra->exchange_rate, 3) }}</div>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="whitespace-nowrap border-dashed text-right dark:bg-darkmode-600">
                                            {{ number_format((float) $compra->taxable_base, 2) }}
                                        </x-base.table.td>
                                        <x-base.table.td class="whitespace-nowrap border-dashed text-right dark:bg-darkmode-600">
                                            {{ number_format((float) $compra->igv, 2) }}
                                        </x-base.table.td>
                                        <x-base.table.td class="whitespace-nowrap border-dashed text-right dark:bg-darkmode-600">
                                            {{ number_format((float) $compra->non_taxable + (float) $compra->other_taxes, 2) }}
                                        </x-base.table.td>
                                        <x-base.table.td class="whitespace-nowrap border-dashed text-right font-medium dark:bg-darkmode-600">
                                            S/ {{ number_format((float) $compra->total, 2) }}
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @empty
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="8" class="border-dashed text-center dark:bg-darkmode-600">
                                            @if($ultima && $ultima->status === \App\Models\SireDownload::STATUS_DONE && ! $search && ! $tipo && (string) $proveedor === '' && (string) $emision === '')
                                                SUNAT no tiene compras registradas para este periodo.
                                            @else
                                                No se encontraron resultados.
                                            @endif
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforelse
                            </x-base.table.tbody>
                            @if($resumen['cantidad'] > 0)
                                <tfoot>
                                    {{-- Suma TODO el filtro, no solo la pagina. Las notas de
                                         credito vienen en negativo y restan. --}}
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="4" class="border-t border-slate-200/60 bg-slate-100 text-right font-bold text-slate-700">
                                            Total ({{ $resumen['cantidad'] }} comprobante{{ $resumen['cantidad'] === 1 ? '' : 's' }})
                                            @if($resumen['en_otra_moneda'] > 0)
                                                <div class="text-xs font-medium text-slate-500">
                                                    {{ $resumen['en_otra_moneda'] }} en moneda extranjera, convertido{{ $resumen['en_otra_moneda'] === 1 ? '' : 's' }} a soles por SUNAT
                                                </div>
                                            @endif
                                            @if($resumen['anteriores'] > 0 && $resumen['anteriores'] < $resumen['cantidad'])
                                                <div class="text-xs font-medium text-slate-500">
                                                    Del mes: S/ {{ number_format($resumen['total_mes'], 2) }} · Meses anteriores: S/ {{ number_format($resumen['total_anteriores'], 2) }} ({{ $resumen['anteriores'] }})
                                                </div>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="whitespace-nowrap border-t border-slate-200/60 bg-slate-100 text-right font-bold text-slate-700">
                                            {{ number_format($resumen['base'], 2) }}
                                        </x-base.table.td>
                                        <x-base.table.td class="whitespace-nowrap border-t border-slate-200/60 bg-slate-100 text-right font-bold text-slate-700">
                                            {{ number_format($resumen['igv'], 2) }}
                                        </x-base.table.td>
                                        <x-base.table.td class="whitespace-nowrap border-t border-slate-200/60 bg-slate-100 text-right font-bold text-slate-700">
                                            {{ number_format($resumen['otros'], 2) }}
                                        </x-base.table.td>
                                        <x-base.table.td class="whitespace-nowrap border-t border-slate-200/60 bg-slate-100 text-right text-base font-bold text-slate-700">
                                            S/ {{ number_format($resumen['total'], 2) }}
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                </tfoot>
                            @endif
                        </x-base.table>
                    </div>
                    @endif
                    <div class="m-4">
                        {{ $compras->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de detalle: todo lo que SUNAT manda del comprobante --}}
    <div
        x-data="{ show: false }"
        x-on:open-sire-detalle-modal.window="show = true"
        x-on:close-sire-detalle-modal.window="show = false"
        x-on:keydown.escape.window="if (show) { show = false }"
    >
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 flex items-center justify-center"
            style="z-index: 1050; display: none;"
        >
            <div class="fixed inset-0 bg-black/50" x-on:click="show = false"></div>
            <div
                class="relative bg-white dark:bg-darkmode-600 rounded-lg shadow-xl w-[760px] max-w-[95vw] max-h-[95vh] overflow-y-auto p-6"
                style="z-index: 1051;"
                x-on:click.stop
            >
                @if($detalle)
                    @php
                        $esNotaDetalle = in_array($detalle->document_type, ['07', '08'], true);
                        $desglose = $detalle->desglose();
                        $enOriginal = $detalle->totalEnMonedaOriginal();
                        $otrosImportes = array_filter([
                            'No gravado'               => (float) $detalle->non_taxable,
                            'ISC'                      => (float) $detalle->isc,
                            'ICBPER'                   => (float) $detalle->icbper,
                            'Otros tributos y cargos'  => (float) $detalle->other_charges,
                        ], fn ($monto) => $monto != 0);
                        // Filas descargadas antes de guardar ISC/ICBPER por separado.
                        if ((float) $detalle->other_taxes != 0 && (float) $detalle->isc == 0 && (float) $detalle->icbper == 0 && (float) $detalle->other_charges == 0) {
                            $otrosImportes['Otros tributos'] = (float) $detalle->other_taxes;
                        }
                    @endphp
                    <div class="mb-4 flex justify-between gap-3">
                        <div>
                            <h3 class="text-base font-medium {{ $detalle->document_type === '07' ? 'text-red-700' : '' }}">
                                {{ $detalle->tipoNombre() }} {{ $detalle->comprobante() }}
                            </h3>
                            <div class="text-sm text-slate-500">{{ $detalle->proveedor() ?: '—' }}</div>
                        </div>
                        <button x-on:click="show = false" class="text-slate-400 hover:text-slate-600" title="Cerrar">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                        <div>
                            <div class="text-xs text-slate-500">Fecha de emisión</div>
                            <div class="font-medium">{{ $detalle->issue_date?->format('d-m-Y') ?? '—' }}</div>
                            @if($detalle->issue_date && $detalle->issue_date->format('Ym') < $detalle->period)
                                <div class="text-xs text-amber-700">
                                    De un mes anterior: SIRE lo propone en {{ \App\Livewire\Purchases\TableSirePurchases::etiquetaPeriodo($detalle->period) }} porque todavía está dentro de los 12 meses en que se puede anotar.
                                </div>
                            @endif
                        </div>
                        @if($detalle->due_date)
                            <div>
                                <div class="text-xs text-slate-500">Vencimiento o pago</div>
                                <div class="font-medium">{{ $detalle->due_date->format('d-m-Y') }}</div>
                            </div>
                        @endif
                        <div>
                            <div class="text-xs text-slate-500">Proveedor</div>
                            <div class="font-medium">{{ $detalle->proveedor() ?: '—' }}</div>
                            @if($detalle->rucProveedor())
                                <div class="text-xs text-slate-500">{{ $detalle->documentoIdentidad() ?? 'Documento' }} {{ $detalle->rucProveedor() }}</div>
                            @endif
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Moneda</div>
                            <div class="font-medium">
                                {{ $detalle->currency === 'PEN' ? 'Soles' : ($detalle->currency === 'USD' ? 'Dólares' : $detalle->currency) }}
                                @if($detalle->currency !== 'PEN' && $detalle->exchange_rate)
                                    <span class="font-normal text-slate-500">· T.C. {{ number_format((float) $detalle->exchange_rate, 3) }}</span>
                                @endif
                            </div>
                        </div>
                        @if($detalle->dam_year)
                            <div>
                                <div class="text-xs text-slate-500">Año de la declaración</div>
                                <div class="font-medium">{{ $detalle->dam_year }}</div>
                            </div>
                        @endif
                        @if($esNotaDetalle && $detalle->affected_document)
                            <div>
                                <div class="text-xs text-slate-500">Comprobante que modifica</div>
                                <div class="font-medium">
                                    {{ $detalle->affected_document }}
                                    @if($detalle->affected_issue_date)
                                        <span class="font-normal text-slate-500">· {{ $detalle->affected_issue_date->format('d-m-Y') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($esNotaDetalle && $detalle->motivoNota())
                            <div>
                                <div class="text-xs text-slate-500">Motivo de la nota</div>
                                <div class="font-medium">{{ $detalle->motivoNota() }}</div>
                            </div>
                        @endif
                        @if($detalle->detraction)
                            <div>
                                <div class="text-xs text-slate-500">Detracción</div>
                                <div class="font-medium text-amber-700">Sujeto a detracción</div>
                            </div>
                        @endif
                        @if($detalle->car)
                            <div class="sm:col-span-2">
                                <div class="text-xs text-slate-500">CAR SUNAT</div>
                                <div class="font-medium" style="word-break: break-all;">{{ $detalle->car }}</div>
                            </div>
                        @endif
                    </div>

                    {{-- Importes en soles, como los manda SUNAT --}}
                    <div class="mt-5 rounded-md border border-slate-200/60 text-sm">
                        @foreach($desglose as $linea)
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 px-4 py-2">
                                <div>
                                    Base imponible
                                    <div class="text-xs text-slate-500">Destino: {{ mb_strtolower($linea['destino'], 'UTF-8') }}</div>
                                </div>
                                <div class="whitespace-nowrap">{{ number_format($linea['base'], 2) }}</div>
                            </div>
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 px-4 py-2">
                                <div>
                                    IGV
                                    <div class="text-xs text-slate-500">Destino: {{ mb_strtolower($linea['destino'], 'UTF-8') }}</div>
                                </div>
                                <div class="whitespace-nowrap">{{ number_format($linea['igv'], 2) }}</div>
                            </div>
                        @endforeach
                        @if($desglose === [] && ((float) $detalle->taxable_base != 0 || (float) $detalle->igv != 0))
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 px-4 py-2">
                                <div>Base imponible</div>
                                <div class="whitespace-nowrap">{{ number_format((float) $detalle->taxable_base, 2) }}</div>
                            </div>
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 px-4 py-2">
                                <div>IGV</div>
                                <div class="whitespace-nowrap">{{ number_format((float) $detalle->igv, 2) }}</div>
                            </div>
                        @endif
                        @foreach($otrosImportes as $concepto => $monto)
                            <div class="flex justify-between gap-3 border-b border-slate-200/60 px-4 py-2">
                                <div>{{ $concepto }}</div>
                                <div class="whitespace-nowrap">{{ number_format($monto, 2) }}</div>
                            </div>
                        @endforeach
                        <div class="flex justify-between gap-3 bg-slate-50 px-4 py-3">
                            <div class="font-medium">Total</div>
                            <div class="text-right">
                                <div class="whitespace-nowrap text-base font-bold">S/ {{ number_format((float) $detalle->total, 2) }}</div>
                                @if($enOriginal !== null)
                                    <div class="whitespace-nowrap text-xs text-slate-500">
                                        ≈ {{ $detalle->currency === 'USD' ? 'US$' : $detalle->currency }} {{ number_format($enOriginal, 2) }} al T.C. {{ number_format((float) $detalle->exchange_rate, 3) }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 justify-between sm:flex-row sm:items-center">
                        <div class="text-xs text-slate-400">
                            Fuente: propuesta del Registro de Compras de SIRE
                            @if($detalle->download)
                                descargada el {{ ($detalle->download->finished_at ?? $detalle->download->created_at)->format('d-m-Y H:i') }}
                                @if($detalle->download->user) por {{ $detalle->download->user->name }} @endif
                            @endif
                        </div>
                        @if((string) $proveedor !== (string) $detalle->issuer_document_number)
                            <x-base.button variant="outline-secondary" size="sm" class="shrink-0" wire:click="verProveedor({{ \Illuminate\Support\Js::from($detalle->issuer_document_number) }})">
                                Ver todas sus compras del mes
                            </x-base.button>
                        @endif
                    </div>
                @else
                    <div class="flex justify-between gap-3">
                        <div class="text-sm text-slate-500">Este comprobante ya no está: el periodo se volvió a descargar de SUNAT.</div>
                        <button x-on:click="show = false" class="text-slate-400 hover:text-slate-600" title="Cerrar">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
