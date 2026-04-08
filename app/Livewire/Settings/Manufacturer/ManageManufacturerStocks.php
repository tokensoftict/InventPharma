<?php

namespace App\Livewire\Settings\Manufacturer;

use App\Models\Stock;
use App\Models\Manufacturer;
use Livewire\Component;
use Livewire\WithPagination;

class ManageManufacturerStocks extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public Manufacturer $manufacturer;
    public $search = '';
    public $currentSearch = '';

    protected $listeners = ['refreshData' => '$refresh'];

    public function mount(Manufacturer $manufacturer)
    {
        $this->manufacturer = $manufacturer;
    }

    public function addStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock) {
            $stock->manufacturer_id = $this->manufacturer->id;
            $stock->save();
            
            // Trigger Kafka sync if the trait method exists
            if (method_exists($stock, 'updateonlinePush')) {
                $stock->updateonlinePush();
            }

            $this->dispatch('refreshData');
            session()->flash('success', 'Stock added to manufacturer successfully.');
        }
    }

    public function removeStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock && $stock->manufacturer_id == $this->manufacturer->id) {
            $stock->manufacturer_id = null;
            $stock->save();

            // Trigger Kafka sync if the trait method exists
            if (method_exists($stock, 'updateonlinePush')) {
                $stock->updateonlinePush();
            }

            $this->dispatch('refreshData');
            session()->flash('success', 'Stock removed from manufacturer successfully.');
        }
    }

    public function render()
    {
        $currentStocks = $this->manufacturer->stocks();

        if (!empty($this->currentSearch)) {
            $currentStocks->where('name', 'like', '%' . $this->currentSearch . '%');
        }

        $currentStocks = $currentStocks->paginate(10, ['*'], 'currentStocksPage');

        $availableStocks = [];
        if (!empty($this->search)) {
            $availableStocks = Stock::where('name', 'like', '%' . $this->search . '%')
                ->where(function($query) {
                    $query->whereNull('manufacturer_id')
                          ->orWhere('manufacturer_id', '!=', $this->manufacturer->id);
                })
                ->where("status", '1')
                ->limit(10)
                ->get();
        }

        return view('livewire.settings.manufacturer.manage-manufacturer-stocks', [
            'currentStocks' => $currentStocks,
            'availableStocks' => $availableStocks
        ]);
    }
}
