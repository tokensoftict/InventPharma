<?php

namespace App\Livewire\ProductModule\Batch;

use App\Jobs\AddLogToProductBinCard;
use App\Models\Batchstock;
use App\Models\Stock;
use Livewire\Component;
use App\Traits\LivewireAlert;
use App\Models\Stockbatch as batch;

class StockBatch extends Component
{

    public Stock $stock;

    public array $batches = [];

    public string $selectedDepartment;

    public string $dept;

    use LivewireAlert;


    public function boot()
    {

    }

    public function mount()
    {

        $batches = batch::where("stock_id", $this->stock->id)
            ->where(function($q){
                $q->orwhere("wholesales",">",0)
                    ->orwhere("bulksales",">",0)
                    ->orwhere("retail",">",0)
                    ->orwhere("quantity",">",0);
            })

            ->get();

        if($batches->count() == 0){
            $batches = batch::where("stock_id",$this->stock->id)->orderBy('id','DESC')->limit(1)->get();
        }

        $this->batches = $batches->toArray();

        $this->dept = department_by_quantity_column($this->selectedDepartment)->label;

    }

    public function render()
    {
        $data = [
           'suppliers' =>  suppliers(true)
        ];
        return view('livewire.product-module.batch.stock-batch', $data);
    }

    public function batchStock()
    {
        $error = false;

        foreach ($this->batches as $key=>$batch)
        {
            unset( $this->batches[$key]['error']);

            if($batch[cost_price_column(department_by_quantity_column($this->selectedDepartment)->id)] == NULL || $batch[cost_price_column(department_by_quantity_column($this->selectedDepartment)->id)] < 1)
            {
                $this->batches[$key]['error'] = "Please update this cost price to adjust quantity";

                $error = true;
            }
        }

        if($error === false) {
            $b_whole = $this->stock->wholesales;
            $b_retail = $this->stock->retail;
            $b_quantity = $this->stock->quantity;
            $b_bulksales = $this->stock->bulksales;
            $b_retail_store_qty = $this->stock->retail_store;


            foreach ($this->batches as $key => $batch) {
                $b = batch::find($batch['id']);
                $b->update($batch);
            }


            $user_col_map = [
                'quantity' => 'quantity_user_id',
                'bulksales' => 'bulk_user_id',
                'wholesales' => 'wholsale_user_id',
                'retail' => 'retail_user_id',
                'retail_store' => 'retail_user_id',
            ];

            $this->stock->updateQuantity();

            if (isset($this->stock->batchstock->stock_id)) {
                $col = $user_col_map[$this->selectedDepartment];
                $this->stock->batchstock->{$this->selectedDepartment} = 1;
                $this->stock->batchstock->$col = auth()->id();
                $this->stock->batchstock->update();
            } else {
                $c = [
                    'stock_id' => $this->stock->id,
                    'quantity' => 0,
                    'wholesales' => 0,
                    'bulksales' => 0,
                    'retail' => 0,
                    'retail_store' => 0,
                ];
                $c[$this->selectedDepartment] = 1;

                $c[$user_col_map[$this->selectedDepartment]] = \auth()->id();

                Batchstock::create($c);
            }

            $bincards[] = [
                'bin_card_type' => 'APP//BATCH_UPDATE',
                'bin_card_date' => date('Y-m-d'),
                'user_id' => auth()->id(),
                'stock_id' => $this->stock->id,
                'out_qty' => 0,
                'comment' => "Stock Batch Update was made
                                <br/>-----BEFORE UPDATE------<br/>
                                Wholesale : $b_whole, Bulk : $b_bulksales
                                , Retail : 
                                ,SuperMarket Store : $b_retail_store_qty 
                                , Main Store : $b_quantity
                                <br/>
                                <br/>-----AFTER UPDATE------<br/>
                                  Wholesale : " . $this->stock->wholesales . ", Bulk : " . $this->stock->bulksales . ", Retail : " . $this->stock->retail. ", SuperMarket Store: " . $this->stock->retail_store . ", Main Store : " . $this->stock->quantity . "
                                ",
                'balance' => $this->stock->totalBalance(),
                'department_balance' => $this->stock->getCurrentlevel($this->selectedDepartment)
            ];


            dispatch(new AddLogToProductBinCard($bincards));

            $this->alert(
                "success",
                "Quick Adjust Quantity",
                [
                    'position' => 'center',
                    'timer' => 6000,
                    'toast' => false,
                    'text' => "Stock Quantity has been updated successfully!",
                ]
            );

            return redirect()->route('product.balance_stock');
        }
    }
}
