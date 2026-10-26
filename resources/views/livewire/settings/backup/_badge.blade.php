@php
    $map = [
        'successful' => ['success', 'Success'],
        'running'    => ['info', 'Running'],
        'pending'    => ['warning', 'Pending'],
        'failed'     => ['danger', 'Failed'],
        'cancelled'  => ['secondary', 'Cancelled'],
        'deleted'    => ['dark', 'Deleted'],
        'ready'        => ['success', 'Ready'],
        'unavailable'  => ['danger', 'Unavailable'],
        'auth_expired' => ['danger', 'Auth expired'],
        'error'        => ['danger', 'Error'],
        'unconfigured' => ['secondary', 'Not configured'],
    ];
    [$cls, $text] = $map[$status] ?? ['secondary', ucfirst($status)];
@endphp
<span class="badge bg-{{ $cls }}{{ $status === 'running' ? ' text-white' : '' }}">
    @if($status === 'running')<span class="spinner-border spinner-border-sm me-1" style="width:.7rem;height:.7rem"></span>@endif{{ $text }}
</span>
