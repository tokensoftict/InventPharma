<?php

namespace App\Livewire\PurchaseOrder\Show;


use App\Models\Purchase;
use App\Repositories\PurchaseOrderRepository;
use App\Traits\SimpleComponentTrait;
use Livewire\Component;

class ShowPurchaseOrder extends Component
{

    use SimpleComponentTrait;

    public Purchase $purchase;

    public string $title;

    public string $subtitle;

    public function render()
    {
        return view('livewire.purchase-order.show.show-purchase-order' );
    }


    public function complete()
    {
        (new PurchaseOrderRepository())->complete($this->purchase);

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

        return redirect()->route('purchase.show', $this->purchase->id);
    }


    public function delete()
    {
        (new PurchaseOrderRepository())->delete($this->purchase);

        $this->alert(
            "success",
            "Purchase Order",
            [
                'position' => 'center',
                'timer' => 6000,
                'toast' => false,
                'text' =>  "Purchase Order has been deleted successfully!.",
            ]
        );

        return redirect()->route('purchase.index');
    }

}
