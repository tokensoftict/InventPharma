<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
{
    public function index(){

        return setPageContent('settings.manufacturer.index');
    }


    public function create(){

    }




    public function toggle($id){



    }




    public function update(Request $request, $id){


    }


    public function destroy($id){


    }
}
