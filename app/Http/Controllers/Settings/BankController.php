<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankController extends Controller
{

    public function index(){

        return setPageContent('settings.bank.index');
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
