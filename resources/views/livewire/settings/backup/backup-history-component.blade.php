@section('pageHeaderTitle', 'Backup History')
@section('pageHeaderDescription', 'Every backup, its status and recovery options')

@section('pageHeaderAction')
@endsection

<div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    @include('livewire.settings.backup._nav')

    <div class="card">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2"><label class="form-label small">From</label><input type="date" class="form-control form-control-sm" wire:model.live="dateFrom"></div>
                <div class="col-md-2"><label class="form-label small">To</label><input type="date" class="form-control form-control-sm" wire:model.live="dateTo"></div>
                <div class="col-md-2">
                    <label class="form-label small">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="status">
                        <option value="">All</option>
                        @foreach(['successful','failed','running','pending','deleted'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Destination</label>
                    <select class="form-select form-select-sm" wire:model.live="destination">
                        <option value="">All</option>
                        @foreach($destinations as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Type</label>
                    <select class="form-select form-select-sm" wire:model.live="trigger">
                        <option value="">All</option>
                        <option value="manual">Manual</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="restore_safety">Safety (pre-restore)</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end"><button class="btn btn-sm btn-light w-100" wire:click="clearFilters">Clear</button></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead>
                <tr><th>Date</th><th>Type</th><th>Destination</th><th>File</th><th>Size</th><th>Status</th><th>Duration</th><th>Checksum</th><th>By</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @forelse($backups as $b)
                    <tr wire:key="b-{{ $b->id }}">
                        <td class="text-nowrap">{{ $b->created_at->timezone($tz)->format('d M Y H:i:s') }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $b->trigger)) }}@if($b->encrypted) <i class="fas fa-lock text-muted" title="Encrypted"></i>@endif</td>
                        <td>{{ $b->destination?->name ?? '-' }}</td>
                        <td class="small text-break">{{ $b->filename ?? '-' }}</td>
                        <td class="text-nowrap">{{ $b->humanSize() }}</td>
                        <td>
                            @include('livewire.settings.backup._badge', ['status' => $b->status])
                            @if($b->status === 'successful' && $b->verification_status === 'passed')<i class="fas fa-check-circle text-success" title="Verified"></i>@endif
                            @if($b->status === 'failed' && $b->error_message)<div class="small text-danger" style="max-width:240px">{{ \Illuminate\Support\Str::limit($b->error_message, 120) }}</div>@endif
                        </td>
                        <td>{{ $b->duration_seconds !== null ? $b->duration_seconds . 's' : '-' }}</td>
                        <td><code title="{{ $b->checksum_sha256 }}">{{ $b->checksum_sha256 ? substr($b->checksum_sha256, 0, 10) . '…' : '-' }}</code></td>
                        <td class="small">{{ $b->triggeredBy() }}</td>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-light" wire:click="showLogs({{ $b->id }})" title="Logs"><i class="fas fa-list"></i></button>
                            @if($b->isUsable())
                                @if($can['download'])<a class="btn btn-sm btn-light" href="{{ route('backup.download', $b->id) }}" title="Download"><i class="fas fa-download"></i></a>@endif
                                @if($can['restore'])<button class="btn btn-sm btn-outline-danger" wire:click="askRestore({{ $b->id }})">Restore</button>@endif
                            @endif
                            @if($b->status === 'failed' && $can['run'])<button class="btn btn-sm btn-outline-primary" wire:click="retry({{ $b->id }})">Retry</button>@endif
                            @if($can['delete'] && ! $b->isActive() && $b->status !== 'deleted')
                                <button class="btn btn-sm btn-outline-secondary" wire:click="delete({{ $b->id }})" wire:confirm="Delete this backup permanently from its destination?"><i class="fas fa-trash"></i></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">No backups match the filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $backups->links() }}</div>
    </div>

    @if($logsFor)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Backup #{{ $logsFor }} log</h5><button class="btn-close" wire:click="showLogs(null)"></button></div>
                <div class="modal-body">
                    @forelse($logs as $log)
                        <div class="small border-bottom py-1"><span class="text-muted">{{ $log->created_at?->timezone($tz)->format('H:i:s') }}</span>
                            <span class="badge bg-{{ $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'secondary') }}">{{ $log->level }}</span> {{ $log->message }}</div>
                    @empty
                        <div class="text-muted">No log entries.</div>
                    @endforelse
                </div>
            </div></div>
        </div>
    @endif

    @if($restoreBackup)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header bg-danger text-white"><h5 class="modal-title">Restore database</h5><button class="btn-close" wire:click="cancelRestore"></button></div>
                <div class="modal-body">
                    <p class="mb-1"><strong>Backup:</strong> {{ $restoreBackup->filename }}</p>
                    <p class="mb-1"><strong>Taken:</strong> {{ $restoreBackup->completed_at?->timezone($tz)->format('d M Y H:i') }}</p>
                    <p><strong>Source:</strong> {{ $restoreBackup->destination?->name }}</p>
                    <div class="alert alert-danger">
                        <strong>This replaces the current inventory data</strong> with the contents of this backup. Anything recorded after the backup was taken will be lost.
                        A safety backup of the current data is taken automatically first, and the restore stops if that fails. Avoid restoring while staff are working.
                    </div>
                    @if($restoreBackup->encrypted)
                        <div class="mb-3"><label class="form-label">Encryption password</label>
                            <input type="password" class="form-control" wire:model="password" autocomplete="off"></div>
                    @endif
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="ack" wire:model="acknowledge">
                        <label class="form-check-label" for="ack">I understand current data will be overwritten.</label>
                    </div>
                    <label class="form-label">Type <strong>RESTORE</strong> to confirm</label>
                    <input type="text" class="form-control" wire:model="confirmation" autocomplete="off">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light" wire:click="cancelRestore">Cancel</button>
                    <button class="btn btn-danger" wire:click="restore" wire:loading.attr="disabled" @disabled(! $acknowledge || strtoupper(trim($confirmation)) !== 'RESTORE')>Restore now</button>
                </div>
            </div></div>
        </div>
    @endif
</div>
