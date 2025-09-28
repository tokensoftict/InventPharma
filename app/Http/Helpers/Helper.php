<?php


use App\Models\Stock;
use App\Models\Usergroup;
use App\Models\WaitingCustomer;
use App\Models\WaitingCustomersHistory;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Classes\Settings;
use Spatie\Valuestore\Valuestore;

function onlineBase($endpoint = ""){

    //return  config('app.online_store_url')."api/data/".$endpoint;
    return  'https://admin.generaldrugcentre.com/api/data/'.$endpoint;
}

function divide($num1, $num2)
{
    if($num1 == 0 || $num2 == 0) return 0;

    return ($num1/$num2);
}

function  _GET($endpoint) : array|bool
{
    if(config('app.sync_with_online')== 0)  return false;

    $response = Http::timeout(config("app.push_to_server_timeout"))
        ->withHeaders(['Accept'=>'application/json'])
        ->get(onlineBase($endpoint));
    if($response->status() == 200 )
    {
        return json_decode($response->body(), true) ??  true;
    }
    return false;
}

function _FETCH($url) : array|bool
{
    if(config('app.sync_with_online')== 0)  return false;

    $response = Http::timeout(config("app.push_to_server_timeout"))
        ->withHeaders(['Accept'=>'application/json'])
        ->get($url);

    if($response->status() == 200 )
    {
        return json_decode($response->body(), true) ??  true;
    }
    return false;
}

function _POST($endpoint, $payload = []) : array|bool
{
    if(config('app.sync_with_online')== 0)  return false;

    $response =   Http::timeout(config("app.push_to_server_timeout"))
        ->withHeaders(['Accept'=>'application/json'])
        ->post(onlineBase($endpoint), $payload);

    if($response->status() == 200 )
    {
        return json_decode($response->body(), true) ??  true;
    }

    Storage::disk('local')->append('bulk-logs', $response->body(), null);

    return false;
}

function _POST2($endpoint, $payload = []) : array|bool
{
    if(config('app.sync_with_online')== 0)  return false;

    $response =   Http::timeout(config("app.push_to_server_timeout"))
        ->withHeaders(['Accept'=>'application/json'])
        ->post(onlineBase($endpoint), $payload);

    if($response->status() == 200 )
    {
        return json_decode($response->body(), true) ??  true;
    }

    return $response->body();

}

function _RAWPOST($url, $payload =[]) : \Illuminate\Http\Client\Response
{
    return Http::timeout(config("app.push_to_server_timeout"))
        ->withHeaders(['Accept'=>'application/json'])
        ->post($url, $payload);
}


function uploadFile($url, array $file, $payload)
{
    return Http::timeout(config("app.push_to_server_timeout"))
        ->attach($file['name'], $file['file'], $file['label'])
        ->post($url, $payload);
}

