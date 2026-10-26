<?php

namespace App\Livewire\Settings\Backup;

use App\Models\Backup;
use App\Models\BackupDestination;
use App\Models\BackupLog;
use App\Models\BackupSetting;
use App\Services\Backup\BackupActions;
use App\Services\Backup\BackupAuthorizer;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\RestoreService;
use App\Services\Backup\ScheduleCalculator;
use App\Services\Backup\StorageMonitor;
use App\Traits\LivewireAlert;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class BackupDashboardComponent extends Component
{
    use LivewireAlert;

    public ?int $destinationId = null;

    public bool $confirmingBackup = false;

    public function mount(BackupAuthorizer $auth)
    {
        abort_unless($auth->can('view'), 403);

        $this->destinationId = BackupSetting::current()->default_destination_id;
    }

    /* --------------------------------------------------------------- actions */

    public function askBackup()
    {
        if (! $this->destinationId) {
            return $this->toast('warning', 'Choose a destination first.');
        }
        $this->confirmingBackup = true;
    }

    public function cancelBackup()
    {
        $this->confirmingBackup = false;
    }

    public function backupNow(BackupActions $actions)
    {
        $this->confirmingBackup = false;

        $this->guard(function () use ($actions) {
            $backup = $actions->backupNow((int) $this->destinationId);
            $this->toast('success', "Backup #{$backup->id} has been queued and will run in the background.");
        });
    }

    public function retry(BackupActions $actions, int $id)
    {
        $this->guard(function () use ($actions, $id) {
            $actions->retry($id);
            $this->toast('success', 'Backup re-queued.');
        });
    }

    public function testDestination(BackupActions $actions, int $id)
    {
        $this->guard(fn () => $this->toast('success', $actions->testDestination($id)));
    }

    /* ---------------------------------------------------------------- render */

    public function render(BackupAuthorizer $auth, StorageMonitor $storage, ScheduleCalculator $calculator)
    {
        $settings = BackupSetting::current();
        $destinations = BackupDestination::orderBy('id')->get();

        $lastSuccess = Backup::successful()->latest('completed_at')->first();
        $lastFailed = Backup::where('status', Backup::STATUS_FAILED)->latest('completed_at')->first();
        $latestFinished = Backup::whereIn('status', [Backup::STATUS_SUCCESSFUL, Backup::STATUS_FAILED])->latest('completed_at')->first();
        $active = Backup::whereIn('status', [Backup::STATUS_PENDING, Backup::STATUS_RUNNING])->latest('id')->first();

        $storageReports = $destinations->mapWithKeys(fn ($d) => [$d->id => $storage->report($d)]);

        $restore = $this->latestRestoreStatus();
        $poll = $active || ($restore['state'] ?? null) === 'running';

        return view('livewire.settings.backup.backup-dashboard-component', [
            'settings'      => $settings,
            'destinations'  => $destinations,
            'enabledDestinations' => $destinations->where('enabled', true),
            'lastSuccess'   => $lastSuccess,
            'lastFailed'    => $lastFailed,
            'active'        => $active,
            'nextRun'       => $calculator->nextRun($settings),
            'totalBackups'  => Backup::successful()->count(),
            'failedCount'   => Backup::where('status', Backup::STATUS_FAILED)->where('created_at', '>=', now()->subDays(30))->count(),
            'storageReports' => $storageReports,
            'health'        => $this->health($settings, $destinations, $latestFinished, $lastSuccess, $storageReports),
            'recent'        => Backup::with(['destination', 'creator'])->latest('id')->limit(8)->get(),
            'activity'      => BackupLog::whereIn('level', ['warning', 'error'])->orWhere('category', 'audit')->latest('id')->limit(8)->get(),
            'restore'       => $restore,
            'poll'          => $poll,
            'can'           => collect(array_keys(BackupAuthorizer::ABILITIES))->mapWithKeys(fn ($a) => [$a => $auth->can($a)])->all(),
            'tz'            => $calculator->timezone($settings),
        ]);
    }

    protected function health(BackupSetting $settings, $destinations, ?Backup $latestFinished, ?Backup $lastSuccess, $reports): array
    {
        if (! $settings->enabled) {
            return ['label' => 'Disabled', 'class' => 'secondary', 'detail' => 'The Backup Manager is turned off.'];
        }
        if ($destinations->where('enabled', true)->isEmpty()) {
            return ['label' => 'Not configured', 'class' => 'warning', 'detail' => 'Add and enable a backup destination.'];
        }
        if (! $lastSuccess) {
            return ['label' => 'No backups yet', 'class' => 'warning', 'detail' => 'Run your first backup.'];
        }
        if ($latestFinished && $latestFinished->status === Backup::STATUS_FAILED) {
            return ['label' => 'Failing', 'class' => 'danger', 'detail' => 'The most recent backup failed.'];
        }
        if ($destinations->where('enabled', true)->contains(fn ($d) => $d->status !== BackupDestination::STATUS_READY)) {
            return ['label' => 'Attention', 'class' => 'warning', 'detail' => 'A destination needs attention.'];
        }
        if ($reports->contains(fn ($r) => $r['low'])) {
            return ['label' => 'Low storage', 'class' => 'warning', 'detail' => 'Free space is running low.'];
        }
        if ($lastSuccess->completed_at && $lastSuccess->completed_at->lt(now()->subHours(48)) && $settings->schedule_enabled) {
            return ['label' => 'Overdue', 'class' => 'warning', 'detail' => 'No successful backup in over 48 hours.'];
        }

        return ['label' => 'Healthy', 'class' => 'success', 'detail' => 'Backups are running normally.'];
    }

    protected function latestRestoreStatus(): ?array
    {
        $log = BackupLog::where('event', 'restore_started')->where('created_at', '>=', now()->subDay())->latest('id')->first();

        return $log && $log->backup_id ? app(RestoreService::class)->status($log->backup_id) : null;
    }

    /* --------------------------------------------------------------- helpers */

    protected function guard(\Closure $callback): void
    {
        try {
            $callback();
        } catch (ValidationException $e) {
            $this->toast('error', collect($e->errors())->flatten()->first());
        } catch (AuthorizationException|BackupException $e) {
            $this->toast('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $this->toast('error', 'Something went wrong. See the backup log for details.');
        }
    }

    protected function toast(string $type, string $message): void
    {
        $this->alert($type, 'Backup Manager', ['position' => 'center', 'timer' => 4000, 'toast' => false, 'text' => $message]);
    }
}
