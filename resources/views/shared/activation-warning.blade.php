@php
    /** @var \App\Services\Activation\ApplicationActivationService $activationSvc */
    $activationSvc = app(\App\Services\Activation\ApplicationActivationService::class);
    $days          = $activationSvc->daysRemaining();
@endphp

@if($days !== null && $days <= 30)
    @php
        if ($days <= 1) {
            $urgency = 'danger';
            $msg     = 'Your application subscription expires <strong>tomorrow</strong>. Please contact your software provider.';
        } elseif ($days <= 7) {
            $urgency = 'danger';
            $msg     = "Your application subscription expires in <strong>{$days} days</strong>. Please contact your software provider.";
        } elseif ($days <= 14) {
            $urgency = 'warning';
            $msg     = "Your application subscription expires in <strong>{$days} days</strong>. Please contact your software provider.";
        } else {
            $urgency = 'info';
            $msg     = "Your application subscription expires in <strong>{$days} days</strong>.";
        }
    @endphp

    <div class="alert alert-{{ $urgency }} alert-dismissible fade show m-2" role="alert" id="activation-warning-banner">
        <i class="mdi mdi-alert-circle-outline me-2"></i>
        {!! $msg !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