if (!function_exists('isJson')) {
    function isJson($string)
    {
        if(is_array($string) || is_object($string)) return true;
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}


function generateRandom($length = 25) {
    $characters = 'abcdefghijklmnopqrstuvwxyz_';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function loadUserMenu($group_id = NULL)
{
    $group_id = $group_id === NULL ? auth()->user()->usergroup_id : $group_id;

    return Cache::remember('route-permission-'.$group_id,86400, function() use ($group_id){
        return \App\Models\Usergroup::with(['tasks'=>function ($q) {
            $q->join('modules', 'modules.id', '=', 'tasks.module_id');
            $q->orderBy('tasks.module_id', "ASC")->orderBy('tasks.id');
        },'permissions','users','tasks','group_tasks','tasks.module'])->find($group_id)->tasks;
    });
}

function accessGroups()
{
    return Cache::remember('usergroups',86400, function(){
        return Usergroup::where('status', 1)->get();
    });
}

function accessGroup($group_id)
{
    $group_id = $group_id === NULL ? auth()->user()->usergroup_id : $group_id;

    return accessGroups()->filter(function($item) use($group_id){
        return $item->id === $group_id;
    })->first();
}

function paymentmethods($active = false)
{
    $paymentmethods =  Cache::remember('paymentmethods', 86400, function(){
        return  DB::table('paymentmethods')->get();
    });

    if($active === true) return $paymentmethods->filter(function($item){
        return $item->status == true;
    });

    return $paymentmethods;
}

function paymentmethodsOnly($only = [])
{
    return paymentmethods(true)->filter(function($method) use ($only){
        return  in_array($method->name, $only) || in_array($method->id, $only);
    });
}

function manufacturers($active = false)
{
    $manus =  Cache::remember('manufacturers', 86400, function(){
        return DB::table('manufacturers')->get();
    });

    if($active === true) return $manus->filter(function($item){
        return $item->status == true;
    });

    return $manus;
}


function brands($active = false)
{
    $manus =  Cache::remember('brands', 86400, function(){
        return DB::table('brands')->get();
    });

    if($active === true) return $manus->filter(function($item){
        return $item->status == true;
    });

    return $manus;
}

function categories($active = false)
{
    $manus =  Cache::remember('categories', 86400, function(){
        return DB::table('categories')->get();
    });

    if($active === true) return $manus->filter(function($item){
        return $item->status == true;
    });

    return $manus;
}

function classifications($active = false)
{
    $manus =  Cache::remember('classifications', 86400, function(){
        return DB::table('classifications')->get();
    });

    if($active === true) return $manus->filter(function($item){
        return $item->status == true;
    });

    return $manus;
}


function departments($active = false)
{
    $depts =  Cache::remember('departments', 86400, function(){
        return DB::table('departments')->get();
    });

    if($active === true) return $depts->filter(function($item){
        return $item->status == true;
    });

    return $depts;
}



function department_by_id($id)
{
    return departments(true)->filter(function($item) use($id){
        return $item->id === $id;
    })->first();
}

function department_by_ids(int | array $id)
{
    return departments(true)->filter(function($item) use($id){
        if(is_array($id)) {
            return in_array($item->id, $id);
        } else {
            return $item->id === $id;
        }
    });
}

function department_by_quantity_column($name, $active = true)
{
    return departments($active)->filter(function($item) use($name){
        return $item->quantity_column === $name;
    })->first();
}

function cost_price_column($department_id = false){

    if($department_id === false)
    {
        $department_id = auth()->user()->department_id;
    }

    return  match (department_by_id($department_id)->quantity_column){
        'quantity', 'wholesales', 'bulksales', NULL => 'cost_price',
        'retail', 'retail_store' => 'retail_cost_price',
    };
}

function selling_price_column($department_id = false)
{
    if($department_id === false)
    {
        $department_id = auth()->user()->department_id;
    }
    return department_by_id( $department_id )->price_column;
}

function stockgroups($active = false)
{
    $depts =  Cache::remember('stockgroups', 86400, function(){
        return DB::table('stockgroups')->get();
    });

    if($active === true) return $depts->filter(function($item){
        return $item->status == true;
    });

    return $depts;
}


function suppliers($active = false)
{
    $suppliers =  Cache::remember('suppliers', 86400, function(){
        return DB::table('suppliers')->get();
    });

    if($active === true) return $suppliers->filter(function($item){
        return $item->status == true;
    });

    return $suppliers;
}


function usergroups($active = false)
{
    $usergroups =  Cache::remember('usergroups', 86400, function(){
        return DB::table('usergroups')->get();
    });

    if($active === true) return $usergroups->filter(function($item){
        return $item->status == true;
    });

    return $usergroups;
}


function bank_accounts($active = false)
{
    $bank_accounts =  Cache::remember('bank_accounts', 85400, function(){
        return   DB::table('bank_accounts')->select("banks.name","bank_accounts.bank_id","bank_accounts.account_number","bank_accounts.account_name",'bank_accounts.status','bank_accounts.id')->leftJoin('banks','bank_accounts.bank_id','=','banks.id')->get();
    });

    if($active === true) return $bank_accounts->filter(function($item){
        return $item->status == true;
    });

    return $bank_accounts;
}

function banks()
{
    return Cache::remember('banks', 85400, function(){
        return DB::table('banks')->get();
    });
}


function myAccessGroup()
{
    return request()->user()->usergroup;
}


function getUserMenu()
{


    $groupMenu = loadUserMenu();

    $userMenus = ' <li class="'.("dashboard" === \Route::currentRouteName() ? 'active' : '').'"><a href="' . route("dashboard") . '"> <i data-feather="home"></i><span data-key="t-dashboard">Dashboard</span></a></li>';

    if ($groupMenu) {
        $lastModule = '';
        $isFirstRun = true;

        foreach ($groupMenu as $menu) {
            if(in_array($menu->module_id, Settings::$reports)) continue;

            $taskIcon = !empty($menu->icon) ? $menu->icon : "fa fa-arrow-right";
            if ($lastModule != $menu->module_id) {
                if ($lastModule != '' && !$isFirstRun) {
                    $userMenus .= '</ul></li>';
                }
                $isFirstRun = false;
                $userMenus .= '<li ><a '.(request()->route()->getPrefix()=== strtolower($menu->module->name) ? 'class="has-arrow mm-active"' : 'class="has-arrow"').' href="javascript:void(0);">
                <i data-feather="'.$menu->module->icon.'"></i>
                <span data-key="t-credit-card"> '.$menu->module->label.'</span>
                </a><ul>';
            }
            if ($menu->visibility) $userMenus .= '<li><a class="'.($menu->route === \Route::currentRouteName() ? 'active' : '').'" href="' . route($menu->route) . '">' . $menu->name . '</a></li>';
            $lastModule = $menu->module_id;
        }

        if (!$isFirstRun) {
            $userMenus .= '</ul></li>';
            $userMenus .= ' <li class="'.(request()->route()->getPrefix()=== 'reports').'"><a href="' . route("reports") . '"> <i data-feather="bar-chart"></i><span data-key="t-chart">Reports</span></a></li>';

            $userMenus .= ' <li class="'.(request()->route() === 'profile').'"><a href="' . route("profile") . '"> <i data-feather="user"></i><span data-key="t-chart">My Profile</span></a></li>';


            $userMenus .= ' <li class="'.(request()->route() === 'logout').'"><a href="' . route("logout") . '"> <i data-feather="lock"></i><span data-key="t-chart">Log Out</span></a></li>';
        }

    }

    return $userMenus;
}

/*
function getUserMenu()
{


    $groupMenu = loadUserMenu();

    $userMenus = ' <li class="'.("dashboard" === \Route::currentRouteName() ? 'active' : '').'"><a href="' . route("dashboard") . '"><img src="'.asset('img/icons/dashboard.svg').'"/><span>Dashboard</span></a></li>';

    if ($groupMenu) {
        $lastModule = '';
        $isFirstRun = true;

        foreach ($groupMenu as $menu) {
            $taskIcon = !empty($menu->icon) ? $menu->icon : "fa fa-arrow-right";
            if ($lastModule != $menu->module_id) {
                if ($lastModule != '' && !$isFirstRun) {
                    $userMenus .= '</ul></li>';
                }
                $isFirstRun = false;
                $userMenus .= '<li class="submenu"><a '.(request()->route()->getPrefix()=== strtolower($menu->module->name) ? 'class="active subdrop"' : "").' href="javascript:void(0);">
                <img src="'.asset('img/icons/'. $menu->module->icon).'" alt="img">
                <span> '.$menu->module->label.'</span>
                <span class="menu-arrow"></span>
                </a><ul>';
            }
            if ($menu->visibility) $userMenus .= '<li><a class="'.($menu->route === \Route::currentRouteName() ? 'active' : '').'" href="' . route($menu->route) . '">' . $menu->name . '</a></li>';
            $lastModule = $menu->module_id;
        }
        if (!$isFirstRun) {
            $userMenus .= '</ul></li>';
        }
    }


    return $userMenus;
}
*/

/**
 * Fetch the list of tasks the user is assigned.
 *
 * @return mixed
 */
function userPermissions()
{
    return loadUserMenu();
}

/**
 * Check whether a user has access to a particular route.
 *
 * @param $route
 * @return mixed
 */
function userCanView($route)
{
    $route = trim($route);
    return userPermissions()->contains(function ($task, $key) use ($route) {
        return $task->route == $route;
    });
}

function type() : string
{
    return auth()->user()->department_id == 4 ? 'retailsales.' : 'invoiceandsales.';
}

function str_plural($name){
    return Str::plural($name);
}

/**
 * Set Controller Default Layout And Render Content
 *
 * @param string $content
 * @return \Illuminate\Http\Response
 */
function setPageContent($pageblade, $data = array(), $layout = 'layouts.app')
{
    return view($layout, ['content' => view($pageblade, $data)]);
}

function getCurrentPeriod()
{
    return \App\Models\PayrollPeriod::where("current",1)->first() ?? false;
}

function getStoreSettings(){

    return json_decode(json_encode(Valuestore::make(storage_path('app/settings.json'))->all()));
}

function month_year($time = false, $pad = false)
{
    if (!$time) $time = time() + time_offset();
    else $time = strtotime($time);
    if ($pad) $pad = ". h:i:s A";
    else $pad = "";
    return date('F, Y' . $pad, $time);
}

function time_offset()
{
    return 0;
}

function eng_str_date($time = false, $pad = false)
{
    if (!$time) $time = time() + time_offset();
    else $time = strtotime($time);
    if ($pad) $pad = ". h:i:s A";
    else $pad = "";
    return date('d/m/Y' . $pad, $time);
}

function human_date($date){
    return (new Carbon($date))->format('F jS, Y');
}

function twentyfourHourClock($time)
{
    return  date('H:i', strtotime($time));
}

function twelveHourClock($time)
{
    return  date('h:i A', strtotime($time));
}

function mysql_str_date($time = false, $pad = false)
{
    if (!$time) $time = time() + time_offset();
    else $time = strtotime($time);
    if ($pad) $pad = ". h:i:s A";
    else $pad = "";
    return date('Y-m-d' . $pad, $time);
}

function str_date($time = false, $pad = false)
{
    if (!$time) $time = time() + time_offset();
    else $time = strtotime($time);
    if ($pad) $pad = ". h:i:s A";
    else $pad = "";
    return date('l, F jS, Y' . $pad, $time);
}

function str_date2($time = false, $pad = false)
{
    if(!$time) return NULL;
    if (!$time) $time = time() + time_offset();
    else $time = strtotime($time);
    if ($pad) $pad = ". h:i:s A";
    else $pad = "";
    return date('D, F jS, Y' . $pad, $time);
}

function format_date($date, $withTime = TRUE)
{
    if ($date == "0000-00-00 00:00:00") {
        return "Never";
    }

    $date = trim($date);
    $retVal = "";
    $date_time_array = explode(" ", $date);
    $time = $date_time_array[1];
    $time_array = explode(":", $time);

    $date_array = explode("-", "$date");
    $day = $date_array['2'];
    $month = $date_array['1'];
    $year = $date_array['0'];
    if ($year > 0) {
        @ $ddate = mktime(12, 12, 12, $month, $day, $year);
        @ $retVal = date("j M Y", $ddate);
    }

    if (!empty($time)) {
        $hr = $time_array[0];
        $min = $time_array[1];
        $sec = $time_array[2];
        @ $ddate = mktime($hr, $min, $sec, $month, $day, $year);
        @ $retVal = date("j M Y, H:i", $ddate);
        if (!$withTime) {
            @ $retVal = date("j M Y", $ddate);
        }
    }

    return $retVal;
}

function restructureDate($date_string)
{
    if (strtotime($date_string)) return $date_string;

    if (str_contains($date_string, "/")) {
        if (strtotime(str_replace("/", "-", $date_string))) return str_replace("/", "-", $date_string);

        // TODO: try to change the date format to make it easier for the system to parse
    }

    return $date_string;
}

function render($type = "append")
{
    echo "@render:$type=out>>";
}

function json_success($data)
{
    return json_status(true, $data, 'success');
}

function json_failure($data, $code_name)
{
    return json_status(false, $data, $code_name);
}

function response_array_failure($data, $code_name)
{
    return response_array_status(false, $data, $code_name);
}

function json_status($status, $data, $code_name)
{

    $response = response_array_status($status, $data, $code_name);

    return json($response);
}

function response_array_status($status, $data, $code_name)
{
    if (!$statuses = config("statuses." . $code_name)) $statuses = config("statuses.unknown");

    $response = [
        'status' => $status,
        'status_code' => $statuses[0],
        'message' => $statuses[1],
    ];

    if ($status) {
        if (is_bool($data)) $data = ($data) ? "true" : "false";

        $response['data'] = $data;
        $response['validation'] = null;
        return $response;
    }

    $response['data'] = null;
    $response['validation'] = $data;
    return $response;
}

function json($response)
{
    return response()->json($response);
}

function normal_case($str)
{
    return ucwords(str_replace("_", " ", Str::snake(str_replace("App\\", "", $str))));
}

function getPaginate()
{
    return Request::get('paginate');
}

function status($status){

    /*
    if(is_array($status))
       return  \App\Models\Status::whereIn('name',$status)->pluck('id');
    else
        return \App\Models\Status::where('name',$status)->first()->id;
    */
    if(is_numeric($status))

        $st = statuses()->filter(function ($item) use($status){
            return $item->id == $status;
        });
    else

        $st = statuses()->filter(function ($item) use($status){
            return $item->name == $status;
        });


    return $st->first()->id;
}

function status_name($status){

    /*
    if(is_array($status))
       return  \App\Models\Status::whereIn('name',$status)->pluck('id');
    else
        return \App\Models\Status::where('name',$status)->first()->id;
    */
    if(is_numeric($status))

        $st = statuses()->filter(function ($item) use($status){
            return $item->id == $status;
        });
    else

        $st = statuses()->filter(function ($item) use($status){
            return $item->name == $status;
        });


    return $st->first()->name;
}

function showStatus($status)
{
    if(is_numeric($status))
        //$st = \App\Models\Status::find($status);
        $st = statuses()->filter(function ($item) use($status){
            return $item->id == $status;
        });
    elseif(is_object($status))
        $st = statuses()->filter(function ($item) use($status){
            return $item->id == $status->id;
        });
    else
        $st = statuses()->filter(function ($item) use($status){
            return $item->name == $status;
        });

    $st = $st->first();

    if(!$st) return label($status);

    return label($st->name,$st->label);
}


function showAddToWaitingStatus(bool $status)
{
    if($status === true) {
        return  label('Yes','success');
    }
    return  label('No','danger');
}


function showWaitingStatus($status)
{
    if($status === \App\Models\WaitingCustomer::$waitingInvoiceStatus['waiting']) {
        return  label( \App\Models\WaitingCustomer::$waitingInvoiceStatus['waiting'],'warning');
    } else if($status === \App\Models\WaitingCustomer::$waitingInvoiceStatus['packing']) {
        return  label(\App\Models\WaitingCustomer::$waitingInvoiceStatus['packing'],'primary');
    } else if($status === \App\Models\WaitingCustomer::$waitingInvoiceStatus['packed']) {
        return  label(\App\Models\WaitingCustomer::$waitingInvoiceStatus['packed'],'success');
    }else if($status === \App\Models\WaitingCustomer::$waitingInvoiceStatus['picking']) {
        return  label(\App\Models\WaitingCustomer::$waitingInvoiceStatus['picking'],'primary');
    } else {
        return  label('Unknown','default');
    }
}


function statuses()
{
    return Cache::remember('status',144000,function(){
        return \App\Models\Status::all();
    });
}


function _status(\App\Models\Status $status)
{
    return label($status->name,$status->label);
}

function label($text, $type = 'default', $extra = 'sm')
{
    return '<span class="font-size-12 badge badge-soft-' . $type . '">' . $text . '</span>';
}

function alert_success($msg)
{
    return alert('success', $msg);
}

function alert_info($msg)
{
    return alert('info', $msg);
}

function alert_warning($msg)
{
    return alert('warning', $msg);
}

function error($msg) : string
{
    return '<span class="text-danger d-block">'.$msg.'</span>';
}

function alert_error($msg)
{
    return alert('danger', $msg);
}

function alert($status, $msg)
{
    return '<div class="alert alert-' . $status . '">' . $msg . '</div>';
}

function money($amt)
{
    return number_format($amt, 2);
}

function show_promo_init(Stock $stock, $column){
    if($stock->has_promo && $stock->promotion_item->{$column} > 0){
        return '<span>'.money($stock->{$column}).'</span>'.'&nbsp;&nbsp;&nbsp;'.'<span style="text-decoration: line-through;color:red">'.money($stock->getRawOriginal($column));
    }
    return money($stock->{$column});
}


function show_promo(Stock $stock, $column) {
    if($stock->has_promo) {
        $promo = $stock->promotion_items->filter(function ($item) use ($column) {
            return $item->{$column} > 0 and $item->status_id === status('Approved');
        })->first();

        if ($promo) {
            return '<span>' . money($promo->{$column}) . '</span>' . '&nbsp;&nbsp;&nbsp;' . '<span style="text-decoration: line-through;color:red">' . money($stock->getRawOriginal($column));
        }
    }
    return money($stock->{$column});
}

/**
 * Return a capitalised string
 *
 * @return string
 * @param string $string
 */
function toCap($string)
{
    return strtoupper(strtolower($string));
}

/**
 * Return a small letter string
 *
 * @return string
 * @param string $string
 */
function toSmall($string)
{
    return strtolower($string);
}

/**
 * Return a sentence case string
 *
 * @return string
 * @param string $string
 */
function toSentence($string)
{
    return ucwords(strtolower($string));
}

function generateRandomString($randStringLength)
{
    $timestring = microtime();
    $secondsSinceEpoch = (integer)substr($timestring, strrpos($timestring, " "), 100);
    $microseconds = (double)$timestring;
    $seed = mt_rand(0, 1000000000) + 10000000 * $microseconds + $secondsSinceEpoch;
    mt_srand($seed);
    $randstring = "";
    for ($i = 0; $i < $randStringLength; $i++) {
        $randstring .= mt_rand(0, 9);
    }
    return ($randstring);
}


/**
 * Get IDs of the Work Groups this User has been granted permission to work on.
 * @return array
 */



function getRandomString_AlphaNum($length)
{
    //Init the pool of characters by category
    $pool[0] = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    $pool[1] = "23456789";
    return randomString_Generator($length, $pool);
}   //END getRandomString_AlphaNum()


function randomString_Num($length)
{
    //Init the pool of characters by category
    $pool[0] = "0123456789";
    return randomString_Generator($length, $pool);
}

function getRandomString_AlphaNumSigns($length)
{
    //Init the pool of characters by category
    $pool[0] = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    $pool[1] = "abcdefghjkmnpqrstuvwxyz";
    $pool[2] = "23456789";
    $pool[3] = "-_";
    return randomString_Generator($length, $pool);
}

function randomString_Generator($length, $pools)
{
    $highest_pool_index = count($pools) - 1;
    //Now generate the string
    $finalResult = "";
    $length = abs((int)$length);
    for ($counter = 0; $counter < $length; $counter++) {
        $whichPool = rand(0, $highest_pool_index);    //Randomly select the pool to use
        $maxPos = strlen($pools[$whichPool]) - 1;    //Get the max number of characters in the pool to be used
        $finalResult .= $pools[$whichPool][mt_rand(0, $maxPos)];
    }
    return $finalResult;
}

/**
 * The only difference between this and date is that it works with the env time offet
 * @param $format
 * @param $signed_seconds
 * @return bool|string
 */
if (!function_exists("now")) {
    function now($format = 'Y-m-d H:i:s', $signed_seconds = 0)
    {
        return date($format, ((time() + (env('TIME_OFFSET_HOURS', 0) * 60)) + $signed_seconds));
    }
}

function removeSpecialCharacter($string)
{
    // return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    return preg_replace('/\'/', '', $string);
}

function getPaperAttributes($paperSize)
{
    $paperSize = strtolower($paperSize);
    switch ($paperSize){
        case "a4l":
            $size = "A4";
            $orientation = "Landscape";
            $startX = 785;
            $startY = 570;
            $font = 9;
            break;
        case "a4p":
            $size = "A4";
            $orientation = "Portrait";
            $startX = 540;
            $startY = 820;
            $font = 9;
            break;
        case "a3l":
            $size = "A3";
            $orientation = "Landscape";
            $startX = 1130;
            $startY = 820;
            $font = 9;
            break;
        case "a3p":
            $size = "A3";
            $orientation = "Portrait";
            $startX = 785;
            $startY = 1165;
            $font = 9;
            break;
        case "us-sff":
            $size = "U.S. Standard Fanfold";
            $orientation = "Landscape";
            $startX = 692;
            $startY = 585;
            $font = 9;
            break;
        default:
            $size = "A4";
            $orientation = "Landscape";
            $startX = 785;
            $startY = 570;
            $font = 9;
            break;
    }
    return [$size, $orientation, $startX, $startY, $font];
}

function softwareStampWithDate($width = "100px") {
    return "<br>
    Generated @". date('Y-m-d H:i A') ;
}

function string_to_secret(string $string = NULL)
{
    if (!$string) return NULL;

    $length = strlen($string);
    $visibleCount = (int) round($length / 4);
    $hiddenCount = $length - ($visibleCount * 2);

    return substr($string, 0, $visibleCount) . str_repeat('*', $hiddenCount) . substr($string, ($visibleCount * -1), $visibleCount);
}



function split_name($name)
{
    $name = trim($name);
    $last_name = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
    $first_name = trim(preg_replace('#' . $last_name . '#', '', $name));
    return array("firstname" => $first_name, "lastname" => $last_name);
}

function convert_date($date){
    return date('D, F jS, Y', strtotime($date));
}

function convert_date_with_time($date){
    return date('D, F jS, Y h:i a', strtotime($date));
}

function convert_date2($date){
    return date('Y/m/d', strtotime($date));
}


function invoice_status($status){
    if($status == "DRAFT"){
        return label($status,"info");
    }else if($status == "PAID"){
        return label(ucwords($status),"success");
    }else if($status == "DISCOUNT"){
        return label(strtoupper("Waiting for Discount"),"primary");
    }else if($status == "VOID"){
        return label(ucwords($status),"warning");
    }else if($status == "HOLD"){
        return label(ucwords($status),"default");
    }else if($status == "COMPLETE"){
        return label(ucwords($status),"success");
    }else if($status == "DELETED"){
        return label(ucwords($status),"danger");
    }else if($status == "PENDING-APPROVAL"){
        return label(ucwords($status),"primary");
    }else if($status == "APPROVED"){
        return label(ucwords($status),"success");
    }
}


function dailySales(){
    return \App\Models\Invoice::where('invoice_date',dailyDate())->where('status','COMPLETE')->sum('total_amount_paid');
}


function weeklySales(){
    return \App\Models\Invoice::whereBetween('invoice_date',weeklyDateRange())->where('status','COMPLETE')->sum('total_amount_paid');
}

function monthlySales(){
    return \App\Models\Invoice::whereBetween('invoice_date',monthlyDateRange())->where('status','COMPLETE')->sum('total_amount_paid');
}

function dailyDate(){
    return date('Y-m-d');
}

function todaysDate(){
    return date('Y-m-d');
}

function yesterdayDate(){
    return date('Y-m-d',strtotime('yesterday'));
}

function weeklyDateRange(){
    $dt = strtotime (date('Y-m-d'));
    $range =  array (
        date ('N', $dt) == 1 ? date ('Y-m-d', $dt) : date ('Y-m-d', strtotime ('last monday', $dt)),
        date('N', $dt) == 7 ? date ('Y-m-d', $dt) : date ('Y-m-d', strtotime ('next sunday', $dt))
    );

    return $range;
}

function monthlyDateRange(){
    $dt = strtotime (date('Y-m-d'));
    $range =  array (
        date ('Y-m-d', strtotime ('first day of this month', $dt)),
        date ('Y-m-d', strtotime ('last day of this month', $dt))
    );
    return $range;
}


function getActualStore($product_type,$store_selected){

    $store = Warehousestore::find($store_selected);

    if($product_type == "NORMAL") {
        return $store->packed_column;
    }
    if($product_type == "PACKED") {
        return $store->yard_column;
    }

    return $store->packed_column;

}

function getStores($refresh = true)
{
    if($refresh == true){
        Cache::forget('warehouseandshops');
    }
    if(!Cache::has('warehouseandshops')){
        Cache::remember('warehouseandshops',144000,function(){
            return Warehousestore::select('id','name','packed_column','yard_column')->where('status',1)->get();
        });
    }
    return Cache::get('warehouseandshops');
}

function realActiveStore()
{
    if(!Cache::has('warehouseandshop')){
        Cache::remember('warehouseandshop',144000,function(){
            return Warehousestore::select('id','name','packed_column','yard_column')->where('default',1)->where('status',1)->first();
        });
    }
    return Cache::get('warehouseandshop')->id;
}

function getActiveStore($force = false){
    if(auth()->user()->warehousestore_id !== NULL) return auth()->user()->warehousestore;
    if(userCanView("stock.available_custom") && request()->get("global_filter_store")) return Warehousestore::select('id','name','packed_column','yard_column')->where("id",request()->get("global_filter_store"))->first();

    if($force == true){
        Cache::forget('warehouseandshop');
    }
    if(!Cache::has('warehouseandshop')){
        Cache::remember('warehouseandshop',144000,function(){
            return Warehousestore::select('id','name','packed_column','yard_column')->where('default',1)->where('status',1)->first();
        });
    }
    return Cache::get('warehouseandshop');
}

function getStockActualCostPrice($stock,$product_type){
    if(is_numeric($stock)) $stock = \App\Models\Stock::findorfail($stock);

    if($product_type == "NORMAL")  return $stock->cost_price;

    if($product_type == "PACKED")  return $stock->yard_cost_price;

    return $stock->cost_price;

}

function getStockActualSellingPrice($stock,$product_type){
    if(is_numeric($stock)) $stock = \App\Models\Stock::findorfail($stock);

    if($product_type == "NORMAL")  return $stock->selling_price;

    if($product_type == "PACKED")  return $stock->yard_selling_price;

    return $stock->selling_price;

}


function loadPDF($link){
    return  '<iframe src ="'.asset('/laraview/#../pdf/'.$link."?v=".mt_rand()).'" style="width: 100%; margin-top: 20px" height="600px"></iframe>';
}


function numberTowords($num)
{
    $f = new \NumberFormatter("en", NumberFormatter::SPELLOUT);
    return $f->format($num);
}


function logActivity($invoice_id, $invoice_number, $activities){

    \App\Models\Invoiceactivitylog::create([
        'invoice_id'=>$invoice_id,
        'invoice_number'=>$invoice_number,
        'activity'=>$activities,
        'user_id'=>auth()->id(),
        'activity_date'=>date('Y-m-d'),
        'activity_time'=>Carbon::now()->toTimeString()
    ]);

}

function isLogAvailable($invoice_id){
    return  \App\Models\Invoiceactivitylog::where('invoice_id', $invoice_id)->get()->count() > 0;
}

function purchaseOrderReferenceNumber()
{
    $num = \App\Models\Purchaseorder::query()->count();
    $num++;
    $num = sprintf('%04d',$num);

    return dailyDate()."-PUR-".$num;
}

function invoiceOrderReferenceNumber()
{
    $num = \App\Models\Invoice::query()->count();
    $num++;
    $num = sprintf('%04d',$num);

    return dailyDate()."-INV-".$num;
}

function invoiceReturnOrdeReferenceNumber()
{
    $num = \App\Models\InvoiceReturn::query()->count();
    $num++;
    $num = sprintf('%04d',$num);

    return dailyDate()."-RET-".$num;
}

function depositPaymentReference()
{
    $num = \App\Models\Customerdeposit::query()->count();
    $num++;
    $num = sprintf('%04d',$num);

    return dailyDate()."-DEP-".$num;
}

function creditPaymentReference()
{
    $num = \App\Models\Creditpaymentlog::query()->count();
    $num++;
    $num = sprintf('%04d',$num);

    return dailyDate()."-CRED-".$num;
}


function can(array $permissions, $model): bool
{
    foreach ($permissions as $permission) {
        if(auth()->user()->can($permission, $model)) return true;
    }
    return  false;
}

function addOtherDepartment($batch, $department): array
{
    if($department === 'retail')  return [];

    $nessArray = [
        'bulksales' => $batch->bulksales,
        'quantity' => $batch->quantity,
        'wholesales' => $batch->wholesales
    ];

    Arr::forget($nessArray, $department);

    return $nessArray;

}


function logInvoicePrint($type, \App\Models\Invoice $invoice)
{
    if($invoice->in_department !== "retail") { // we dont need to log retail invoice because printing of draft does not mean anything
        \App\Models\Invoiceprinthistory::create([
            'invoice_id' => $invoice->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'status_id' => $invoice->status_id,
            'print_date' => todaysDate(),
            'created_at' => Carbon::now()->toDateTimeString()
        ]);
    }
}


function canPrint($type, \App\Models\Invoice $invoice){

    return $invoice->invoiceprinthistories()->where('type', $type)->where('status_id', $invoice->status_id)->count() === 0;
}

function generateUniqueNumber() {
    do {
        $number = '';
        while (strlen($number) < 10) {
            $randomBytes = random_int(0, 9); // Generates a cryptographically secure random digit
            $number .= $randomBytes;
        }
    } while (\App\Models\Invoice::where('invoice_number', $number)->exists());

    return $number;
}


function addCustomerWaitingListStatusHistory(WaitingCustomer $waitingCustomer, string $status)
{
   $waitingCustomerHistory = WaitingCustomersHistory::where('waiting_customer_id', $waitingCustomer->id)->first();
   if($waitingCustomerHistory){
       if(is_null($waitingCustomer->{$status})) {
           $waitingCustomerHistory->{$status} = now()->timestamp;
           $waitingCustomerHistory->save();
       }
   } else {
       WaitingCustomersHistory::create([
           'waiting_customer_id' => $waitingCustomer->id,
           $status => now()->timestamp
       ]);
   }
}

function waitingListCustomerUpdateButton(WaitingCustomer $waitingCustomer)
{
    $button = "";
    if (auth()->user()->can('setWaitingListInvoiceToPacking', $waitingCustomer->invoice)) {
        $button = '<a href="' . route('invoiceandsales.packWaitingListInvoice', $waitingCustomer->invoice->id) . '" href="javascript:" onclick="return confirm(\'Are you sure you want set the invoice queue status to packing, this can not be reversed\');" class="btn btn-sm btn-primary">Start Packing</a>';
    }

    if (auth()->user()->can('setWaitingListInvoiceToPacked', $waitingCustomer->invoice)) {
        $button = '<a href="' . route('invoiceandsales.packedWaitingListInvoice', $waitingCustomer->invoice->id) . '" href="javascript:" onclick="return confirm(\'Are you sure you want set the invoice queue status to packed, this can not be reversed\');" class="btn btn-sm btn-primary">Complete Packing</a>';
    }

    if (auth()->user()->can('setWaitingListInvoiceToPicking', $waitingCustomer->invoice)) {
        $button = '<a href="' . route('invoiceandsales.pickWaitingListInvoice', $waitingCustomer->invoice->id) . '" href="javascript:" onclick="return confirm(\'Are you sure you want set the invoice queue status to picking, this can not be reversed\');" class="btn btn-sm btn-success">Start Picking</a>';
    }

    if (auth()->user()->can('setWaitingListInvoiceToCompletePicking',$waitingCustomer->invoice)) {
        $button = '<a href="' . route('invoiceandsales.completePickingWaitingListInvoice', $waitingCustomer->invoice->id) . '" href="javascript:" onclick="return confirm(\'Are you sure you want set the invoice queue status to complete picking, this can not be reversed\');" class="btn btn-sm btn-primary">Complete Picking</a>';
    }

    if($button == "") return "No Action";

    return $button;
}


function assets($string) : string
{
    $string = str_replace('products/images', 'images/product', $string);
    return "https://admin.generaldrugcentre.com/".$string;
}