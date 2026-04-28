<?php

namespace App\Livewire\OnDemandProducts;

use App\Models\Article;
use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class TableOnDemandProducts extends Component
{
    use WithPagination;

    public $search = "";
    public $filter = "";

    public $sortTitle   = null;
    public $sortStock   = null;
    public $sortMargin  = null;

    public float $rate = 1;

    public function mount()
    {
        $this->rate = (float) (Setting::value('exchange_rate') ?? 1);
    }

    public function reportOnDemand()
    {
        $url = route('reports.on-demand-products');
        $this->dispatch('abrir-nueva-pestania', ['url' => $url]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function edit($id)
    {
        return redirect()->route('on-demand-products.show', $id);
    }

    #[On('destroy')]
    public function destroy($id)
    {
        $article = Article::findOrFail($id);
        $article->update(['status' => 'inactive']);
        $this->render();
    }

    public function refresh()
    {
        $this->render();
    }

    public function updatedSortTitle($v)  { if ($v) { $this->sortStock = null; $this->sortMargin = null; } }
    public function updatedSortStock($v)  { if ($v) { $this->sortTitle = null; $this->sortMargin = null; } }
    public function updatedSortMargin($v) { if ($v) { $this->sortTitle = null; $this->sortStock = null; } }

    public function delete($id)
    {
        $this->dispatch('delete', [
            'label' => 'Esta seguro que desea eliminar el producto a pedido?.',
            'btn'   => 'Eliminar',
            'route' => route('on-demand-products.index'),
            'id'    => $id,
        ]);
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
            ->onDemand()
            ->with(['category:id,name', 'brand:id,name'])
            ->whereNot('id', 1)
            ->when($this->search, function ($query, $search) {
                $terms = collect(preg_split('/[\s\+]+/', trim($search)))
                    ->filter()
                    ->map(fn($t) => Str::lower($t));

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

        return view('livewire.on-demand-products.table-on-demand-products', compact('articles', 'rate'));
    }
}
