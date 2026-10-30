<?php

namespace App\Http\Controllers\CustomerManager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(){
        $data = [
            'filters' => []
        ];
        return view('customermanager.index', $data);
    }

    public function retails(){
        $data = [
            'filters' => ['department_type' => 'retail']
        ];
        return view('customermanager.index', $data);
    }


    public function create(){
    }


    public function update(Request $request, $id){
    }

    public function changeorassigncustumertomembergroup(Request $request, $id){
    }
}
