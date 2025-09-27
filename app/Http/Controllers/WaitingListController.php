<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WaitingListController extends Controller
{

    public function __invoke(Request $request)
    {
        return $this->load();
    }

    private function load(){
        return view('waitinglist.customerWaitingList');
    }
}
