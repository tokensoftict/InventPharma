<?php

namespace App\Http\Controllers\Auth;

use App\Classes\Settings;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;

class LoginController extends Controller
{

    public function index()
    {
        if(auth()->check())   return redirect()->route('dashboard');
        $data['store'] = app(Settings::class)->store();
        return view('auth.login', $data);
    }

    public function loginprocess(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $credentials = $request->except(['_token']);

        $credentials['status'] = 1;

        if (auth()->attempt($credentials)) {

            return redirect()->route('dashboard');

        }else{
            session()->flash('message', 'Invalid Username or Password, Please check and try again');
            return redirect()->back();
        }
    }


    public function logout(){
        auth()->logout();
        return redirect()->route('index');
    }


    public function profile(Request $request)
    {
        if(!auth()->check())   return redirect()->route('home');

        $data['title'] = "My Profile";
        $data['subtitle'] = 'Update Profile or Change Password';

        $data['user'] = auth()->user();

        if($request->method() == "POST")
        {
            $user = $request->only(User::$profile_fields);

            if(!empty($user['password']))
            {
                $user['password'] = bcrypt($user['password']);
            }else
            {
                unset($user['password']);
            }

            $data['user']->update($user);

            return redirect()->route('profile')->with('success','Profile has been updated successfully!');
        }

        return setPageContent('profile',$data);
    }

}
