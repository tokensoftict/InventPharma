<?php

namespace App\Livewire\ProductScanner;

use App\Models\Stock;
use App\Models\Stockbarcode;
use Livewire\Component;
use App\Traits\LivewireAlert;

class ProductView extends Component
{
    use LivewireAlert;

    public  $product;

    public  $product_id = 7;

    public function boot()
    {

    }

    public function mount()
    {
        $this->product = Stock::find($this->product_id);
    }

    public function render()
    {
        return view('livewire.product-scanner.product-view');
    }


    public function getProductByBarcode($barcode)
    {
        $barcode = trim(preg_replace('/\s\s+/', ' ', $barcode));
        $product = Stockbarcode::where('barcode', $barcode)->first();
        if($product){
            $this->product = $product->stock;
        }
    }

    public function getProductByID($stock)
    {
        $this->product = Stock::find($stock);
    }
}
