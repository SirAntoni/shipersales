<?php

namespace App\Livewire\Articles;

use App\Models\Article;
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
        $articles = Article::query()
            ->active()
            ->with([
                'category:id,name',
                'brand:id,name'
            ])
            ->whereNot('id', 1)
            // Búsqueda avanzada multi-término
            ->when($this->search, function ($query, $search) {
                // 1. Dividimos por espacios o '+' y limpiamos términos vacíos
                $terms = collect(preg_split('/[\s\+]+/', trim($search)))
                    ->filter()
                    ->map(fn($t) => Str::lower($t));

                // 2. Por cada término, forzamos que aparezca en al menos un campo/relación
                foreach ($terms as $term) {
                    $query->where(function ($q) use ($term) {
                        $q->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"])
                            ->orWhereRaw('LOWER(description) LIKE ?', ["%{$term}%"])
                            ->orWhereRaw('LOWER(detail) LIKE ?', ["%{$term}%"])
                            ->orWhereRaw('LOWER(sku) LIKE ?', ["%{$term}%"])
                            ->orWhereHas('category', fn($c) =>
                            $c->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                            )
                            ->orWhereHas('brand', fn($b) =>
                            $b->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                            );
                    });
                }
            })
            // Orden dinámico por título (alfabético A-Z / Z-A)
            ->when(in_array($this->sortTitle, ['asc','desc']), function ($q) {
                $q->orderBy('title', $this->sortTitle);
            })
            // Orden dinámico por stock (numérico ascendente/descendente)
            ->when(in_array($this->sortStock, ['asc','desc']), function ($q) {
                $q->orderBy('stock', $this->sortStock);
            })
            // Si no se aplica ningún orden, por defecto ordenar por el último ID
            ->when(!$this->sortTitle && !$this->sortStock, fn($q) => $q->orderByDesc('id'))
            ->paginate($limit);

        return view('livewire.articles.table-articles', compact('articles'));
    }
}
