<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Classification;
use Illuminate\Http\Request;

class ClassificationController extends Controller
{
    public function index(){

        return setPageContent('settings.classification.index');
    }

    public function listAll(){
        // Handled by Livewire
    }

    public function stocks(Classification $classification){
        $data = [
            'classification' => $classification
        ];
        return view('settings.classification.stocks', $data);
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
