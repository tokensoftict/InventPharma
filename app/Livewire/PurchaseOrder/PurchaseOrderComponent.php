<?php

namespace App\Livewire\PurchaseOrder;

use App\Models\Purchase;
use App\Repositories\PurchaseOrderRepository;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\LivewireAlert;

class PurchaseOrderComponent extends Component
{
    use LivewireAlert;

    public Purchase $purchase;

    public array $data;

    public $suppliers;

    public $depertments;

    protected PurchaseOrderRepository $purchaseOrderRepository;

    public function boot(PurchaseOrderRepository $purchaseOrderRepository)
    {
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }

    public function mount()
    {
        $this->data = PurchaseOrderRepository::purchaseOrder($this->purchase);

    }

    public function booted()
    {
        $this->suppliers = suppliers(true);
        $this->depertments = departments(true)->filter(function($item){
            if(in_array(auth()->user()->department_id, [1,2,3,5])){
                return $item->id === 1 || $item->id == 4 || $item->id == 6;
            }
            return auth()->user()->department_id === 4;
        });

        if(config('app.PURCHASE_DEPARTMENT') !== false) {
            $this->depertments = department_by_ids(explode(",", config('app.PURCHASE_DEPARTMENT')));
        }
    }

    public function render()
    {
        return view('livewire.purchase-order.purchase-order-component');
    }


    public function draftPurchase()
    {
        $this->data['completed_by'] = NULL;
        $this->data['date_completed'] = NULL;
        DB::transaction(function (){
            $this->purchase = $this->purchaseOrderRepository->savePurchaseOrder($this->purchase, $this->data);
        });

        $this->alert(
            "success",
            "Purchase Order",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' =>  "Purchase Order has been drafted successfully!.",
            ]
        );

        return ['status'=>true];

    }

    public function completePurchase()
    {
        $this->data['status_id'] = status('Draft');

        DB::transaction(function (){
            $this->purchase = $this->purchaseOrderRepository->savePurchaseOrder($this->purchase, $this->data);

            $this->purchaseOrderRepository->complete($this->purchase->fresh());
        });
        $this->alert(
            "success",
            "Purchase Order",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' =>  "Purchase Order has been completed successfully!.",
            ]
        );


        return ['status'=>true];

    }

}
