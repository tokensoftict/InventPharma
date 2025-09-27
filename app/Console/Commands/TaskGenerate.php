<?php

namespace App\Console\Commands;

use App\Classes\Sql;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class TaskGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates tasks and seeds the modules and tasks table';

    private static $excluded_modules = ['Bespoke', 'Sandbox', 'Auth'];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $module_label = [
            'AccessControl' => 'Access Control',
            'Settings' => 'Settings',
            'CustomerManager'=>'Customer Manager',
            'ProductManager'=>"Stock Manager",
            'StockTransfer'=>"Stock Transfer",
            'PurchaseOrder' => 'Purchase Orders',
            'Expenses' => 'Expenses',
            'InvoiceAndSales'=>"Invoice & Sales",
            'RetailSales'=> "Retail Sales",
            'PaymentManager' => "Payment Manager",
            'PromotionManager' => "Promotion Manager",
            'PurchaseReport' => 'Purchase Report(s)',
            'PaymentReport' => 'Payment Report(s)',
            'InvoiceReport' => 'Invoice Report(s)',
            'StockTransferReport' => 'Stock Transfer Report(s)',
            'CustomerReport' => 'Customer Report(s)',
            'ProductReport' => 'Product Report(s)',
            'ExpensesReport' => 'Expenses Report(s)',
            'StaffPerformanceReport' => 'Staff Performance Report'
        ];


        $module_icon = [
            'AccessControl' => 'lock',
            'Settings' => 'settings',
            'CustomerManager'=>'user',
            'ProductManager'=>"grid",
            "StockTransfer"=>"book",
            'PurchaseOrder'=>"briefcase",
            'Expenses'=>"briefcase",
            'InvoiceAndSales'=>"shopping-cart",
            'RetailSales'=>"shopping-cart",
            'PaymentManager' => "credit-card",
            'PromotionManager' => "shopping-cart",
            'PurchaseReport' => 'Purchase Report(s)',
            'PaymentReport' => 'Payment Report(s)',
            'InvoiceReport' => 'Invoice Report(s)',
            'StockTransferReport' => 'Stock Transfer Report(s)',
            'CustomerReport' => '',
            'ProductReport' => 'Product Report(s)',
            'ExpensesReport' => 'Expenses Report(s)',
            'StaffPerformanceReport' => 'Staff Performance Report'
        ];

        $taskList = DB::table('tasks')->pluck('route', 'id');

        /*$auth_task_list = DB::table('authorizations')->whereNotNull('task_id')->pluck('task_id', 'id');
        if (!empty($auth_task_list))
            DB::table('authorizations')->whereNotNull('task_id')->update(['task_id' => null]);*/

        $sql = new Sql();
        DB::statement($sql->disableChecks);

        $permTaskList = DB::table('permissions')->select('id', 'usergroup_id', 'task_id')->get();
        if (!empty($permTaskList)) {
            DB::statement('TRUNCATE TABLE permissions');
        }

        /*$perm_auth_task_list = DB::table('permission_authorizers')->select('id', 'usergroup_id', 'task_id')->get();
        if (!empty($perm_auth_task_list)) {
            DB::statement('TRUNCATE TABLE permission_authorizers');
        }*/

        //Lets cache these values in case a failure occurs
        if (Cache::get('temp.task_list')) $taskList = Cache::get('temp.task_list');
        else Cache::forever('temp.task_list', $taskList);

        /*if (Cache::get('temp.auth_task_list')) $auth_task_list = Cache::get('temp.auth_task_list');
        else Cache::forever('temp.auth_task_list', $auth_task_list);*/

        if (Cache::get('temp.perm_task_list')) $permTaskList = Cache::get('temp.perm_task_list');
        else Cache::forever('temp.perm_task_list', $permTaskList);

        /*if (Cache::get('temp.perm_auth_task_list')) $perm_auth_task_list = Cache::get('temp.perm_auth_task_list');
        else Cache::forever('temp.perm_auth_task_list', $perm_auth_task_list);*/

        DB::table('tasks')->delete();
        $sql->resetAutoIncrement("tasks");

        DB::table('modules')->delete();
        $sql->resetAutoIncrement("modules");

        DB::statement($sql->enableChecks);

        $object = Route::getRoutes();

        $pattern = ["/destroy/", "/index/", "/store/", "/(^me$)/"];
        $replacement = ["delete", "list", "save", "my"];

        $taskOrder = [null => 1];
        $parent_task = [];
        $count = 0;

        foreach ($object as $value) {

            $route = $value->getName();

            if (!in_array('permit.task', $value->middleware())) continue;

            if (!$route) continue;

            $method = $value->methods[0];
            if (!$method == "PATCH") continue;

            $controller = explode("@", $value->getActionName());

            if (!isset($controller[1])) continue;
            $controllerName = $controller[0];
            $controllerMethod = $controller[1];

            try {
                if (!in_array($controllerMethod, get_class_methods($controllerName)))
                    continue;
            } catch (\Exception $e) {
                die($controllerName . " does not exist. on line " . $e->getLine());
            }

            $controllerName = explode("\\",$controllerName);
            $controllerName =  implode("\\",array_slice($controllerName,-2,2));

            $name = str_replace("App\\Http\\Controllers\\", "", $controllerName);
            $names = explode("\\", $name);

            if (in_array($names[0], static::$excluded_modules)) continue;

            $moduleName = $moduleID = null;
            $i = 0;

            if (count($names) == 1) continue;

            if (count($names) > 1) {
                $exist = (array)DB::table('modules')->where('name', $names[0])->first();

                if (empty($exist)) {
                    $moduleName = $names[0];
                    $moduleID = DB::table('modules')->insertGetId([
                        'name' => $moduleName,
                        'label' => $module_label[$moduleName],
                        'description' => $moduleName . " Module",
                        'status' => '1',
                        'visibility' => '1',
                        'order' => $i,
                        'icon' => $module_icon[$moduleName],
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                    $taskOrder[$moduleID] = 1;
                } else {
                    $moduleID = $exist['id'];
                    $moduleName = $exist['name'];
                }

                $names[0] = $names[1];
            }

            $order = $taskOrder[$moduleID];

            $cname = str_replace("Controller", "", $names[0]);
            $controllerMethod = preg_replace($pattern, $replacement, $controllerMethod);
            $name = !empty($value->action['label']) ? $value->action['label'] : normal_case($controllerMethod . " " . $cname);
            //$taskType = ($method == "PUT" or $method == "DELETE" or !empty($value->parameterNames())) ? "1" : "0";
            $taskType = str_contains($value->uri, 'back-office') ? '1' : '2';
            $visibility = !empty($value->action['visible']) ? '1' : '0';
            $name = (isset($value->action['custom_label']) ? $value->action['custom_label'] :  $name );

            $task_id = DB::table('tasks')->insertGetId([
                'module_id' => $moduleID,
                'route' => $route,
                'name' => $name,
                'description' => static::getTaskName($name, $controllerMethod, $cname, $moduleName, $method),
                'status' => '1',
                'visibility' => $visibility,
                'order' => $order,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            if (strstr($route, "index")) {
                $routeArray = explode(".", $route);
                array_pop($routeArray);
                $parent_task[implode(".", $routeArray)] = $task_id;
            }

            $taskOrder[$moduleID]++;
            $count++;
        }

        foreach ($parent_task as $k => $id) {
            DB::table('tasks')->where('route', 'like', $k . "%")->where('id', '<>', $id)->update(['parent_task_id' => $id]);
        }

        print "\n";

        $new_task_list = DB::table('tasks')->pluck('id', 'route');

        if (!empty($auth_task_list)) {
            print "Remapping " . (count($auth_task_list)) . " authorization task(s)";

            foreach ($auth_task_list as $id => $task_id) {
                if (isset($taskList[$task_id]) and isset($new_task_list[$taskList[$task_id]]))
                    DB::table('authorizations')->where('id', $id)->update(['task_id' => $new_task_list[$taskList[$task_id]]]);
            }
            Cache::forget('temp.auth_task_list');
        }

        if (!empty($permTaskList)) {
            print "\n";
            print "Regenerating " . (count($permTaskList)) . " permissions";

            $data = [];
            foreach ($permTaskList as $row) {
                if (isset($taskList[$row->task_id]) and isset($new_task_list[$taskList[$row->task_id]]))
                    $data[] = ['usergroup_id' => $row->usergroup_id, 'task_id' => $new_task_list[$taskList[$row->task_id]], 'created_at' => now(), 'updated_at' => now()];
            }

            DB::table('permissions')->insert($data);
            Cache::forget('temp.perm_task_list');
        }

        if (!empty($perm_auth_task_list)) {
            print "\n";
            print "Regenerating " . (count($perm_auth_task_list)) . " authorizer permissions";

            $data = [];
            foreach ($perm_auth_task_list as $row) {
                if (isset($taskList[$row->task_id]) and isset($new_task_list[$taskList[$row->task_id]]))
                    $data[] = ['usergroup_id' => $row->usergroup_id, 'task_id' => $new_task_list[$taskList[$row->task_id]], 'created_at' => now(), 'updated_at' => now()];
            }

            DB::table('permission_authorizers')->insert($data);
            Cache::forget('temp.perm_auth_task_list');
        }

        Cache::forget('temp.task_list');

        print "\n" . (count($taskOrder) - 1) . " module(s) and $count task(s) were generated";
        print "\n";


        //Artisan::call('db:seed --class=PermissionSeeder');

        return Command::SUCCESS;
    }

    public static function getTaskName($name, $controllerMethod, $controller, $module, $method)
    {

        $controllerMethod = normal_case($controllerMethod);

        $nouns = ['staff', 'beneficiary', 'biller', 'merchant', 'consumer'];

        $noun = array_shift($nouns);
        $is_noun = strstr($controllerMethod, $noun);
        foreach ($nouns as $noun) {
            $is_noun = ($is_noun or strstr($controllerMethod, $noun));
        }

        if ($method == "DELETE") {
            $prefix = "Delete";
        } elseif ($method == "PUT") {
            $prefix = "Update";
        } else {
            $prefix = "View";
        }

        if ($module == "Report") {
            if ($is_noun) {
                return ucwords($prefix . " " . $controllerMethod . " " . $module);
            } else {
                if (strtolower($controllerMethod) == "list") {
                    $controllerMethod = "View";
                    $controller = str_plural($controller);
                } elseif (strtolower($controllerMethod) == "show") {
                    $module = $module . " Detail";
                } elseif ($controller == "User") {
                    return ucwords($controller . " " . $controllerMethod . " " . $module);
                }

                return ucwords($controllerMethod . " " . $controller . " " . $module);
            }
        }

        if (strtolower($controllerMethod) == "list") {
            $controller = str_plural($controller);
            $name = str_replace($controller, $controller, str_replace("List ", "View ", $name));
        } elseif (strtolower($controllerMethod) == "show") {
            $name = $name . " Detail";
        }

        return $name;
    }

}
