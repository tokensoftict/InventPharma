<?php

namespace App\Livewire\Settings\Brand;

use App\Models\Stock;
use App\Models\Brand;
use Livewire\Component;
use Livewire\WithPagination;

class ManageBrandStocks extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public Brand $brand;
    public $search = '';
    public $currentSearch = '';

    protected $listeners = ['refreshData' => '$refresh'];

    public function mount(Brand $brand)
    {
        $this->brand = $brand;
    }

    public function addStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock) {
            $stock->brand_id = $this->brand->id;
            $stock->save();
            
            // Trigger Kafka sync if the trait method exists
            if (method_exists($stock, 'updateonlinePush')) {
                $stock->updateonlinePush();
            }

            $this->dispatch('refreshData');
            session()->flash('success', 'Stock added to brand successfully.');
        }
    }

    public function removeStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock && $stock->brand_id == $this->brand->id) {
            $stock->brand_id = null;
            $stock->save();

            // Trigger Kafka sync if the trait method exists
            if (method_exists($stock, 'updateonlinePush')) {
                $stock->updateonlinePush();
            }

            $this->dispatch('refreshData');
            session()->flash('success', 'Stock removed from brand successfully.');
        }
    }

    public function render()
    {
        $currentStocks = $this->brand->stocks();

        if (!empty($this->currentSearch)) {
            $currentStocks->where('name', 'like', '%' . $this->currentSearch . '%');
        }

        $currentStocks = $currentStocks->paginate(10, ['*'], 'currentStocksPage');

        $availableStocks = [];
        if (!empty($this->search)) {
            $availableStocks = Stock::where('name', 'like', '%' . $this->search . '%')
                ->where(function($query) {
                    $query->whereNull('brand_id')
                          ->orWhere('brand_id', '!=', $this->brand->id);
                })
                ->where("status", '1')
                ->limit(10)
                ->get();
        }

        return view('livewire.settings.brand.manage-brand-stocks', [
            'currentStocks' => $currentStocks,
            'availableStocks' => $availableStocks
        ]);
    }
}
