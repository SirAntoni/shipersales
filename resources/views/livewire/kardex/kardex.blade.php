
<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-1">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Kardex - Entradas y salidas
                </div>

            </div>

        </div>
        <div class="col-span-12 mb-8">
            <div class="box box--stacked mt-3.5 flex flex-col p-5 sm:p-6">
                <div
                    class="col-span-12 relative mb-4 mt-7 rounded-[0.6rem] border border-slate-200/80 dark:border-darkmode-400">
                    <div class="absolute left-0 -mt-2 ml-4 bg-white px-3 text-xs uppercase text-slate-500">
                        <div class="-mt-px">Buscar Producto</div>
                    </div>
                    <div class="grid grid-cols-12 pt-4">
                        <div class="col-span-12 px-5 py-2">
                            <div class="flex items-center gap-2">
                                <!-- INPUT ocupa todo -->
                                <div class="flex-1 min-w-0" wire:ignore>
                                    <x-base.tom-select
                                        id="tomArticles"
                                        class="w-full"
                                        data-placeholder="Buscar producto por nombre"
                                        wire:model.live="article">
                                    </x-base.tom-select>
                                </div>

                                <!-- Botón: Refrescar resultados (mantiene el producto seleccionado) -->
                                <button type="button"
                                        wire:click="getKardex"
                                        wire:loading.attr="disabled"
                                        wire:target="getKardex"
                                        @disabled(!$article)
                                        class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fa-solid fa-rotate-right" wire:loading.class="fa-spin" wire:target="getKardex"></i>
                                    Refrescar
                                </button>

                                <!-- Botón: Copiar Nombre + toast encima -->
                                <div class="relative shrink-0 inline-block">

                                    <button type="button" id="btnCopyName"
                                            class="inline-flex items-center px-3 py-1.5 rounded-md text-sm bg-slate-800 text-white hover:bg-slate-700 disabled:opacity-50">
                                        Copiar Nombre
                                    </button>
                                    <div id="toastName"
                                         class="pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2
                    bg-emerald-600 text-white text-xs px-2.5 py-1 rounded shadow
                    opacity-0 translate-y-1 transition duration-200">
                                        Copiado
                                    </div>
                                </div>

                                <!-- Botón: Copiar SKU + toast encima -->
                                <div class="relative shrink-0 inline-block">
                                    <button type="button" id="btnCopySku"
                                            class="inline-flex items-center px-3 py-1.5 rounded-md text-sm border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-50">
                                        Copiar SKU
                                    </button>
                                    <div id="toastSku"
                                         class="pointer-events-none absolute -top-9 left-1/2 -translate-x-1/2
                    bg-emerald-600 text-white text-xs px-2.5 py-1 rounded shadow
                    opacity-0 translate-y-1 transition duration-200">
                                        Copiado
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>




                </div>
            </div>
        </div>
    </div>


    <div class="col-span-12">
        <div>

            <div class="box box--stacked mt-3.5">
                <div id="kardexScroll" class="w-full overflow-x-auto">
                    <x-base.table>
                        <x-base.table.thead>
                            <x-base.table.tr>

                                <x-base.table.td
                                    class="w-40 border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]"
                                >
                                    Fecha
                                </x-base.table.td>
                                <x-base.table.td
                                    class="w-40 border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem]"
                                >
                                    Número
                                </x-base.table.td>
                                <x-base.table.td
                                    class="truncate border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem] text-center"
                                >
                                    Contacto
                                </x-base.table.td>
                                <x-base.table.td
                                    class="truncate border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem] text-center"
                                >
                                    Cliente
                                </x-base.table.td>
                                <x-base.table.td
                                    class="truncate border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem] text-center"
                                >
                                    Usuario
                                </x-base.table.td>
                                <x-base.table.td
                                    class="w-24 border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem] text-center"
                                >
                                    Entradas
                                </x-base.table.td>
                                <x-base.table.td
                                    class="w-24 border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem] text-center"
                                >
                                    Salidas
                                </x-base.table.td>
                                <x-base.table.td
                                    class="w-24 border-slate-200/80 bg-slate-50 py-5 font-medium text-slate-500 first:rounded-tl-[0.6rem] last:rounded-tr-[0.6rem] text-center"
                                >
                                    Saldo
                                </x-base.table.td>

                            </x-base.table.tr>
                        </x-base.table.thead>
                        <x-base.table.tbody>

                            @if(collect($kardex)->count() > 0)

                                @foreach($kardex as $article)

                                    @php
                                        $rowColor = ($article->src === 'adjustment')
                                            ? 'text-warning'
                                            : (($article->tipo == 'entrada') ? 'text-success' : 'text-danger');
                                    @endphp

                                    <x-base.table.tr class="[&_td]:last:border-b-0">

                                        <x-base.table.td
                                            class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600"
                                        >

                                            <div
                                                class="ml-1.5 whitespace-nowrap {{ $rowColor }} font-semibold">
                                               {{$article->fecha}}
                                            </div>

                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600"
                                        >

                                            <div
                                                class="ml-1.5 whitespace-nowrap {{ $rowColor }} font-semibold">
                                                {{($article->tipo == "salida") ? $article->number:$article->document}}
                                            </div>

                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                        >
                                            <div
                                                class="ml-1.5 whitespace-nowrap {{ $rowColor }} font-semibold">
                                                {{($article->tipo == "salida") ? $article->contact_name:$article->provider_name}}
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                        >
                                            <div
                                                class="ml-1.5 whitespace-nowrap {{ $rowColor }} font-semibold">
                                                {{($article->tipo == "salida") ? $article->client_name:$article->passenger}}
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                        >
                                            <div
                                                class="ml-1.5 whitespace-nowrap {{ $rowColor }} font-semibold">
                                                {{$article->user_name}}
                                            </div>
                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                        >

                                            <div
                                                class="ml-1.5 whitespace-nowrap {{ $rowColor }} font-semibold">
                                                {{($article->tipo == "entrada") ? $article->cantidad:"-"}}
                                            </div>

                                        </x-base.table.td>

                                        <x-base.table.td
                                            class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                        >

                                            <div
                                                class="ml-1.5 whitespace-nowrap {{ $rowColor }} font-semibold">
                                                {{($article->tipo == "salida") ? $article->cantidad:"-"}}
                                            </div>

                                        </x-base.table.td>
                                        <x-base.table.td
                                            class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                        >

                                            <div
                                                class="ml-1.5 whitespace-nowrap {{ $rowColor }} font-semibold">
                                                <i class="fa-solid {{($article->tipo == "entrada") ? "fa-circle-plus":"fa-circle-minus"}}"></i> {{$article->saldo}}
                                            </div>

                                        </x-base.table.td>

                                    </x-base.table.tr>

                                @endforeach

                            @else
                                <x-base.table.tr class="[&_td]:last:border-b-0">

                                    <x-base.table.td
                                        colspan="8"
                                        class="rounded-l-none rounded-r-none border-x-0 border-t-0 border-dashed py-5 first:rounded-l-[0.6rem] last:rounded-r-[0.6rem] dark:bg-darkmode-600 text-center"
                                    >

                                        Seleccione un articulo

                                    </x-base.table.td>


                                </x-base.table.tr>

                            @endif


                        </x-base.table.tbody>
                    </x-base.table>
                </div>
            </div>

        </div>
    </div>

    <div id="copyToast"
         class="fixed z-50 top-4 right-4 pointer-events-none transition-all duration-300
            opacity-0 translate-y-2 hidden">
        <div class="rounded-md bg-emerald-600 text-white px-4 py-2 shadow-lg text-sm">
            Copiado
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ts = new TomSelect('#tomArticles', {
            valueField: 'value',
            labelField: 'text',
            searchField: 'text',
            maxItems: 1,
            create: false,
            shouldLoad: q => q.length > 0,
            load: function (query, callback) {
                @this.call('searchArticles', query).then(callback).catch(() => callback());
            }
        });

        const btnName   = document.getElementById('btnCopyName');
        const btnSku    = document.getElementById('btnCopySku');
        const toastName = document.getElementById('toastName');
        const toastSku  = document.getElementById('toastSku');

        function setButtonsState() {
            const has = !!ts.getValue();
            btnName.disabled = !has;
            btnSku.disabled  = !has;
        }
        ts.on('change', setButtonsState);
        setButtonsState();

        function getSelectedText() {
            const item = ts.control.querySelector('.item');
            if (item) return item.textContent.trim();
            const val = ts.getValue();
            const opt = ts.getOption(val);
            return opt ? opt.textContent.trim() : '';
        }
        function splitNameSku(full) {
            const sep = ' - ';
            const idx = full.lastIndexOf(sep);
            if (idx === -1) return { name: full, sku: '' };
            return { name: full.slice(0, idx).trim(), sku: full.slice(idx + sep.length).trim() };
        }

        function showInlineToast(el, msg='Copiado') {
            if (!el) return;
            el.textContent = msg;
            el.classList.remove('opacity-0','translate-y-1');
            el.classList.add('opacity-100','translate-y-0');
            clearTimeout(el._t);
            el._t = setTimeout(() => {
                el.classList.add('opacity-0','translate-y-1');
                el.classList.remove('opacity-100','translate-y-0');
            }, 1200);
        }

        async function copyToClipboard(text, toastEl) {
            if (!text) { showInlineToast(toastEl, 'Sin datos'); return; }
            try {
                await navigator.clipboard.writeText(text);
                showInlineToast(toastEl, 'Copiado');
            } catch {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly','');
                ta.style.position = 'fixed';
                ta.style.top = '-1000px';
                document.body.appendChild(ta);
                ta.select();
                try { document.execCommand('copy'); showInlineToast(toastEl, 'Copiado'); }
                finally { document.body.removeChild(ta); }
            }
        }

        btnName.addEventListener('click', () => {
            const { name } = splitNameSku(getSelectedText());
            copyToClipboard(name, toastName);
        });
        btnSku.addEventListener('click', () => {
            const { sku } = splitNameSku(getSelectedText());
            copyToClipboard(sku, toastSku);
        });
    });
