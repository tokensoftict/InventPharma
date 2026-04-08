<?php

namespace App\Livewire\Settings\Classification;

use App\Models\Stock;
use App\Models\Classification;
use Livewire\Component;
use Livewire\WithPagination;

class ManageClassificationStocks extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public Classification $classification;
    public $search = '';
    public $currentSearch = '';

    protected $listeners = ['refreshData' => '$refresh'];

    public function mount(Classification $classification)
    {
        $this->classification = $classification;
    }

    public function addStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock) {
            $stock->classification_id = $this->classification->id;
            $stock->save();
            
            // Trigger Kafka sync if the trait method exists
            if (method_exists($stock, 'updateonlinePush')) {
                $stock->updateonlinePush();
            }

            $this->dispatch('refreshData');
            session()->flash('success', 'Stock added to classification successfully.');
        }
    }

    public function removeStock($stockId)
    {
        $stock = Stock::find($stockId);
        if ($stock && $stock->classification_id == $this->classification->id) {
            $stock->classification_id = null;
            $stock->save();

            // Trigger Kafka sync if the trait method exists
            if (method_exists($stock, 'updateonlinePush')) {
                $stock->updateonlinePush();
            }

            $this->dispatch('refreshData');
            session()->flash('success', 'Stock removed from classification successfully.');
        }
    }

    public function render()
    {
        $currentStocks = $this->classification->stocks();

        if (!empty($this->currentSearch)) {
            $currentStocks->where('name', 'like', '%' . $this->currentSearch . '%');
        }

        $currentStocks = $currentStocks->paginate(10, ['*'], 'currentStocksPage');

        $availableStocks = [];
        if (!empty($this->search)) {
            $availableStocks = Stock::where('name', 'like', '%' . $this->search . '%')
                ->where(function($query) {
                    $query->whereNull('classification_id')
                          ->orWhere('classification_id', '!=', $this->classification->id);
                })
                ->where("status", '1')
                ->limit(10)
                ->get();
        }

        return view('livewire.settings.classification.manage-classification-stocks', [
            'currentStocks' => $currentStocks,
            'availableStocks' => $availableStocks
        ]);
    }
}
