<?php

namespace App\Livewire\ProductModule;

use App\Models\Stock;
use Livewire\Component;

class ProductBarcodeComponent extends Component
{
    public Stock $product;
    public $selectedBarcode = '';
    public $labelSize = '25x50';
    public $numberOfCopies = 1;

    public array $availableSizes = [
        '25x50' => '25mm x 50mm',
        '30x40' => '30mm x 40mm',
        '30x50' => '30mm x 50mm',
        '40x60' => '40mm x 60mm'
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
            $code = str_pad($this->product->id, 4, '0', STR_PAD_LEFT) . rand(10000000, 99999999);
            
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
