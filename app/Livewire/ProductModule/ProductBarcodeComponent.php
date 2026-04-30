<?php

namespace App\Livewire\ProductModule;

use App\Models\Stock;
use Livewire\Component;

class ProductBarcodeComponent extends Component
{
    public Stock $product;
    public $selectedBarcode = '';
    public $labelSize = '50x25';
    public $numberOfCopies = 1;

    public array $availableSizes = [
        '50x25' => '50mm x 25mm',
        '40x30' => '40mm x 30mm',
        '50x30' => '50mm x 30mm',
        '60x40' => '60mm x 40mm'
    ];

    public function mount()
    {
        // Select the first barcode by default if available
        if (isset($this->product) && $this->product->stockbarcodes && $this->product->stockbarcodes->count() > 0) {
            $this->selectedBarcode = $this->product->stockbarcodes->first()->barcode;
        }
    }

    public function generateBarcode()
    {
        if (isset($this->product)) {
            // Generate a 12-digit random barcode based on product ID
            $code = mt_rand(1000000, 9999999);
            
            $barcode = new \App\Models\Stockbarcode([
                'barcode' => $code,
                'user_id' => auth()->id() ?? 1,
            ]);
            
            $this->product->stockbarcodes()->save($barcode);
            
            // Reload relation so the UI updates
            $this->product->load('stockbarcodes');
            $this->selectedBarcode = $code;
        }
    }

    public function render()
    {
        return view('livewire.product-module.product-barcode-component');
    }
}
