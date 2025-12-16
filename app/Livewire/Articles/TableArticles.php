<?php

namespace App\Livewire\Articles;

use App\Models\Article;
use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TableArticles extends Component
{
    use WithPagination;

    public $search = "";
    public $filter = "";

    public $sortTitle   = null;  // 'asc' | 'desc' | null
    public $sortStock   = null;  // 'asc' | 'desc' | null

    public $sortMargin = null; // 'asc' | 'desc' | null

    public float $rate = 1;

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

    public function render()
    {
        $limit = 15;
        $rate  = (float) $this->rate;

        $articles = Article::query()
            ->active()
            ->with(['category:id,name', 'brand:id,name'])
            ->whereNot('id', 1)

            // ...tu búsqueda...

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
