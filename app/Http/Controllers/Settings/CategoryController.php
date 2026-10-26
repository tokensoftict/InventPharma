<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(){

        return setPageContent('settings.category.index');
    }

    public function listAll(){
        // Handled by Livewire
    }

    public function stocks(Category $category){
        $data = [
            'category' => $category
        ];
        return view('settings.category.stocks', $data);
    }


    public function create(){

    }

    public function edit($id){

    }

    public function toggle($id){

    }

    public function update($id){

    }

    public function destroy($id){

    }

    public function store(){

    }
}
