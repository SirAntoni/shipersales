<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Compras USA
                </div>
                <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                    <x-base.button
                        class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                        variant="primary"
                        wire:click="newRecord"
                    >
                        <i class="fa-solid fa-plus mr-1"></i>
                        Nuevo registro
                    </x-base.button>
                </div>
            </div>

            {{-- Summary --}}
            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 text-blue-700 px-3 py-1 font-medium">{{ $totalRecords }} total</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 text-green-700 px-3 py-1 font-medium">{{ $totalDelivered }} entregados</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 text-amber-700 px-3 py-1 font-medium">{{ $totalShipped }} embarcados</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 text-red-700 px-3 py-1 font-medium">{{ $totalPending }} parcial/no llegó</span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 text-slate-600 px-3 py-1 font-medium">{{ $totalProcessed }} procesados</span>
            </div>

            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">
                    {{-- Filters --}}
                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center sm:flex-wrap gap-x-3">
                        <div class="relative">
                            <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                            <x-base.form-input
                                class="rounded-[0.5rem] pl-9 sm:w-56"
                                type="text"
                                placeholder="Buscar..."
                                wire:model.live="search"
                            />
                        </div>
                        <x-base.form-select class="sm:w-36" wire:model.live="filterType">
                            <option value="">Tipo: Todos</option>
                            <option value="CARGO">Cargo</option>
                            <option value="VIAJERO">Viajero</option>
                        </x-base.form-select>
                        <x-base.form-select class="sm:w-28" wire:model.live="filterYear">
                            <option value="">Año: Todos</option>
                            @foreach($years as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </x-base.form-select>
                        <x-base.form-select class="sm:w-36" wire:model.live="filterStatus">
                            <option value="">Estado: Todos</option>
                            <option value="ENTREGADO">Entregado</option>
                            <option value="EMBARCADO">Embarcado</option>
                            <option value="PARCIAL">Parcial</option>
                            <option value="NO LLEGO">No llegó</option>
                        </x-base.form-select>
                        <x-base.form-select class="sm:w-40" wire:model.live="filterStore">
                            <option value="">Tienda: Todos</option>
                            @foreach($stores as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </x-base.form-select>
                        @if($filterType || $filterYear || $filterStatus || $filterStore)
                            <x-base.button variant="secondary" size="sm" wire:click="clearFilters">
                                <i class="fa-solid fa-xmark mr-1"></i> Limpiar
                            </x-base.button>
                        @endif
                    </div>

                    {{-- Table --}}
                    <div class="overflow-auto xl:overflow-visible text-sm">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Fecha</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Tipo</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Pasajero</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Tienda</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Orden</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Tracking</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500 text-center">Cant.</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Descripción</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">Estado</x-base.table.td>
                                    <x-base.table.td class="border-t border-slate-200/60 bg-slate-50 font-medium text-slate-500">F. Ingreso</x-base.table.td>
                                    <x-base.table.td class="w-28 border-t border-slate-200/60 bg-slate-50 text-center font-medium text-slate-500">Acciones</x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @forelse ($records as $record)
                                    <x-base.table.tr class="[&_td]:last:border-b-0 {{ $record->processed ? 'bg-slate-50/50' : '' }}">
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            {{ $record->date?->format('d/m/Y') ?? '-' }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $record->type === 'CARGO' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700' }}">
                                                {{ $record->type }}
                                            </span>
                                        </x-base.table.td>
                                        <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                            {{ $record->carrier }}
                                        </x-base.table.td-sale>
                                        <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                            {{ $record->store }}
                                        </x-base.table.td-sale>
                                        <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600 text-xs">
                                            {{ \Illuminate\Support\Str::limit($record->order_number, 25) }}
                                        </x-base.table.td-sale>
                                        <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600 text-xs">
                                            {{ \Illuminate\Support\Str::limit($record->tracking, 20) }}
                                        </x-base.table.td-sale>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600 text-center">
                                            {{ $record->quantity }}
                                        </x-base.table.td>
                                        <x-base.table.td-sale class="border-dashed dark:bg-darkmode-600">
                                            {{ \Illuminate\Support\Str::limit($record->description, 30) }}
                                        </x-base.table.td-sale>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            @php
                                                $statusColors = [
                                                    'ENTREGADO' => 'bg-green-100 text-green-700',
                                                    'EMBARCADO' => 'bg-yellow-100 text-yellow-700',
                                                    'PARCIAL' => 'bg-orange-100 text-orange-700',
                                                    'NO LLEGO' => 'bg-red-100 text-red-700',
                                                ];
                                            @endphp
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$record->status] ?? 'bg-slate-100 text-slate-700' }}">
                                                {{ $record->status }}
                                            </span>
                                            @if($record->processed)
                                                <i class="fa-solid fa-check-circle text-primary ml-1" title="Procesado"></i>
                                            @endif
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            {{ $record->arrival_date?->format('d/m/Y') ?? '-' }}
                                        </x-base.table.td>
                                        <x-base.table.td class="border-dashed dark:bg-darkmode-600">
                                            <div class="flex items-center justify-center gap-1">
                                                @if(!$record->processed && in_array($record->status, ['ENTREGADO','PARCIAL']) && $record->article_id)
                                                    <x-base.tippy
                                                        as="x-base.button-sm"
                                                        variant="success"
                                                        size="sm"
                                                        content="Importar a stock"
                                                        wire:click="openImport({{ $record->id }})">
                                                        <i class="text-white fa-solid fa-file-import"></i>
                                                    </x-base.tippy>
                                                @endif
                                                <x-base.tippy
                                                    as="x-base.button-sm"
                                                    variant="primary"
                                                    size="sm"
                                                    content="Editar"
                                                    wire:click="editRecord({{ $record->id }})">
                                                    <i class="text-white fa-solid fa-edit"></i>
                                                </x-base.tippy>
                                                <x-base.tippy
                                                    as="x-base.button-sm"
                                                    variant="danger"
                                                    size="sm"
                                                    content="Eliminar"
                                                    wire:click="deleteRecord({{ $record->id }})">
                                                    <i class="text-white fa-solid fa-trash"></i>
                                                </x-base.tippy>
                                            </div>
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @empty
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="11" class="text-center border-dashed dark:bg-darkmode-600">
                                            No se encontraron registros.
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endforelse
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                    <div class="m-4">
                        {{ $records->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Import --}}
    <div
        x-data="{ show: false }"
        x-on:open-import-usa-modal.window="show = true"
        x-on:close-import-usa-modal.window="show = false"
        x-on:keydown.escape.window="if(show) show = false"
        wire:ignore.self
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
                class="relative bg-white dark:bg-darkmode-600 rounded-lg shadow-xl w-[420px] p-6"
                style="z-index: 1051;"
                x-on:click.stop
            >
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-medium">Importar Compras USA</h3>
                    <button x-on:click="show = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
                <p class="text-sm text-slate-500 mb-4">
                    Sube un archivo Excel (.xlsx) con las hojas organizadas por tipo y año
                    (ej: CARGO 2025, VIAJEROS 2025).
                </p>
                <div class="mb-4">
                    <input type="file" wire:model="importFile" accept=".xlsx,.xls"
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20"
                        wire:loading.attr="disabled" wire:target="importFile"/>
                    <div wire:loading wire:target="importFile" class="text-xs text-slate-400 mt-1">
                        <i class="fa-solid fa-spinner animate-spin mr-1"></i> Subiendo archivo...
                    </div>
                </div>
                <div class="flex justify-end gap-2">
                    <x-base.button variant="secondary" x-on:click="show = false"
                        wire:loading.attr="disabled" wire:target="importExcel">
                        Cancelar
                    </x-base.button>
                    <x-base.button variant="primary" wire:click="importExcel"
                        wire:loading.attr="disabled" wire:target="importExcel,importFile">
                        <span wire:loading.remove wire:target="importExcel">
                            <i class="fa-solid fa-upload mr-1"></i> Importar
                        </span>
                        <span wire:loading wire:target="importExcel">
                            <i class="fa-solid fa-spinner animate-spin mr-1"></i> Importando...
                        </span>
                    </x-base.button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div
        x-data="{ show: false }"
        x-on:open-edit-usa-modal.window="show = true"
        x-on:close-edit-usa-modal.window="show = false"
        x-on:keydown.escape.window="if(show) { show = false }"
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
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-medium">{{ $editingId ? 'Editar registro' : 'Nuevo registro' }}</h3>
                    <button x-on:click="show = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <x-base.form-label>Fecha</x-base.form-label>
                        <x-base.form-input type="date" wire:model="editDate" />
                    </div>
                    <div>
                        <x-base.form-label>Tipo</x-base.form-label>
                        <x-base.form-select wire:model="editType">
                            <option value="CARGO">Cargo</option>
                            <option value="VIAJERO">Viajero</option>
                        </x-base.form-select>
                        @error('editType') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <x-base.form-label>Estado</x-base.form-label>
                        <x-base.form-select wire:model="editStatus">
                            <option value="EMBARCADO">Embarcado</option>
                            <option value="ENTREGADO">Entregado</option>
                            <option value="PARCIAL">Parcial</option>
                            <option value="NO LLEGO">No llegó</option>
                        </x-base.form-select>
                        @error('editStatus') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <x-base.form-label>Pasajero / Consignatario</x-base.form-label>
                        <x-base.form-input type="text" wire:model="editCarrier" placeholder="Ej: Felipe Sotelo" />
                        @error('editCarrier') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <x-base.form-label>Tienda</x-base.form-label>
                        <x-base.form-input type="text" wire:model="editStore" placeholder="Ej: AMAZON, EBAY" />
                    </div>
                    <div>
                        <x-base.form-label>Fecha ingreso</x-base.form-label>
                        <x-base.form-input type="date" wire:model="editArrivalDate" />
                    </div>
                    <div>
                        <x-base.form-label>Orden</x-base.form-label>
                        <x-base.form-input type="text" wire:model="editOrderNumber" placeholder="Ej: Pedido #123-456" />
                    </div>
                    <div>
                        <x-base.form-label>Tracking</x-base.form-label>
                        <x-base.form-input type="text" wire:model="editTracking" placeholder="Ej: TBA318541946298" />
                    </div>
                    <div></div>

                    {{-- Artículos --}}
                    <div class="col-span-3">
                        <x-base.form-label>Buscar artículo</x-base.form-label>
                        <div wire:ignore>
                            <x-base.tom-select id="usaArticlesSelect" class="w-full"></x-base.tom-select>
                        </div>
                    </div>

                    <div class="col-span-3">
                        @error('articlesSelected') <div class="text-sm text-red-600 mb-2">{{ $message }}</div> @enderror

                        @if(count($articlesSelected) > 0)
                            <div class="border border-slate-200 rounded-md">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-slate-500">Artículo</th>
                                            <th class="px-3 py-2 text-center font-medium text-slate-500 w-32">Cantidad</th>
                                            <th class="px-3 py-2 text-center font-medium text-slate-500 w-16"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($articlesSelected as $index => $item)
                                            <tr class="border-t border-slate-100">
                                                <td class="px-3 py-2">
                                                    <span class="text-xs text-slate-500">[{{ $item['sku'] ?? '—' }}]</span>
                                                    {{ $item['title'] }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="inline-flex items-center border border-slate-200 rounded-md overflow-hidden bg-white shadow-sm">
                                                        <button type="button" wire:click="decrementQty({{ $index }})"
                                                            class="w-8 h-8 flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                                                            <i class="fa-solid fa-minus text-xs"></i>
                                                        </button>
                                                        <input type="number" min="1"
                                                            class="w-14 text-center text-sm font-medium border-0 focus:ring-0 focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                                            wire:model="articlesSelected.{{ $index }}.quantity" />
                                                        <button type="button" wire:click="incrementQty({{ $index }})"
                                                            class="w-8 h-8 flex items-center justify-center text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                                                            <i class="fa-solid fa-plus text-xs"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <button type="button" wire:click="removeArticle({{ $index }})" class="text-red-600 hover:text-red-800">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-sm text-slate-400 italic text-center py-3 border border-dashed border-slate-200 rounded-md">
                                Busca y agrega uno o más artículos
                            </div>
                        @endif
                    </div>

                    <div class="col-span-3">
                        <x-base.form-label>Comentarios</x-base.form-label>
                        <x-base.form-textarea wire:model="editComments" placeholder="Notas u observaciones (opcional)"></x-base.form-textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <x-base.button variant="secondary" x-on:click="show = false">
                        Cancelar
                    </x-base.button>
                    <x-base.button variant="primary" wire:click="saveRecord">
                        {{ $editingId ? 'Guardar' : 'Crear' }}
                    </x-base.button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Importar a stock --}}
    <div
        x-data="{ show: false }"
        x-on:open-import-stock-modal.window="show = true"
        x-on:close-import-stock-modal.window="show = false"
        x-on:keydown.escape.window="if(show) show = false"
        wire:ignore.self
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
                class="relative bg-white dark:bg-darkmode-600 rounded-lg shadow-xl w-[480px] p-6"
                style="z-index: 1051;"
                x-on:click.stop
            >
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-medium">Importar a stock</h3>
                    <button x-on:click="show = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="mb-3 p-3 bg-slate-50 rounded-md text-sm">
                    <div><strong>Artículo:</strong> {{ $importArticleTitle }}</div>
                    <div class="text-xs text-slate-500 mt-1">Cantidad pedida: {{ $importOriginalQuantity }}</div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <x-base.form-label>Cantidad recibida</x-base.form-label>
                        <x-base.form-input type="number" min="1" wire:model="importQuantity" />
                        @error('importQuantity') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <x-base.form-label>Precio compra ($)</x-base.form-label>
                        <x-base.form-input type="number" step="0.01" min="0" wire:model="importPrice" />
                        @error('importPrice') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-span-2">
                        <x-base.form-label>Proveedor</x-base.form-label>
                        <x-base.form-select wire:model="importProviderId">
                            <option value="">Selecciona un proveedor</option>
                            @foreach($providers as $prov)
                                <option value="{{ $prov['id'] }}">{{ $prov['name'] }}</option>
                            @endforeach
                        </x-base.form-select>
                        @error('importProviderId') <div class="text-sm text-red-600">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <x-base.button variant="secondary" x-on:click="show = false">
                        Cancelar
                    </x-base.button>
                    <x-base.button variant="primary" wire:click="confirmImport">
                        <i class="fa-solid fa-check mr-1"></i> Confirmar importación
                    </x-base.button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let usaTs = null;

            function initUsaTomSelect() {
                const el = document.getElementById('usaArticlesSelect');
                if (!el) return;
                if (usaTs) { usaTs.destroy(); usaTs = null; }

                usaTs = new TomSelect('#usaArticlesSelect', {
                    valueField: 'value',
                    labelField: 'text',
                    searchField: 'text',
                    maxItems: 1,
                    create: false,
                    loadThrottle: 300,
                    load: function (query, callback) {
                        if (!query.length) return callback();
                        @this.call('searchArticles', query)
                            .then(data => callback(data))
                            .catch(() => callback());
                    },
                    onItemAdd: function (value) {
                        @this.call('addArticle', value);
                        this.clear();
                        this.blur();
                    }
                });
            }

            window.addEventListener('open-edit-usa-modal', () => {
                setTimeout(initUsaTomSelect, 100);
            });

            window.addEventListener('close-edit-usa-modal', () => {
                if (usaTs) { usaTs.destroy(); usaTs = null; }
            });
        });
    </script>
</div>
