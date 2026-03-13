<?php

namespace App\Livewire\Articles;

use App\Models\Article;
use App\Models\Setting;
use App\Imports\ArticleBarcodeImport;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class TableArticles extends Component
{
    use WithPagination, WithFileUploads;

    public $search = "";
    public $filter = "";

    public $sortTitle   = null;  // 'asc' | 'desc' | null
    public $sortStock   = null;  // 'asc' | 'desc' | null

    public $sortMargin = null; // 'asc' | 'desc' | null

    public float $rate = 1;

    public $importFile;

    public function mount()
    {
        $this->rate = (float) (Setting::value('exchange_rate') ?? 1);
    }

    public function reportArticle(){
        $url = route('reports.articles');
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }

    public function updatingSearch(){
        $this->resetPage();
    }

    public function edit($id)
    {
        return redirect()->route('articles.show', $id);
    }

    #[On('destroy')]
    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->update(['status' => 'inactive']);
        $this->render();
    }

    public function refresh(){
        $this->render();
    }

    public function updatedSortTitle($v)  { if($v) { $this->sortStock = null; $this->sortMargin = null; } }
    public function updatedSortStock($v)  { if($v) { $this->sortTitle = null; $this->sortMargin = null; } }
    public function updatedSortMargin($v) { if($v) { $this->sortTitle = null; $this->sortStock = null; } }

    public function delete($id)
    {
        $this->dispatch('delete', ['label' => 'Esta seguro que desea eliminar el articulo?.', 'btn' => 'Eliminar', 'route' => route('articles.index'), 'id' => $id]);
    }

    public function clearSearch()
    {
        $this->reset('search');
    }

    public function importBarcodes()
    {
        if (!$this->importFile) {
            $this->dispatch('errorNotRoute', ['label' => 'Selecciona un archivo primero.']);
            return;
        }

        $ext = strtolower($this->importFile->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            $this->dispatch('errorNotRoute', ['label' => 'El archivo debe ser .xlsx, .xls o .csv']);
            return;
        }

        try {
            $import = new ArticleBarcodeImport();
            Excel::import($import, $this->importFile->getRealPath());

            $this->reset('importFile');
            $this->dispatch('close-import-modal');
            $this->dispatch('successNotRoute', [
                'label' => "Importación completada: {$import->updated} actualizados, {$import->skipped} omitidos."
            ]);
        } catch (\Exception $e) {
            $this->dispatch('errorNotRoute', ['label' => 'Error al importar: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        $limit = 15;
        $rate  = (float) $this->rate;

        $articles = Article::query()
            ->active()
            ->with(['category:id,name', 'brand:id,name'])
            ->whereNot('id', 1)

            ->when($this->search, function ($query, $search) {
                // 1. Dividimos por espacios o '+' y limpiamos términos vacíos
                $terms = collect(preg_split('/[\s\+]+/', trim($search)))
                    ->filter()
                    ->map(fn($t) => Str::lower($t));

                // 2. Por cada término, forzamos que aparezca en al menos un campo/relación
                foreach ($terms as $term) {
                    $like = '%'.$term.'%';
                    $query->where(function ($q) use ($like) {
                        $q->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhere('detail', 'like', $like)
                            ->orWhere('sku', 'like', $like)
                            ->orWhere('barcode', 'like', $like)
                            ->orWhereIn('category_id', function ($sub) use ($like) {
                                $sub->select('id')->from('categories')->where('name', 'like', $like);
                            })
                            ->orWhereIn('brand_id', function ($sub) use ($like) {
                                $sub->select('id')->from('brands')->where('name', 'like', $like);
                            });
                    });
                }
            })

            ->when(in_array($this->sortTitle, ['asc','desc']), fn($q) =>
            $q->orderBy('title', $this->sortTitle)
            )
            ->when(in_array($this->sortStock, ['asc','desc']), fn($q) =>
            $q->orderBy('stock', $this->sortStock)
            )
            ->when(in_array($this->sortMargin, ['asc','desc']), function ($q) use ($rate) {
                $expr = "((sale_price - (purchase_price * ?)) / NULLIF(sale_price, 0))";
                $q->orderByRaw("$expr {$this->sortMargin}", [$rate]);
            })
            ->when(!$this->sortTitle && !$this->sortStock && !$this->sortMargin, fn($q) =>
            $q->orderByDesc('id')
            )
            ->paginate($limit);

        return view('livewire.articles.table-articles', compact('articles', 'rate'));
    }

}
