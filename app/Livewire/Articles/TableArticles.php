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
        Article::destroy($id);
        $this->render();
    }

    public function delete($id)
    {
        $this->dispatch('delete', ['label' => 'Esta seguro que desea eliminar el articulo?.', 'btn' => 'Eliminar', 'route' => route('articles.index'), 'id' => $id]);
    }

    public function render()
    {
        $limit = 15;
        $articles = Article::query()
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

            ->orderByDesc('id')
            ->paginate($limit);

        return view('livewire.articles.table-articles', compact('articles'));
    }
}
