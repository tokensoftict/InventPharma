<?php

namespace App\Http\Controllers\PromotionManager;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionManagerController extends Controller
{

    public function index()
    {
        $data = [];
        return view('promotion.index', $data);
    }


    public function create()
    {
        $data = [];
        $data['promotion'] = new Promotion();
        return view('promotion.form', $data);
    }


    public function destroy()
    {

    }

    public function show(Promotion $promotion)
    {
        $data = [];
        $data['promotion'] = $promotion;
        return view('promotion.show', $data);
    }

    public function approve()
    {

    }


    public function update(Promotion $promotion)
    {
        $data = [];
        $data['promotion'] = $promotion;
        return view('promotion.form', $data);
    }

}
