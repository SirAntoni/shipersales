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

                        <div class="flex items-center gap-2">
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

                            <div class="relative mr-2">
                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                                <x-base.form-input
                                    class="rounded-[0.5rem] pl-9 sm:w-64"
                                    type="text"
                                    placeholder="Buscar comprobante..."
                                    wire:model.live="search"
                                />
                            </div>
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
                                        N° Orden
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
                                        <x-base.table.tr class="[&_td]:last:border-b-0">
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600 text">

                                                {{ $document->created_at->format("d-m-Y H:i:s")}}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                                <span
                                                    class="bg-blue-100 text-white-800 text-xs font-medium me-2 px-2.5 p-1 rounded-full">{{ $document->serie}}-{{$document->correlative}}</span>


                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">

                                                {{ $document->sale?->number ?? '—' }}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">

                                                {{$document->client->name}}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed dark:bg-darkmode-600">

                                                S/. {{ number_format($document->total,2) }}

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
                                                @php
                                                    $esNotaCredito = str_starts_with($document->serie, 'FC') || str_starts_with($document->serie, 'BC');
                                                @endphp
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

                                                    @if($document->status == "nota_credito")
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
                                        <x-base.table.td colspan="11"
                                                         class=" text-center border-dashed dark:bg-darkmode-600">
                                            No se encontrarón resultados.
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endif
                            </x-base.table.tbody>
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



