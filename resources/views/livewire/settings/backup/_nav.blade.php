<ul class="nav nav-pills mb-3">
    @if(userCanView('backup.index'))
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('backup.index') ? 'active' : '' }}" href="{{ route('backup.index') }}"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
    @endif
    @if(userCanView('backup.history'))
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('backup.history') ? 'active' : '' }}" href="{{ route('backup.history') }}"><i class="fas fa-history me-1"></i> Backup History</a></li>
    @endif
    @if(userCanView('backup.settings'))
        <li class="nav-item"><a class="nav-link {{ request()->routeIs('backup.settings') ? 'active' : '' }}" href="{{ route('backup.settings') }}"><i class="fas fa-cog me-1"></i> Backup Settings</a></li>
    @endif
</ul>
