<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Manufacturer;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
{
    public function index(){

        return setPageContent('settings.manufacturer.index');
    }

    public function listAll(){
        // Handled by Livewire
    }

    public function stocks(Manufacturer $manufacturer){
        $data = [
            'manufacturer' => $manufacturer
        ];
        return view('settings.manufacturer.stocks', $data);
    }
}
