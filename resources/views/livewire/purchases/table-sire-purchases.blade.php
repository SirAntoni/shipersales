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
                        content="Descargar en Excel las compras del filtro actual"
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
                        <div class="mt-1 text-xs text-slate-400">
                            Es lo que SUNAT tiene registrado como compras del RUC (facturas y notas emitidas por proveedores peruanos), con los importes en soles. Cada mes está disponible desde el día 2 del mes siguiente.
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
                                    <x-base.table.tr class="[&_td]:last:border-b-0 {{ $compra->document_type === '07' ? '[&_td]:bg-red-50' : '' }}">
                                        <x-base.table.td class="whitespace-nowrap border-dashed dark:bg-darkmode-600">
                                            {{ $compra->issue_date?->format('d-m-Y') ?? '—' }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            <div class="whitespace-nowrap font-medium">{{ $compra->comprobante() }}</div>
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
                                            @if($ultima && $ultima->status === \App\Models\SireDownload::STATUS_DONE && ! $search && ! $tipo)
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
                    <div class="m-4">
                        {{ $compras->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
