<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class PrescriberController extends Controller
{
    public function index(){

        return setPageContent('settings.prescribers.index');
    }


    public function create(){

    }


    public function toggle($id){

    }



    public function update(Request $request, $id){


    }

}
