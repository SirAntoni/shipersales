<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Documentos Electrónicos
                </div>
            </div>
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center justify-end">
                        <div>

                                <x-base.tippy
                                    as="x-base.button"
                                    class="mr-2"
                                    variant="primary"
                                    content="Reenviar a SUNAT todos los documentos pendientes"
                                    wire:click="resendPending"
                                    wire:loading.attr="disabled"
                                    wire:target="resendPending"
                                >
                <span wire:loading.remove wire:target="resendPending">
                    <i class="fa-solid fa-cloud-arrow-up mr-1"></i>
                    Enviar a SUNAT (masivo)
                </span>

                                    <span wire:loading wire:target="resendPending">
                    <i class="fa-solid fa-spinner fa-spin mr-1"></i>
                    Enviando...
                </span>
                                </x-base.tippy>

                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <x-base.tippy
                                as="x-base.button"
                                variant="outline-primary"
                                content="Descargar en Excel los comprobantes del filtro actual"
                                wire:click="exportar"
                            >
                                <i class="fa-solid fa-file-excel mr-1"></i>
                                Exportar Excel
                            </x-base.tippy>

                            <x-base.form-select
                                class="rounded-[0.5rem] sm:w-32"
                                wire:model.live="anio"
                            >
                                <option value="">Todos los años</option>
                                @foreach($anios as $unAnio)
                                    <option value="{{ $unAnio }}">{{ $unAnio }}</option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-select
                                class="rounded-[0.5rem] sm:w-36"
                                wire:model.live="mes"
                            >
                                <option value="">Todos los meses</option>
                                @foreach($meses as $numero => $nombre)
                                    <option value="{{ $numero }}">{{ $nombre }}</option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-select
                                class="rounded-[0.5rem] sm:w-44"
                                wire:model.live="tipo"
                            >
                                <option value="">Todos los tipos</option>
                                @foreach($tipos as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </x-base.form-select>

                            <x-base.form-select
                                class="rounded-[0.5rem] sm:w-44"
                                wire:model.live="statusSunat"
                            >
                                <option value="">Todos los estados</option>
                                <option value="aceptado">Aceptado</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="rechazado">Rechazado</option>
                                <option value="anulado">Anulado</option>
                            </x-base.form-select>

                            <div class="relative">
                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-arrow-up-1-9"></i>
                                <x-base.form-select
                                    class="rounded-[0.5rem] pl-9 sm:w-44"
                                    wire:model.live="perPage"
                                >
                                    @foreach($opcionesPorPagina as $opcion)
                                        <option value="{{ $opcion }}" @selected((int) $perPage === $opcion)>{{ $opcion }} por página</option>
                                    @endforeach
                                </x-base.form-select>
                            </div>

                            <div class="relative mr-2">
                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                                <x-base.form-input
                                    class="rounded-[0.5rem] pl-9 sm:w-64"
                                    type="text"
                                    maxlength="100"
                                    placeholder="Buscar comprobante..."
                                    wire:model.live="search"
                                />
                            </div>

                            @if($search || $statusSunat || $anio || $mes || $tipo)
                                <x-base.tippy
                                    as="x-base.button"
                                    variant="outline-secondary"
                                    content="Quitar todos los filtros"
                                    wire:click="limpiarFiltros"
                                >
                                    <i class="fa-solid fa-filter-circle-xmark"></i>
                                </x-base.tippy>
                            @endif
                        </div>
                    </div>
                    <div class="overflow-auto xl:overflow-visible text-sm">
                        <x-base.table class="border-b border-slate-200/60 ">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Fecha
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Comprobante
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Tipo
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        N° Orden
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Contacto
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Cliente
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Monto
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        PDF
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        XML
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        CDR
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500"
                                    >
                                        Sunat
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="w-36 border-t border-slate-200/60 bg-slate-50 text-center font-medium text-slate-500"
                                    >
                                        Action
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @if($documents->count() > 0 )
                                    @foreach ($documents as $document)
                                        @php
                                            // El criterio de "es nota de credito" vive en el modelo
                                            // (por serie, no por document_type).
                                            $esNotaCredito = $document->esNotaCredito();
                                            $afectado      = $esNotaCredito ? $document->affectedDocument : null;
                                            // Nota de credito que anula ESTE comprobante. Se lee de la
                                            // relacion y no de status, porque al anular un comprobante
                                            // el status 'nota_credito' se pierde.
                                            $notaCredito   = $esNotaCredito ? null : $document->creditNotes->last();
                                            $anuladoPorNc  = $notaCredito !== null;
                                            // Saldo tras la nota de credito. documents.total es DOUBLE:
                                            // se redondea antes de restar y se trata < 1 centimo como cero.
                                            $saldo = $anuladoPorNc
                                                ? round(round((float) $document->total, 2) - $document->creditNotes->sum(fn($nc) => round((float) $nc->total, 2)), 2)
                                                : null;
                                            // La otra via de anulacion: baja comunicada a SUNAT (RA/RC).
                                            $baja = $document->bajaSunat();
                                            $anuladoSinConstancia = $document->status === 'anulado' && ! $baja;
                                        @endphp
                                        <x-base.table.tr class="[&_td]:last:border-b-0 {{ $esNotaCredito ? '[&_td]:bg-red-50' : '' }}">
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600 text">
                                                {{-- Fecha de emision: es la que va en el XML de SUNAT y la
                                                     que usan los filtros. La de registro se muestra debajo
                                                     porque en 588 documentos no coinciden. --}}
                                                <div class="whitespace-nowrap">
                                                    {{ $document->date ? \Carbon\Carbon::parse($document->date)->format('d-m-Y') : '—' }}
                                                </div>
                                                <div class="whitespace-nowrap text-xs text-slate-400">
                                                    Registrado {{ $document->created_at->format('d-m-Y H:i') }}
                                                </div>
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                @if($esNotaCredito)
                                                    <span
                                                        class="bg-red-100 text-red-700 text-xs font-medium me-2 px-2.5 p-1 rounded-full whitespace-nowrap">NC {{ $document->serie}}-{{$document->correlative}}</span>
                                                    <div class="mt-1 whitespace-nowrap text-xs text-slate-500">
                                                        @if($afectado)
                                                            anula
                                                            <a href="javascript:;"
                                                               class="underline decoration-dotted hover:text-primary"
                                                               title="Buscar {{ $afectado->serie }}-{{ $afectado->correlative }}"
                                                               wire:click="verComprobante(@js($afectado->serie.'-'.$afectado->correlative))">{{ $afectado->serie }}-{{ $afectado->correlative }}</a>
                                                        @else
                                                            <span class="text-amber-700">sin comprobante afectado</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span
                                                        class="bg-blue-100 text-white-800 text-xs font-medium me-2 px-2.5 p-1 rounded-full whitespace-nowrap">{{ $document->serie}}-{{$document->correlative}}</span>
                                                    @if($anuladoPorNc)
                                                        {{-- Se listan TODAS: el Neto de abajo las resta todas,
                                                             asi que nombrar solo una mentiria por omision. --}}
                                                        <div class="mt-1 whitespace-nowrap text-xs text-amber-700">
                                                            anulado por
                                                            @foreach($document->creditNotes as $nc)
                                                                <a href="javascript:;"
                                                                   class="underline decoration-dotted hover:text-primary"
                                                                   title="Buscar {{ $nc->serie }}-{{ $nc->correlative }}"
                                                                   wire:click="verComprobante(@js($nc->serie.'-'.$nc->correlative))">{{ $nc->serie }}-{{ $nc->correlative }}</a>{{ !$loop->last ? ',' : '' }}
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    {{-- La otra via de anulacion: baja ante SUNAT. No genera
                                                         ningun documento nuevo, asi que sin esta linea la unica
                                                         pista era el estado. --}}
                                                    @if($baja)
                                                        <div class="mt-1 whitespace-nowrap text-xs text-slate-500"
                                                             title="{{ $baja['tipo'] === 'RC' ? 'Resumen diario de baja de boletas' : 'Comunicación de baja de facturas' }}">
                                                            dado de baja el {{ $baja['fecha'] }} ·
                                                            <span class="font-medium">{{ $baja['numero'] }}</span>
                                                        </div>
                                                    @elseif($anuladoSinConstancia)
                                                        <div class="mt-1 whitespace-nowrap text-xs text-slate-500">
                                                            anulado sin constancia registrada
                                                        </div>
                                                    @endif
                                                @endif
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                @php
                                                    $tipoClave = $document->tipoClave();
                                                    $tipoEstilo = match($tipoClave) {
                                                        'factura'      => 'bg-blue-100 text-slate-700',
                                                        'boleta'       => 'bg-green-100 text-green-800',
                                                        'nota_credito' => 'bg-red-100 text-red-700',
                                                        default        => 'bg-slate-100 text-slate-700',
                                                    };
                                                @endphp
                                                @if($tipoClave)
                                                    <span class="{{ $tipoEstilo }} whitespace-nowrap rounded-full px-2.5 p-1 text-xs font-medium">{{ $document->tipoEtiqueta() }}</span>
                                                @else
                                                    <span class="text-slate-400" title="La serie {{ $document->serie }} no corresponde a boleta, factura ni nota de crédito">—</span>
                                                @endif
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">

                                                {{ $document->sale?->number ?? '—' }}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">

                                                {{-- Canal por el que entro la venta (Agora, Falabella, ...) --}}
                                                <span class="whitespace-nowrap">{{ $document->sale?->contact?->name ?? '—' }}</span>

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">

                                                {{$document->client->name}}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                {{-- En negativo porque resta del total. En el XML de SUNAT
                                                     la nota de credito figura en positivo: se avisa en el
                                                     tooltip para que nadie crea que hay una diferencia. --}}
                                                <span class="whitespace-nowrap {{ $esNotaCredito ? 'font-medium text-danger' : '' }}"
                                                      @if($esNotaCredito) title="Resta del total. En SUNAT figura como S/. {{ number_format($document->total,2) }}" @endif>
                                                    {{ $esNotaCredito ? '- ' : '' }}S/. {{ number_format($document->total,2) }}
                                                </span>
                                                @if($anuladoPorNc)
                                                    <div class="whitespace-nowrap text-xs text-slate-400">
                                                        Neto S/. {{ number_format($saldo, 2) }}
                                                    </div>
                                                @endif

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                <div class="flex items-center gap-1">
                                                    @if(!empty($document->pdf_path))
                                                        <a href="{{ route('documents.download', ['path' => ltrim($document->pdf_path, '/')]) }}" title="PDF del comprobante">
                                                            <img src="{{ asset('images/pdf.svg') }}" width="35px" alt="Descargar PDF">
                                                        </a>
                                                    @else
                                                        {{-- Sin PDF (pendiente o no generado) --}}
                                                        <img src="{{ asset('images/reload.svg') }}" width="35px" class="opacity-40 cursor-not-allowed" alt="PDF no disponible">
                                                    @endif
                                                    @if($document->status == 'anulado' && !empty($document->pdf_path_anulled))
                                                        <a href="{{ route('documents.download', ['path' => ltrim($document->pdf_path_anulled, '/')]) }}" title="Constancia de anulación (PDF)" class="relative">
                                                            <img src="{{ asset('images/pdf.svg') }}" width="24px" class="opacity-70" alt="Constancia de anulación">
                                                            <span class="absolute -top-1 -right-1 text-[9px] font-bold text-danger">B</span>
                                                        </a>
                                                    @endif
                                                </div>
                                            </x-base.table.td>

                                            <x-base.table.td class="text-center border-dashed dark:bg-darkmode-600">
                                                <div class="flex items-center gap-1">
                                                    @if(!empty($document->xml_path))
                                                        <a href="{{ route('documents.download', ['path' => ltrim($document->xml_path, '/')]) }}" title="XML del comprobante">
                                                            <img src="{{ asset('images/xml.svg') }}" width="35px" alt="Descargar XML">
                                                        </a>
                                                    @else
                                                        {{-- Muy raro, pero por si hay registros viejos sin XML --}}
                                                        <img src="{{ asset('images/reload.svg') }}" width="35px" class="opacity-40 cursor-not-allowed" alt="XML no disponible">
                                                    @endif
                                                    @if($document->status == 'anulado' && !empty($document->xml_path_anulled))
                                                        <a href="{{ route('documents.download', ['path' => ltrim($document->xml_path_anulled, '/')]) }}" title="XML de la anulación" class="relative">
                                                            <img src="{{ asset('images/xml.svg') }}" width="24px" class="opacity-70" alt="XML de la anulación">
                                                            <span class="absolute -top-1 -right-1 text-[9px] font-bold text-danger">B</span>
                                                        </a>
                                                    @endif
                                                </div>
                                            </x-base.table.td>

                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                <div class="flex items-center gap-1">
                                                    @if (!empty($document->cdr_path))
                                                        <a href="{{ route('documents.download', ['path' => ltrim($document->cdr_path, '/')]) }}" title="CDR del comprobante">
                                                            <img src="{{ asset('images/cdr.png') }}" width="35px" alt="Descargar CDR">
                                                        </a>
                                                    @else
                                                        {{-- Sin CDR: aún pendiente o error --}}
                                                        <img src="{{ asset('images/reload.svg') }}" width="35px" alt="CDR pendiente">
                                                    @endif
                                                    @if($document->status == 'anulado' && !empty($document->cdr_path_anulled))
                                                        <a href="{{ route('documents.download', ['path' => ltrim($document->cdr_path_anulled, '/')]) }}" title="CDR de la anulación" class="relative">
                                                            <img src="{{ asset('images/cdr.png') }}" width="24px" class="opacity-70" alt="CDR de la anulación">
                                                            <span class="absolute -top-1 -right-1 text-[9px] font-bold text-danger">B</span>
                                                        </a>
                                                    @endif
                                                </div>
                                            </x-base.table.td>

                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                @if($document->status_sunat == "aceptado")
                                                    <span
                                                        class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">
                                                        {{ucfirst(strtolower($document->status_sunat))}}</span>
                                                @else
                                                    <span
                                                        class="bg-red-100 text-white-100 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-white-100">
                                                        {{ucfirst(strtolower($document->status_sunat))}}</span>
                                                @endif
                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                {{-- $esNotaCredito ya viene calculado arriba, al inicio de la fila. --}}
                                                <div class="flex items-center justify-center">
                                                    {{-- Pendiente/rechazado (nunca aceptado por SUNAT): permitir corregir
                                                         la fecha de emisión y reenviar (salida al error 2329) --}}
                                                    @if($document->status != "anulado" && !$esNotaCredito && in_array($document->status_sunat, ['pendiente', 'rechazado']))
                                                        @can('update')
                                                            <x-base.tippy
                                                                as="x-base.button-sm"
                                                                variant="warning"
                                                                size="sm"
                                                                class="mr-2"
                                                                content="Cambiar fecha de emisión y reenviar"
                                                                wire:click="editDate({{$document->id}})">
                                                                <i class="text-white fa-solid fa-calendar-day"></i>
                                                            </x-base.tippy>
                                                        @endcan
                                                    @endif

                                                    {{-- Pendiente: eliminar definitivamente (la venta no se toca) --}}
                                                    @if($document->status_sunat == "pendiente" && !$esNotaCredito)
                                                        @can('delete')
                                                            <x-base.tippy
                                                                as="x-base.button-sm"
                                                                variant="danger"
                                                                size="sm"
                                                                class="mr-2"
                                                                content="Eliminar comprobante pendiente"
                                                                wire:click="deletePending({{$document->id}})">
                                                                <i class="text-white fa-solid fa-trash-can"></i>
                                                            </x-base.tippy>
                                                        @endcan
                                                    @endif

                                                    {{-- Por la relacion y no por status: al anular un comprobante
                                                         su status pasa a 'anulado' y se perdia la marca de que
                                                         ademas tenia nota de credito. --}}
                                                    @if($anuladoPorNc)
                                                        <span class="bg-amber-100 text-amber-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-amber-900 dark:text-amber-300">Con NC</span>
                                                    @elseif($document->status != "anulado" && $document->status_sunat != "pendiente")
                                                        @if($document->status_sunat == "aceptado" && !$esNotaCredito)
                                                        <x-base.tippy
                                                            as="x-base.button-sm"
                                                            variant="success"
                                                            size="sm"
                                                            class="mr-2"
                                                            content="Emitir nota de crédito"
                                                            wire:click="creditNote({{$document->id}})">
                                                            <i class="text-white fa-solid fa-file-invoice"></i>
                                                        </x-base.tippy>
                                                        @endif

{{--                                                        <x-base.tippy--}}
{{--                                                            as="x-base.button-sm"--}}
{{--                                                            variant="dark"--}}
{{--                                                            size="sm"--}}
{{--                                                            class="mr-2"--}}
{{--                                                            content="Enviar comprobante por email."--}}
{{--                                                            wire:click="creditNote({{$document->id}})">--}}
{{--                                                            <i class="text-white fa-solid fa-envelope"></i>--}}
{{--                                                        </x-base.tippy>--}}

                                                        {{-- Anular NO disponible para notas de crédito (decisión 2026-07-07):
                                                             anular una NC es un caso muy atípico y dejaba un estado inconsistente
                                                             (stock re-descontado + venta anulada + comprobante afectado vigente).
                                                             Si algún día se necesita, quitar el !$esNotaCredito de aquí y el guard
                                                             en TableDocuments::document_destroy; la lógica de reversión de la NC
                                                             sigue mapeada en TableDocuments::finishAnulacion (rama FC/BC). --}}
                                                        @if(!$esNotaCredito)
                                                            @can('delete')
                                                                <x-base.tippy
                                                                    as="x-base.button-sm"
                                                                    variant="danger"
                                                                    size="sm"
                                                                    class="mr-2"
                                                                    content="Anular comprobante"
                                                                    wire:click="delete({{$document->id}})">
                                                                    <i class="text-white fa-solid fa-xmark"></i>
                                                                </x-base.tippy>
                                                            @endcan
                                                        @endif
                                                    @endif

                                                </div>
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    @endforeach
                                @else
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="12"
                                                         class=" text-center border-dashed dark:bg-darkmode-600">
                                            No se encontrarón resultados.
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endif
                            </x-base.table.tbody>
                            <tfoot>
                                {{-- El pie suma TODOS los documentos del filtro, no solo la
                                     pagina visible. Solo entran los aceptados por SUNAT y
                                     las notas de credito restan. --}}
                                <x-base.table.tr>
                                    <x-base.table.td colspan="6"
                                                     class="border-t border-slate-200/60 bg-slate-50 text-right font-medium text-slate-500">
                                        Comprobantes aceptados ({{ $resumen['cantidad_comprobantes'] }})
                                    </x-base.table.td>
                                    <x-base.table.td colspan="6"
                                                     class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-600">
                                        S/. {{ number_format($resumen['comprobantes'], 2) }}
                                    </x-base.table.td>
                                </x-base.table.tr>
                                @if($resumen['notas_credito'] > 0)
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="6"
                                                         class="bg-slate-50 text-right font-medium text-slate-500">
                                            Notas de crédito ({{ $resumen['cantidad_notas_credito'] }})
                                        </x-base.table.td>
                                        <x-base.table.td colspan="6"
                                                         class="bg-slate-50 font-medium text-danger">
                                            - S/. {{ number_format($resumen['notas_credito'], 2) }}
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endif
                                <x-base.table.tr>
                                    <x-base.table.td colspan="6"
                                                     class="bg-slate-100 text-right text-base font-bold text-slate-700">
                                        {{-- Con el filtro de tipo puesto el total ya no es "lo aceptado":
                                             es solo la parte del tipo elegido, y hay que decirlo o
                                             contradice el "Neto" que muestran las propias filas. --}}
                                        @if($tipo === 'nota_credito')
                                            Total de notas de crédito
                                        @elseif($tipo)
                                            Total de {{ strtolower($tipos[$tipo] ?? $tipo) }} <span class="text-xs font-medium text-slate-500">(sin descontar notas de crédito)</span>
                                        @else
                                            Total aceptado por SUNAT
                                        @endif
                                    </x-base.table.td>
                                    <x-base.table.td colspan="6"
                                                     class="bg-slate-100 text-base font-bold text-slate-700">
                                        @if($tipo === 'nota_credito')
                                            {{-- En positivo: aisladas no restan de nada, son su propio total. --}}
                                            S/. {{ number_format(abs($resumen['neto']), 2) }}
                                        @else
                                            S/. {{ number_format($resumen['neto'], 2) }}
                                        @endif
                                        @if($resumen['excluidos'] > 0)
                                            <span class="ml-2 text-xs font-medium text-slate-400">
                                                ({{ $resumen['excluidos'] }} documento{{ $resumen['excluidos'] > 1 ? 's' : '' }} sin estado aceptado no suma{{ $resumen['excluidos'] > 1 ? 'n' : '' }})
                                            </span>
                                        @endif
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </tfoot>
                        </x-base.table>
                    </div>
                    <div class="m-4">
                        {{$documents->links()}}
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