</script>

<script>
    // Arrastrar con el mouse para desplazar la tabla del kardex horizontalmente.
    (function () {
        const SEL = '#kardexScroll';

        function refreshCursor(el) {
            if (!el) return;
            const scrollable = el.scrollWidth > el.clientWidth + 1;
            // "manito" (grab) solo cuando hay contenido que desplazar
            el.style.cursor = scrollable ? 'grab' : '';
        }

        function bind(el) {
            if (!el || el.dataset.dragBound) return;
            el.dataset.dragBound = '1';

            let isDown = false, startX = 0, startLeft = 0, moved = false;

            el.addEventListener('mousedown', function (e) {
                if (e.button !== 0 || el.scrollWidth <= el.clientWidth + 1) return;
                isDown = true;
                moved = false;
                startX = e.pageX;
                startLeft = el.scrollLeft;
                el.style.cursor = 'grabbing';
                el.style.userSelect = 'none';
            });

            window.addEventListener('mousemove', function (e) {
                if (!isDown) return;
                const dx = e.pageX - startX;
                if (Math.abs(dx) > 3) moved = true;
                el.scrollLeft = startLeft - dx;
                e.preventDefault();
            });

            window.addEventListener('mouseup', function () {
                if (!isDown) return;
                isDown = false;
                el.style.userSelect = '';
                refreshCursor(el); // vuelve a 'grab'
            });

            // Evita que un arrastre dispare clicks accidentales dentro de la tabla
            el.addEventListener('click', function (e) {
                if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
            }, true);
        }

        function init() {
            const el = document.querySelector(SEL);
            bind(el);
            refreshCursor(el);
        }

        document.addEventListener('DOMContentLoaded', init);
        document.addEventListener('livewire:navigated', init);
        document.addEventListener('livewire:init', function () {
            if (window.Livewire && window.Livewire.hook) {
                // Tras cada actualizacion del componente (ej. seleccionar producto)
                window.Livewire.hook('commit', function (payload) {
                    if (payload && payload.succeed) {
                        payload.succeed(function () { setTimeout(init, 0); });
                    }
                });
            }
        });
        window.addEventListener('resize', function () {
            refreshCursor(document.querySelector(SEL));
        });
    })();
</script>


