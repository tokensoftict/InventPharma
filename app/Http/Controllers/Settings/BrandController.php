<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(){

        return setPageContent('settings.brand.index');
    }

    public function listAll(){
        // Handled by Livewire
    }

    public function stocks(Brand $brand){
        $data = [
            'brand' => $brand
        ];
        return view('settings.brand.stocks', $data);
    }
}
