<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Registers the Backup Manager routes as permission "tasks" WITHOUT running task:generate
 * (which truncates and rebuilds every task/permission). Only missing backup.* tasks are
 * inserted into the existing Settings module, and the administrator group (id 1) is granted
 * them. Assign them to other groups in Access Control > User Group > Permission.
 *
 * Idempotent. task:generate will also pick these routes up on its next run.
 */
class BackupRegisterPermissions extends Command
{
    protected $signature = 'backup:register-permissions';

    protected $description = 'Register Backup Manager permission tasks (non-destructive) and grant them to the admin group';

    public function handle(): int
    {
        $module = DB::table('modules')->where('name', 'Settings')->first();
        if (! $module) {
            $this->error('The "Settings" module was not found. Run `php artisan task:generate` once, then re-run this command.');

            return self::FAILURE;
        }

        $order = (int) DB::table('tasks')->where('module_id', $module->id)->max('order');
        $parentId = null;
        $added = 0;

        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => $r->getName() && str_starts_with($r->getName(), 'backup.') && in_array('permit.task', $r->middleware()))
            ->sortBy(fn ($r) => $r->getName() === 'backup.index' ? 0 : 1);

        foreach ($routes as $route) {
            $name = $route->getName();
            $existing = DB::table('tasks')->where('route', $name)->first();

            if (! $existing) {
                $label = $route->action['custom_label'] ?? $name;
                $id = DB::table('tasks')->insertGetId([
                    'module_id'      => $module->id,
                    'parent_task_id' => $name === 'backup.index' ? 0 : ($parentId ?? 0),
                    'route'          => $name,
                    'name'           => mb_substr($label, 0, 60),
                    'description'    => $label,
                    'status'         => 1,
                    'visibility'     => ! empty($route->action['visible']) ? 1 : 0,
                    'order'          => ++$order,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
                $added++;
            } else {
                $id = $existing->id;
            }

            if ($name === 'backup.index') {
                $parentId = $id;
            }

            if (! DB::table('permissions')->where(['usergroup_id' => 1, 'task_id' => $id])->exists()) {
                DB::table('permissions')->insert(['usergroup_id' => 1, 'task_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        Cache::forget('route-permission-1');

        $this->info("Backup Manager permissions registered ({$added} new). The administrator group has been granted access.");

        return self::SUCCESS;
    }
}
