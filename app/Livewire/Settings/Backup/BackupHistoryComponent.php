<?php

namespace App\Livewire\Settings\Backup;

use App\Models\Backup;
use App\Models\BackupDestination;
use App\Models\BackupLog;
use App\Services\Backup\BackupActions;
use App\Services\Backup\BackupAuthorizer;
use App\Services\Backup\Exceptions\BackupException;
use App\Services\Backup\ScheduleCalculator;
use App\Traits\LivewireAlert;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class BackupHistoryComponent extends Component
{
    use LivewireAlert, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public string $dateFrom = '';
    public string $dateTo = '';
    public string $status = '';
    public string $destination = '';
    public string $trigger = '';

    public ?int $restoreId = null;
    public bool $acknowledge = false;
    public string $confirmation = '';
    public string $password = '';

    public ?int $logsFor = null;

    public function mount(BackupAuthorizer $auth)
    {
        abort_unless($auth->can('history'), 403);
    }

    public function updating($name)
    {
        if (in_array($name, ['dateFrom', 'dateTo', 'status', 'destination', 'trigger'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters()
    {
        $this->reset(['dateFrom', 'dateTo', 'status', 'destination', 'trigger']);
        $this->resetPage();
    }

    /* --------------------------------------------------------------- actions */

    public function retry(BackupActions $actions, int $id)
    {
        $this->guard(function () use ($actions, $id) {
            $actions->retry($id);
            $this->toast('success', 'Backup re-queued.');
        });
    }

    public function delete(BackupActions $actions, int $id)
    {
        $this->guard(function () use ($actions, $id) {
            $actions->delete($id);
            $this->toast('success', 'Backup deleted.');
        });
    }

    public function showLogs(?int $id)
    {
        $this->logsFor = $id;
    }

    public function askRestore(BackupAuthorizer $auth, int $id)
    {
        if (! $auth->can('restore')) {
            return $this->toast('error', 'Only administrators can restore backups.');
        }
        $this->reset(['acknowledge', 'confirmation', 'password']);
        $this->restoreId = $id;
    }

    public function cancelRestore()
    {
        $this->reset(['restoreId', 'acknowledge', 'confirmation', 'password']);
    }

    public function restore(BackupActions $actions)
    {
        if (! $this->restoreId) {
            return;
        }
        if (! $this->acknowledge) {
            return $this->toast('warning', 'Tick the acknowledgement box first.');
        }

        $id = $this->restoreId;
        $this->guard(function () use ($actions, $id) {
            $actions->restore($id, $this->confirmation, $this->password ?: null);
            $this->toast('success', 'Restore started. A safety backup is taken first; progress shows on the dashboard.');
            $this->cancelRestore();
        });
        $this->password = '';
    }

    /* ---------------------------------------------------------------- render */

    public function render(BackupAuthorizer $auth, ScheduleCalculator $calculator)
    {
        $query = Backup::with(['destination', 'creator'])->latest('id');

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }
        if ($this->status !== '') {
            $query->where('status', $this->status);
        }
        if ($this->destination !== '') {
            $query->where('destination_id', (int) $this->destination);
        }
        if ($this->trigger !== '') {
            $query->where('trigger', $this->trigger);
        }

        $restoreBackup = $this->restoreId ? Backup::with('destination')->find($this->restoreId) : null;

        return view('livewire.settings.backup.backup-history-component', [
            'backups'       => $query->paginate(15),
            'destinations'  => BackupDestination::orderBy('name')->get(),
            'can'           => collect(array_keys(BackupAuthorizer::ABILITIES))->mapWithKeys(fn ($a) => [$a => $auth->can($a)])->all(),
            'tz'            => $calculator->timezone(\App\Models\BackupSetting::current()),
            'restoreBackup' => $restoreBackup,
            'logs'          => $this->logsFor ? BackupLog::where('backup_id', $this->logsFor)->orderBy('id')->limit(100)->get() : collect(),
        ]);
    }

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
