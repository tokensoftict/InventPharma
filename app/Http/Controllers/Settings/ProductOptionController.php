<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Option;
use App\Models\OptionField;
use Illuminate\Http\Request;

class ProductOptionController extends Controller
{
    public function index(){
        return view('settings.product_option.index',);
    }
    public function create(){}

    public function toggle($id){}

    public function update(Request $request, $id){}

    public function destroy($id){}

    public function view_values($id)
    {
        $options = OptionField::find($id);
        return view('settings.product_option.view_values', compact('options'));
    }

    public function toggle_product_option_fields($id){}
    public function update_product_option_fields($id){}
}
