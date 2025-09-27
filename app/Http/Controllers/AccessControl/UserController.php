<?php

namespace App\Http\Controllers\AccessControl;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;


class UserController extends Controller
{

    public function index()
    {

        return view('accesscontrol.user.index');
    }


    public function create()
    {

    }


    public function update(Request $request, $id)
    {


    }


    public function toggle($id)
    {

    }

    public function edit()
    {

    }


}
