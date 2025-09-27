<?php

namespace App\Livewire\StockTransfer;

use App\Models\Stock;
use App\Models\Stocktransfer;
use App\Repositories\StockTransferRepository;
use Illuminate\Support\Facades\DB;
use App\Traits\LivewireAlert;
use Livewire\Component;

class StockTransferComponent extends Component
{
    use LivewireAlert;

    public Stocktransfer $stocktransfer;

    public $data;

    public  $from = NULL;

    public  $to = NULL;

    private StockTransferRepository  $stockTransferRepository;

    public function mount()
    {

    }

    public function boot(StockTransferRepository $stockTransferRepository)
    {
        $this->stockTransferRepository = $stockTransferRepository;
    }

    public function booted()
    {
        $this->stocktransfer->from = $this->from;
        $this->stocktransfer->to = $this->to;

        $this->data = StockTransferRepository::stockTransfer($this->stocktransfer);

    }


    public function render()
    {
        if(session()->has('transfer_stock')){
            $transfer_stocks = session()->get('transfer_stock');
            session()->forget('transfer_stock');
            $stocks = Stock::whereIn('id', array_keys($transfer_stocks))->get();
            $trns = [];
            foreach ($stocks as $stock){
                $trns[] = [
                    'stock_id' => $stock->id,
                    'name' => $stock->name,
                    'quantity' => $transfer_stocks[$stock->id],
                    'location' => $stock->location,
                    'selling_price' => $stock->whole_price,
                    'cost_price' => $stock->cost_price,
                    'stockbatch_id' => '',
                    'label_qty' => ($this->to === "retail" || $this->to === "retail_store") ? ($transfer_stocks[$stock->id]."(".$transfer_stocks[$stock->id] * $stock->box.")") : $transfer_stocks[$stock->id],
                    'user_id' => auth()->id(),
                    'total' => $transfer_stocks[$stock->id] * $stock->whole_price
                ];
            }

            $this->data['stocktransferitems'] = json_encode($trns);

        }


        return view('livewire.stock-transfer.stock-transfer-component');
    }


    public function draftTransfer()
    {

        $draft = DB::transaction(function(){

            return $this->stockTransferRepository->saveTransfer($this->stocktransfer, $this->data);
        });

        if(is_array($draft))
        {
            $this->alert(
                "error",
                "Stock Transfer",
                [
                    'position' => 'center',
                    'timer' => 12000,
                    'toast' => false,
                    'text' =>  "An error occurred while drafting this transfer, please check and adjust need items",
                ]
            );

            return ['errors' =>  $draft, 'status'=>false];

        }else {
            $this->alert(
                "success",
                "Stock Transfer",
                [
                    'position' => 'center',
                    'timer' => 6000,
                    'toast' => false,
                    'text' => "Stock Transfer has been drafted successfully!.",
                ]
            );

            return ['status' => true];
        }

    }


    public function completeTransfer()
    {
        $completed = DB::transaction(function(){
            return $this->stockTransferRepository->saveTransfer($this->stocktransfer, $this->data);
        });

        $this->stocktransfer =  $completed;
        if(is_array($completed))
        {
            $this->alert(
                "error",
                "Stock Transfer",
                [
                    'position' => 'center',
                    'timer' => 12000,
                    'toast' => false,
                    'text' =>  "An error occurred while completing this transfer, please check and adjust need items",
                ]
            );

            return ['errors' =>  $completed, 'status'=>false];

        }else {

            $completed = DB::transaction(function(){

                return $this->stockTransferRepository->complete($this->stocktransfer);

            });

            $this->stocktransfer = $completed;

            $this->alert(
                "success",
                "Stock Transfer",
                [
                    'position' => 'center',
                    'timer' => 6000,
                    'toast' => false,
                    'text' =>  "Stock Transfer has been completed successfully!.",
                ]
            );

            return ['status'=>true];
        }

    }

}
