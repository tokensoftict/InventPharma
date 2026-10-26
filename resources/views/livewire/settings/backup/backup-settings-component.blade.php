@section('pageHeaderTitle', 'Backup Settings')
@section('pageHeaderDescription', 'Destinations, schedule, retention, encryption and notifications')

@section('pageHeaderAction')
@endsection

<div>
    @if(View::hasSection('pageHeaderTitle'))
        @include('shared.pageheader')
    @endif

    @include('livewire.settings.backup._nav')

    @if(! $canConfigure)
        <div class="alert alert-info">You can view these settings but not change them.</div>
    @endif

    <fieldset @disabled(! $canConfigure)>
    {{-- ============================================================ DESTINATIONS --}}
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-hdd me-1"></i> Local Storage</h5></div>
                <div class="card-body">
                    <p class="small text-muted">A folder on this server, or an external drive (flash drive, HDD/SSD) mounted on it. The drive must be mounted when backups run.</p>
                    <div class="mb-2"><label class="form-label">Name</label><input class="form-control" wire:model="localName"></div>
                    <div class="mb-2">
                        <label class="form-label">Folder path</label>
                        <div class="input-group">
                            <input class="form-control" wire:model="localPath" placeholder="/mnt/backup-drive/inventory">
                            <button class="btn btn-outline-secondary" type="button" wire:click="openFolderBrowser" title="Browse server and external storage folders">
                                <i class="fas fa-folder-open me-1"></i> Browse...
                            </button>
                        </div>
                        <div class="form-text">Absolute path on the server/computer. It cannot be inside the web-accessible <code>public</code> folder.</div>

                        @if(!empty($detectedLocations))
                            <div class="mt-2">
                                <span class="small text-muted d-block mb-1">Detected drives / quick shortcuts:</span>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($detectedLocations as $loc)
                                        <button type="button" class="btn btn-xs btn-outline-secondary text-truncate" style="max-width: 260px;" wire:click="$set('localPath', '{{ addslashes($loc['path']) }}')" title="{{ $loc['path'] }}">
                                            <i class="fas fa-hdd me-1"></i> {{ $loc['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col"><label class="form-label">Keep last N</label><input type="number" min="1" class="form-control" wire:model="localKeepLast"></div>
                        <div class="col"><label class="form-label">Keep days</label><input type="number" min="1" class="form-control" wire:model="localKeepDays"></div>
                    </div>
                    <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="localEnabled" wire:model="localEnabled"><label class="form-check-label" for="localEnabled">Enabled</label></div>
                    <button class="btn btn-primary btn-sm" wire:click="saveLocal" wire:loading.attr="disabled">Save &amp; verify</button>
                    @if($localId && $canTest)<button class="btn btn-light btn-sm" wire:click="test({{ $localId }})">Test</button>@endif
                    @php $localDest = $destinations->firstWhere('id', $localId); @endphp
                    @if($localDest)
                        <div class="mt-2">@include('livewire.settings.backup._badge', ['status' => $localDest->status])
                            @if($localDest->status_message && $localDest->status !== 'ready')<span class="small text-danger ms-1">{{ $localDest->status_message }}</span>@endif</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0"><i class="fab fa-google-drive me-1"></i> Google Drive</h5></div>
                <div class="card-body">
                    @if(! $googleReady)
                        <div class="alert alert-warning small d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <i class="fas fa-exclamation-triangle me-1"></i> Google OAuth API credentials are not set up.
                            </div>
                            @if($canConfigure)
                                <button type="button" class="btn btn-sm btn-dark" wire:click="openGoogleConfig">
                                    <i class="fas fa-key me-1"></i> Setup Credentials
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded border">
                            <div>
                                <span class="badge bg-success me-1"><i class="fas fa-check-circle me-1"></i> Ready</span>
                                <span class="small text-muted">Google OAuth credentials configured</span>
                            </div>
                            @if($canConfigure)
                                <button type="button" class="btn btn-xs btn-outline-secondary" wire:click="openGoogleConfig">
                                    <i class="fas fa-cog me-1"></i> Edit Credentials
                                </button>
                            @endif
                        </div>
                    @endif

                    <div class="small text-muted mb-2">
                        Authorized redirect URI to register in Google Cloud Console:<br>
                        <div class="input-group input-group-sm mt-1">
                            <input type="text" class="form-control font-monospace" readonly value="{{ $redirectUri }}" id="googleRedirectUriInput">
                            <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ $redirectUri }}'); alert('Redirect URI copied to clipboard!');" title="Copy to clipboard">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    @foreach($google as $g)
                        <div class="border rounded p-2 mb-2" wire:key="g-{{ $g->id }}">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $g->name }}</strong>
                                @include('livewire.settings.backup._badge', ['status' => $g->status])
                            </div>
                            <div class="small">Account: {{ $g->conf('account') ?? '-' }} &middot; Folder: {{ $g->conf('folder_name') ?? '-' }}</div>
                            @if($g->status_message && $g->status !== 'ready')<div class="small text-danger">{{ $g->status_message }}</div>@endif
                            <div class="mt-1">
                                @if($canTest)<button class="btn btn-sm btn-light" wire:click="test({{ $g->id }})">Test</button>@endif
                                <button class="btn btn-sm btn-light" wire:click="toggle({{ $g->id }})">{{ $g->enabled ? 'Disable' : 'Enable' }}</button>
                                @if($canDisconnect)<button class="btn btn-sm btn-outline-danger" wire:click="disconnectGoogle({{ $g->id }})" wire:confirm="Disconnect Google Drive? Existing backups stay in Drive.">Disconnect</button>@endif
                            </div>
                        </div>
                    @endforeach

                    @if($canGoogle)
                        <div class="mb-2"><label class="form-label">Drive folder name</label><input class="form-control" wire:model="googleFolder"></div>
                        <div class="d-flex gap-2 align-items-center">
                            <button class="btn btn-primary btn-sm" wire:click="connectGoogle" @disabled(! $googleReady)>
                                <i class="fab fa-google me-1"></i> {{ $google->isEmpty() ? 'Connect Google Drive' : 'Reconnect / add account' }}
                            </button>
                            @if(! $googleReady && $canConfigure)
                                <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="openGoogleConfig">
                                    <i class="fas fa-key me-1"></i> Enter OAuth Details
                                </button>
                            @endif
                        </div>
                        <div class="form-text">Only a folder created by this app is accessed (limited <code>drive.file</code> scope).</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================ GENERAL / SCHEDULE --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">General &amp; schedule</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="enabled" wire:model="enabled"><label class="form-check-label" for="enabled">Enable Backup Manager</label></div>
                    <div class="form-check form-switch mt-2"><input class="form-check-input" type="checkbox" id="sched" wire:model="schedule_enabled"><label class="form-check-label" for="sched">Enable automatic schedule</label></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Default destination</label>
                    <select class="form-select" wire:model="default_destination_id">
                        <option value="">-- none --</option>
                        @foreach($destinations as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Timezone</label>
                    <select class="form-select" wire:model="timezone">@foreach($timezones as $tz)<option value="{{ $tz }}">{{ $tz }}</option>@endforeach</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Frequency</label>
                    <select class="form-select" wire:model.live="frequency">@foreach($frequencies as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Time</label>
                    <input type="time" class="form-control" wire:model="schedule_time" @disabled(in_array($frequency, ['manual','every_hour','every_6_hours','every_12_hours']))>
                </div>
                @if($frequency === 'weekly')
                    <div class="col-md-3"><label class="form-label">Day of week</label>
                        <select class="form-select" wire:model="day_of_week">@foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $n)<option value="{{ $i }}">{{ $n }}</option>@endforeach</select></div>
                @endif
                @if($frequency === 'monthly')
                    <div class="col-md-3"><label class="form-label">Day of month</label>
                        <input type="number" min="1" max="31" class="form-control" wire:model="day_of_month">
                        <div class="form-text">Months with fewer days run on their last day.</div></div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================================ RETENTION --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Retention</h5></div>
        <div class="card-body">
            <p class="small text-muted">Older backups are deleted when they exceed <em>either</em> limit. The newest successful backup is always kept. Leave blank to keep everything.</p>
            @forelse($destinations as $d)
                <div class="row g-2 align-items-end mb-2" wire:key="ret-{{ $d->id }}">
                    <div class="col-md-4"><strong>{{ $d->name }}</strong></div>
                    <div class="col-md-3"><label class="form-label small mb-0">Keep last N</label><input type="number" min="1" class="form-control form-control-sm" wire:model="retention.{{ $d->id }}.last"></div>
                    <div class="col-md-3"><label class="form-label small mb-0">Keep days</label><input type="number" min="1" class="form-control form-control-sm" wire:model="retention.{{ $d->id }}.days"></div>
                    <div class="col-md-2"><button class="btn btn-sm btn-light" wire:click="saveRetention({{ $d->id }})">Save</button></div>
                </div>
            @empty
                <div class="text-muted">Add a destination first.</div>
            @endforelse
        </div>
    </div>

    {{-- ============================================================ SECURITY --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0"><i class="fas fa-lock me-1"></i> Encryption</h5></div>
        <div class="card-body">
            @if(! $sodium)
                <div class="alert alert-warning">The PHP <code>sodium</code> extension is not enabled, so encryption is unavailable.</div>
            @endif
            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="enc" wire:model="encryption_enabled" @disabled(! $sodium)><label class="form-check-label" for="enc">Encrypt backups before upload</label></div>
            <div class="row"><div class="col-md-6">
                <label class="form-label">{{ $hasPassword ? 'New encryption password (leave blank to keep current)' : 'Encryption password' }}</label>
                <input type="password" class="form-control" wire:model="encryption_password" autocomplete="new-password" placeholder="min 8 characters">
            </div></div>
            <div class="alert alert-danger mt-3 mb-0 small"><strong>Keep this password safe.</strong> It cannot be recovered. Without it, encrypted backups can never be restored. Changing it affects new backups only; older ones still need their original password.</div>
        </div>
    </div>

    {{-- ============================================================ NOTIFICATIONS --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Notifications</h5></div>
        <div class="card-body">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="nf" wire:model="notify_failure"><label class="form-check-label" for="nf">Email on failure</label></div>
            <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="ns" wire:model="notify_success"><label class="form-check-label" for="ns">Email on success</label></div>
            <label class="form-label">Recipients</label>
            <input class="form-control" wire:model="notify_recipients" placeholder="admin@example.com, owner@example.com">
        </div>
    </div>

    @if($canConfigure)
        <button class="btn btn-success mb-4" wire:click="save" wire:loading.attr="disabled"><i class="fas fa-save me-1"></i> Save settings</button>
    @endif
    </fieldset>

    {{-- ============================================================ FOLDER BROWSER MODAL --}}
    @if($browsingFolder)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5); z-index: 1060;">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title"><i class="fas fa-folder-tree me-1 text-primary"></i> Browse Storage Folders</h5>
                        <button type="button" class="btn-close" wire:click="closeFolderBrowser"></button>
                    </div>
                    <div class="modal-body p-3">
                        <p class="small text-muted mb-2">
                            Browse the server and mounted external storage (Flash drives, USB HDDs, mounted volumes) to choose a backup destination folder.
                        </p>

                        {{-- Quick Drive / Mount shortcuts --}}
                        @if(!empty($detectedLocations))
                            <div class="mb-3 p-2 bg-light rounded border">
                                <span class="small fw-bold d-block mb-1 text-muted"><i class="fas fa-server me-1"></i> Available Mounts &amp; Drives:</span>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($detectedLocations as $loc)
                                        <button type="button" class="btn btn-sm btn-outline-dark" wire:click="browseTo('{{ addslashes($loc['path']) }}')">
                                            <i class="fas fa-hdd me-1 text-secondary"></i> {{ $loc['name'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Current Path Navigation & Status --}}
                        <div class="card mb-3 border">
                            <div class="card-body p-2 bg-light">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        @if($browseParent)
                                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="browseTo('{{ addslashes($browseParent) }}')" title="Go up to parent directory">
                                                <i class="fas fa-arrow-up me-1"></i> Up
                                            </button>
                                        @endif
                                        <span class="small text-muted">Current location:</span>
                                    </div>
                                    <div class="small">
                                        @if($browseIsWritable)
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Writable</span>
                                        @else
                                            <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Read-only / Not Writable</span>
                                        @endif
                                        @if($browseFreeBytes !== null)
                                            <span class="badge bg-secondary ms-1">Free: {{ \App\Models\Backup::formatBytes($browseFreeBytes) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="bg-white p-2 rounded border font-monospace text-break user-select-all small">
                                    <i class="fas fa-folder-open text-warning me-1"></i> {{ $currentBrowsePath }}
                                </div>
                            </div>
                        </div>

                        {{-- Folder list --}}
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Subdirectories:</label>
                            <div class="list-group border rounded" style="max-height: 240px; overflow-y: auto;">
                                @forelse($browseFolders as $folder)
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2" wire:key="bf-{{ $folder['path'] }}">
                                        <div class="d-flex align-items-center flex-grow-1 text-truncate cursor-pointer" role="button" wire:click="browseTo('{{ addslashes($folder['path']) }}')">
                                            <i class="fas fa-folder text-warning me-2 fs-5"></i>
                                            <span class="fw-semibold text-truncate">{{ $folder['name'] }}</span>
                                            @if(!$folder['writable'])
                                                <span class="badge bg-light text-danger border ms-2 small">locked</span>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-xs btn-outline-primary ms-2" wire:click="selectBrowsedFolder('{{ addslashes($folder['path']) }}')">
                                            Select
                                        </button>
                                    </div>
                                @empty
                                    <div class="list-group-item text-muted text-center py-3">
                                        <i class="fas fa-folder-open text-muted me-1"></i> No subdirectories in this folder.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Create Subfolder form --}}
                        <div class="p-2 border rounded bg-light">
                            <label class="form-label small fw-bold mb-1"><i class="fas fa-folder-plus me-1 text-primary"></i> Create new subfolder here:</label>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" wire:model="newFolderName" placeholder="e.g. inventory_backups" wire:keydown.enter="createFolderInBrowse">
                                <button type="button" class="btn btn-outline-primary" wire:click="createFolderInBrowse" wire:loading.attr="disabled">
                                    <i class="fas fa-plus me-1"></i> Create Folder
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-light" wire:click="closeFolderBrowser">Cancel</button>
                        <button type="button" class="btn btn-primary" wire:click="selectBrowsedFolder('{{ addslashes($currentBrowsePath) }}')">
                            <i class="fas fa-check me-1"></i> Use Current Folder ({{ basename($currentBrowsePath) ?: $currentBrowsePath }})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ GOOGLE OAUTH CONFIG MODAL --}}
    @if($configuringGoogle)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5); z-index: 1060;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title"><i class="fab fa-google text-primary me-2"></i> Configure Google OAuth Credentials</h5>
                        <button type="button" class="btn-close" wire:click="closeGoogleConfig"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="alert alert-info small mb-3">
                            <strong><i class="fas fa-info-circle me-1"></i> How to get your Google OAuth details:</strong>
                            <ol class="mb-0 mt-2 ps-3">
                                <li class="mb-1">
                                    Open the <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="fw-bold text-decoration-underline">Google Cloud Console &rarr; Credentials <i class="fas fa-external-link-alt small"></i></a>.
                                </li>
                                <li class="mb-1">
                                    Ensure the <strong>Google Drive API</strong> is enabled in your project under <em>Enabled APIs &amp; Services</em>.
                                </li>
                                <li class="mb-1">
                                    Go to <strong>Credentials</strong> &rarr; click <strong>Create Credentials</strong> &rarr; select <strong>OAuth client ID</strong>.
                                </li>
                                <li class="mb-1">
                                    Set Application type to <strong>Web application</strong>.
                                </li>
                                <li class="mb-1">
                                    Under <strong>Authorized redirect URIs</strong>, add this exact URL:
                                    <div class="input-group input-group-sm my-1">
                                        <input type="text" class="form-control font-monospace" readonly value="{{ $redirectUri }}">
                                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ $redirectUri }}'); alert('Redirect URI copied to clipboard!');">
                                            <i class="fas fa-copy me-1"></i> Copy URI
                                        </button>
                                    </div>
                                </li>
                                <li>
                                    Click <strong>Create</strong>. Copy the generated <strong>Client ID</strong> and <strong>Client Secret</strong>, then paste them below:
                                </li>
                            </ol>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Google Client ID</label>
                            <input type="text" class="form-control" wire:model="googleClientId" placeholder="e.g. 123456789-abcdef.apps.googleusercontent.com">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Google Client Secret</label>
                            <input type="password" class="form-control" wire:model="googleClientSecret" placeholder="Paste your Google Client Secret here (leave blank to keep current)">
                            <div class="form-text small">Your secret is encrypted before being saved in the database.</div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-light" wire:click="closeGoogleConfig">Cancel</button>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary" wire:click="saveGoogleCredentials" wire:loading.attr="disabled">
                                <i class="fas fa-save me-1"></i> Save Credentials
                            </button>
                            <button type="button" class="btn btn-primary" wire:click="saveGoogleAndConnect" wire:loading.attr="disabled">
                                <i class="fab fa-google me-1"></i> Save &amp; Connect Google Drive
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
