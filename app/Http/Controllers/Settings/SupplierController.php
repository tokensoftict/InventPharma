<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupplierController extends Controller
{

    public function index(){

        return setPageContent('settings.supplier.index');
    }


    public function create(){

    }




    public function toggle($id){

    }


    public function update(Request $request, $id){

    }


}
