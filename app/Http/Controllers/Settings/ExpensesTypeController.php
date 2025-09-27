<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExpensesTypeController extends Controller
{
    public function index(){
        return setPageContent('settings.expenses_type.index');
    }
    public function create(){}

    public function toggle($id){}

    public function update(Request $request, $id){}

    public function destroy($id){}
}
