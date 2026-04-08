<?php

namespace App\Livewire\Settings\StockGroup;

use App\Models\Stock;
use App\Models\Stockgroup;
use Livewire\Component;
use Livewire\WithPagination;

class ManageStockGroupStocks extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public Stockgroup $stockgroup;
    public $search = '';
    public $currentSearch = '';

    protected $listeners = ['refreshData' => '$refresh'];

    public function mount(Stockgroup $stockgroup)
    {
        $this->stockgroup = $stockgroup;
    }

    public function addStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock) {
            $stock->stockgroup_id = $this->stockgroup->id;
            $stock->save();


            $this->dispatch('refreshData');
            session()->flash('success', 'Stock added to group successfully.');
        }
    }

    public function removeStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock && $stock->stockgroup_id == $this->stockgroup->id) {
            $stock->stockgroup_id = null;
            $stock->save();

            $this->dispatch('refreshData');
            session()->flash('success', 'Stock removed from group successfully.');
        }
    }

    public function render()
    {
        $currentStocks = $this->stockgroup->stocks();

        if (!empty($this->currentSearch)) {
            $currentStocks->where('name', 'like', '%' . $this->currentSearch . '%');
        }

        $currentStocks = $currentStocks->paginate(10, ['*'], 'currentStocksPage');

        $availableStocks = [];
        if (!empty($this->search)) {
            $availableStocks = Stock::where('name', 'like', '%' . $this->search . '%')
                ->where(function ($query) {
                    $query->whereNull('stockgroup_id')
                        ->orWhere('stockgroup_id', '!=', $this->stockgroup->id);
                })
                ->where("status", '1')
                ->limit(10)
                ->get();
        }

        return view('livewire.settings.stockgroup.manage-stock-group-stocks', [
            'currentStocks' => $currentStocks,
            'availableStocks' => $availableStocks
        ]);
    }
}
