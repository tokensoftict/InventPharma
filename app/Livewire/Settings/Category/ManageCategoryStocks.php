<?php

namespace App\Livewire\Settings\Category;

use App\Models\Stock;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class ManageCategoryStocks extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public Category $category;
    public $search = '';
    public $currentSearch = '';

    protected $listeners = ['refreshData' => '$refresh'];

    public function mount(Category $category)
    {
        $this->category = $category;
    }

    public function addStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock) {
            $stock->category_id = $this->category->id;
            $stock->save();
            
            // Trigger Kafka sync if the trait method exists
            if (method_exists($stock, 'updateonlinePush')) {
                $stock->updateonlinePush();
            }

            $this->dispatch('refreshData');
            session()->flash('success', 'Stock added to category successfully.');
        }
    }

    public function removeStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock && $stock->category_id == $this->category->id) {
            $stock->category_id = null;
            $stock->save();

            // Trigger Kafka sync if the trait method exists
            if (method_exists($stock, 'updateonlinePush')) {
                $stock->updateonlinePush();
            }

            $this->dispatch('refreshData');
            session()->flash('success', 'Stock removed from category successfully.');
        }
    }

    public function render()
    {
        $currentStocks = $this->category->stocks();

        if (!empty($this->currentSearch)) {
            $currentStocks->where('name', 'like', '%' . $this->currentSearch . '%');
        }

        $currentStocks = $currentStocks->paginate(10, ['*'], 'currentStocksPage');

        $availableStocks = [];
        if (!empty($this->search)) {
            $availableStocks = Stock::where('name', 'like', '%' . $this->search . '%')
                ->where(function($query) {
                    $query->whereNull('category_id')
                          ->orWhere('category_id', '!=', $this->category->id);
                })
                ->where("status", '1')
                ->limit(10)
                ->get();
        }

        return view('livewire.settings.category.manage-category-stocks', [
            'currentStocks' => $currentStocks,
            'availableStocks' => $availableStocks
        ]);
    }
}
