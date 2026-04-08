<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Stockgroup;
use Illuminate\Http\Request;

class StockGroupController extends Controller
{
    public function index(){

        return setPageContent('settings.stockgroup.index');
    }

    public function listAll(){
        // This is usually handle by livewire, but route exists
    }

    public function stocks(Stockgroup $stockgroup){
        $data = [
            'stockgroup' => $stockgroup
        ];
        return view('settings.stockgroup.stocks', $data);
    }
}
