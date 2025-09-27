<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductScannerController extends Controller
{

    public function __invoke()
    {
        return $this->load();
    }


    private function load(){
        return view('scanner.product-view');
    }

}
