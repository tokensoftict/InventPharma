<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class CustomPriceController extends Controller
{
    public function index(){

        return setPageContent('settings.custom_price.index');
    }


    public function create(){

    }


    public function toggle($id){

    }

    public function toggle_default_price($id){

    }



    public function update(Request $request, $id){


    }

}
