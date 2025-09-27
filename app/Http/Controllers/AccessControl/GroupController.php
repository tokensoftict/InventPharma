<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Usergroup;
use Illuminate\Http\Request;




class GroupController extends Controller
{

    public function index()
    {
        return setPageContent('accesscontrol.usergroup.index');
    }

    public function create()
    {

    }


    public function toggle()
    {

    }

    public function update(Request $request, $id)
    {

    }


    public function destroy($id)
    {
        //
    }

    public function permission(Usergroup $group)
    {

        $data = [
            'usergroup' => $group
        ];
        return view('accesscontrol.usergroup.permission-user-group', $data);
    }

}
