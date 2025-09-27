<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class PermitTask
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {

        $group_id =  $request->user()->usergroup_id;

        $myAccess =  accessGroup($group_id);

        //if (!$myAccess) abort('403');

        $validTask = userCanView(Route::currentRouteName());

        session()->put('current_route', Route::currentRouteName());

        if(session()->get('past_page') != Route::currentRouteName() && $group_id !=1)
        {
            session()->put('past_page',Route::currentRouteName());
        }

        //if (!$validTask) abort('403');//throw new UplException('access_denied');

        //$request->user()->update(['last_activity' => Carbon::now()->toDateTimeString()]);

        return $next($request);
    }
}
