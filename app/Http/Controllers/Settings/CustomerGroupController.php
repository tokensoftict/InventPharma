<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function index(){

        return setPageContent('settings.customer-group.index');
    }

    public function listAll(){
        // Handled by Livewire
    }

    public function create(){}

    public function show(){}

    public function store(){}

    public function edit(){}

    public function toggle($id){}

    public function update(Request $request, $id){}

    public function destroy($id){}



}
