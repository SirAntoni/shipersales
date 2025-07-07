<div>
    <div class="grid grid-cols-12 gap-x-6 gap-y-10">
        <div class="col-span-12">
            <div class="flex flex-col gap-y-3 md:h-10 md:flex-row md:items-center">
                <div class="text-base font-medium group-[.mode--light]:text-white">
                    Articulos
                </div>

                    <div class="flex flex-col gap-x-3 gap-y-2 sm:flex-row md:ml-auto">
                        @can('articles.create')
                        <x-base.button
                            class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                            variant="primary"
                            onclick="window.location.href='{{ route('articles.create') }}'"
                        >
                            <div class="px-2"><i class="fa-solid fa-plus"></i></div>
                            Nuevo articulo
                        </x-base.button>
                        @endcan
                            <x-base.button
                                class="group-[.mode--light]:!border-transparent group-[.mode--light]:!bg-white/[0.12] group-[.mode--light]:!text-slate-200"
                                variant="primary"
                                wire:click="reportArticle"
                            >
                                <div class="px-2"><i class="fa-solid fa-file-excel"></i></div>
                                Exportar Excel
                            </x-base.button>
                    </div>


            </div>
            <div class="mt-3.5">
                <div class="box box--stacked flex flex-col">
                    <div class="flex flex-col gap-y-2 p-5 sm:flex-row sm:items-center justify-end">
                        <div>
                            <div class="relative mr-2">

                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-magnifying-glass"></i>
                                <x-base.form-input
                                    class="rounded-[0.5rem] pl-9 sm:w-64"
                                    type="text"
                                    placeholder="Buscar..."
                                    wire:model.live="search"
                                />
                                @if($search)
                                    <i
                                        class="absolute inset-y-0 right-0 z-10 my-auto mr-3.5 h-4 w-4 cursor-pointer text-slate-400 hover:text-slate-600 fa-solid fa-xmark"
                                        wire:click="clearSearch"
                                        title="Borrar búsqueda"
                                    ></i>
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="relative mr-2">

                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-arrow-up-1-9"></i>
                                <x-base.form-select
                                    class="rounded-[0.5rem] pl-9 sm:w-64"
                                    type="text"
                                    placeholder="Buscar..."
                                    wire:model.live="sortTitle"
                                >
                                    <option value="">Ordenar por Nombre</option>
                                    <option value="asc">A-Z Articulo</option>
                                    <option value="desc">Z-A Articulo</option>

                                </x-base.form-select>
                            </div>
                        </div>

                        <div>
                            <div class="relative">

                                <i class="absolute inset-y-0 left-0 z-10 my-auto ml-3.5 h-4 w-4 stroke-[1.3] text-slate-500 fa-solid fa-arrow-up-1-9"></i>
                                <x-base.form-select
                                    class="rounded-[0.5rem] pl-9 sm:w-64"
                                    type="text"
                                    placeholder="Buscar..."
                                    wire:model.live="sortStock"
                                >
                                    <option value="">Ordenar por Stock</option>
                                    <option value="asc">Ascendente</option>
                                    <option value="desc">Descendente</option>

                                </x-base.form-select>
                            </div>
                        </div>


                    </div>
                    <div class="overflow-auto xl:overflow-visible">
                        <x-base.table class="border-b border-slate-200/60">
                            <x-base.table.thead>
                                <x-base.table.tr>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        Nombre
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        Categoría
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        Marca
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        Detalle
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        SKU
                                    </x-base.table.td>
                                    <x-base.table.td
                                        class="w-28 border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        Stock
                                    </x-base.table.td>
                                    @if(auth()->user()->id == 1)
                                        <x-base.table.td
                                            class="w-36 border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                        >
                                            Precio Compra
                                        </x-base.table.td>
                                    @endif
                                    <x-base.table.td
                                        class="w-36 border-t border-slate-200/60 bg-slate-50 py-4 font-medium text-slate-500"
                                    >
                                        Precio Venta
                                    </x-base.table.td>

                                    <x-base.table.td
                                        class="w-36 border-t border-slate-200/60 bg-slate-50 py-4 text-center font-medium text-slate-500"
                                    >
                                        Action
                                    </x-base.table.td>
                                </x-base.table.tr>
                            </x-base.table.thead>
                            <x-base.table.tbody>
                                @if($articles->count() > 0 )
                                    @foreach ($articles as $article)
                                        <x-base.table.tr class="[&_td]:last:border-b-0">
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">

                                                {{ $article->title }}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">

                                                {{ $article->category->name }}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">

                                                {{ $article->brand->name }}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">

                                                {{ $article->detail }}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">

                                                {{ $article->sku }}

                                            </x-base.table.td>
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">

                                                @if($article->stock == 0)
                                                    <span
                                                        class="bg-gray-100 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-gray-700 dark:text-gray-300">{{$article->stock}}</span>
                                                @elseif($article->stock > 0  && $article->stock <= 10)
                                                    <span
                                                        class="bg-red-100 text-red-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-red-900 dark:text-red-300">{{$article->stock}}</span>

                                                @elseif($article->stock > 10)
                                                    <span
                                                        class="bg-green-100 text-green-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">{{$article->stock}}</span>
                                                @endif
                                            </x-base.table.td>
                                            @if(auth()->user()->id == 1)
                                                <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">

                                                    $ {{ number_format($article->purchase_price,2) }}

                                                </x-base.table.td>
                                            @endif
                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">

                                                S/. {{ number_format($article->sale_price,2) }}

                                            </x-base.table.td>

                                            <x-base.table.td class="border-dashed py-4 dark:bg-darkmode-600">
                                                <div class="flex items-center justify-center">
                                                    @can('update')
                                                        <x-base.button
                                                            class="mr-2"
                                                            variant="success"
                                                            wire:click="edit({{$article->id}})"
                                                        >
                                                            <i class="text-white fa-solid fa-pen-to-square"></i>
                                                        </x-base.button>
                                                    @endcan
                                                    @can('delete')
                                                        <x-base.button
                                                            variant="danger"
                                                            wire:click="delete({{$article->id}})"
                                                        >
                                                            <i class="text-white fa-solid fa-trash"></i>
                                                        </x-base.button>
                                                    @endcan
                                                </div>
                                            </x-base.table.td>
                                        </x-base.table.tr>
                                    @endforeach
                                @else
                                    <x-base.table.tr>
                                        <x-base.table.td colspan="9"
                                                         class=" text-center border-dashed py-4 dark:bg-darkmode-600">
                                            No se encontrarón resultados.
                                        </x-base.table.td>
                                    </x-base.table.tr>
                                @endif
                            </x-base.table.tbody>
                        </x-base.table>
                    </div>
                    <div class="m-4">
                        {{$articles->links()}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
