@section('pageHeaderTitle', 'Backup Manager')
@section('pageHeaderDescription', 'Automatic database backups, monitoring and recovery')

@section('pageHeaderAction')
@endsection

<div @if($poll) wire:poll.5s @else wire:poll.30s @endif>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    @include('livewire.settings.backup._nav')

    @if($restore && ($restore['state'] ?? null) === 'running')
        <div class="alert alert-warning"><i class="fas fa-sync fa-spin me-1"></i> <strong>Restore in progress.</strong> {{ $restore['message'] ?? '' }}</div>
    @elseif($restore && ($restore['state'] ?? null) === 'completed')
        <div class="alert alert-success"><strong>Last restore completed.</strong> {{ $restore['message'] ?? '' }}</div>
    @elseif($restore && ($restore['state'] ?? null) === 'failed')
        <div class="alert alert-danger"><strong>Last restore failed.</strong> {{ $restore['message'] ?? '' }}</div>
    @endif

    <div class="row">
        <div class="col-md-3">
            <div class="card border-{{ $health['class'] }}">
                <div class="card-body">
                    <div class="text-muted small">Backup health</div>
                    <h4 class="text-{{ $health['class'] }} mb-1">{{ $health['label'] }}</h4>
                    <div class="small">{{ $health['detail'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Last successful backup</div>
                <h6 class="mb-0">{{ $lastSuccess?->completed_at?->timezone($tz)->format('d M Y H:i') ?? 'Never' }}</h6>
                @if($lastSuccess)<div class="small text-muted">{{ $lastSuccess->humanSize() }} &middot; {{ $lastSuccess->destination?->name }}</div>@endif
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Last failed backup</div>
                <h6 class="mb-0">{{ $lastFailed?->completed_at?->timezone($tz)->format('d M Y H:i') ?? 'None' }}</h6>
                @if($lastFailed)<div class="small text-danger text-truncate" title="{{ $lastFailed->error_message }}">{{ $lastFailed->error_message }}</div>@endif
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <div class="text-muted small">Next scheduled backup</div>
                <h6 class="mb-0">
                    @if($settings->enabled && $settings->schedule_enabled && $nextRun)
                        {{ $nextRun->timezone($tz)->format('d M Y H:i') }}
                    @else
                        Not scheduled
                    @endif
                </h6>
                <div class="small text-muted">{{ $totalBackups }} successful &middot; {{ $failedCount }} failed (30d)</div>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Backup now</h5></div>
                <div class="card-body">
                    @if($active)
                        <div class="alert alert-info mb-3">
                            <span class="spinner-border spinner-border-sm me-1"></span>
                            Backup #{{ $active->id }} is <strong>{{ $active->status }}</strong>@if($active->destination) to {{ $active->destination->name }}@endif…
                            @if($active->attempts > 1) (attempt {{ $active->attempts }}) @endif
                        </div>
                    @endif

                    @if($can['run'])
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">Destination</label>
                                <select class="form-select" wire:model="destinationId">
                                    <option value="">-- choose --</option>
                                    @foreach($enabledDestinations as $d)
                                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->typeLabel() }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button class="btn btn-primary" wire:click="askBackup" wire:loading.attr="disabled" @disabled($active || ! $settings->enabled)>
                                    <i class="fas fa-database me-1"></i> Backup Now
                                </button>
                            </div>
                        </div>
                        @if(! $settings->enabled)<div class="small text-muted mt-2">Backup Manager is disabled in settings.</div>@endif

                        @if($confirmingBackup)
                            @php $chosen = $enabledDestinations->firstWhere('id', $destinationId); @endphp
                            <div class="alert alert-secondary mt-3 mb-0">
                                Start a full database backup to <strong>{{ $chosen?->name }}</strong>?
                                <div class="mt-2">
                                    <button class="btn btn-success btn-sm" wire:click="backupNow">Yes, start backup</button>
                                    <button class="btn btn-light btn-sm" wire:click="cancelBackup">Cancel</button>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-muted">You do not have permission to start backups.</div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">Recent backups</h5>
                    @if($can['history'])<a href="{{ route('backup.history') }}" class="btn btn-sm btn-light">View all</a>@endif
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Date</th><th>Destination</th><th>Size</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @forelse($recent as $b)
                            <tr wire:key="recent-{{ $b->id }}">
                                <td>{{ $b->created_at->timezone($tz)->format('d M H:i') }} <span class="text-muted small">({{ $b->triggeredBy() }})</span></td>
                                <td>{{ $b->destination?->name ?? '-' }}</td>
                                <td>{{ $b->humanSize() }}</td>
                                <td>@include('livewire.settings.backup._badge', ['status' => $b->status])
                                    @if($b->status === 'failed' && $b->error_message)<div class="small text-danger">{{ \Illuminate\Support\Str::limit($b->error_message, 90) }}</div>@endif
                                </td>
                                <td class="text-end">
                                    @if($b->status === 'failed' && $can['run'])
                                        <button class="btn btn-xs btn-outline-primary btn-sm" wire:click="retry({{ $b->id }})">Retry</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No backups yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Destinations</h5></div>
                <div class="card-body">
                    @forelse($destinations as $d)
                        @php $r = $storageReports[$d->id] ?? null; @endphp
                        <div class="mb-3 pb-2 border-bottom" wire:key="dest-{{ $d->id }}">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $d->name }}</strong>
                                @include('livewire.settings.backup._badge', ['status' => $d->enabled ? $d->status : 'unconfigured'])
                            </div>
                            <div class="small text-muted">{{ $d->typeLabel() }}@if(! $d->enabled) &middot; disabled @endif</div>
                            @if($d->status_message && $d->status !== 'ready')<div class="small text-danger">{{ $d->status_message }}</div>@endif
                            @if($r)
                                <div class="small">Backups: {{ \App\Models\Backup::formatBytes($r['backups']) }} ({{ $r['backup_count'] }})</div>
                                @if(isset($r['free']))
                                    <div class="small {{ $r['low'] ? 'text-danger' : '' }}">Free: {{ \App\Models\Backup::formatBytes((int) $r['free']) }}@if(isset($r['total'])) of {{ \App\Models\Backup::formatBytes((int) $r['total']) }}@endif @if($r['low']) &mdash; low storage @endif</div>
                                @endif
                            @endif
                            @if($can['test'])
                                <button class="btn btn-sm btn-light mt-1" wire:click="testDestination({{ $d->id }})" wire:loading.attr="disabled">Test</button>
                            @endif
                        </div>
                    @empty
                        <div class="text-muted">No destinations yet. @if($can['settings'])<a href="{{ route('backup.settings') }}">Configure one</a>.@endif</div>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Alerts &amp; activity</h5></div>
                <ul class="list-group list-group-flush">
                    @forelse($activity as $log)
                        <li class="list-group-item small" wire:key="act-{{ $log->id }}">
                            <span class="badge bg-{{ $log->level === 'error' ? 'danger' : ($log->level === 'warning' ? 'warning' : 'secondary') }}">{{ $log->level }}</span>
                            {{ $log->message }}
                            <div class="text-muted">{{ $log->created_at?->timezone($tz)->format('d M H:i') }}</div>
                        </li>
                    @empty
                        <li class="list-group-item small text-muted">Nothing to report.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
