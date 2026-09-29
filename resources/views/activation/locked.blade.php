<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }}: Application Unavailable</title>
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/icons.min.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('css/app.min.css') }}" rel="stylesheet" type="text/css"/>
    <style>
        body {
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .activation-card {
            max-width: 520px;
            width: 100%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 48px 40px;
            text-align: center;
        }
        .activation-icon { font-size: 56px; color: #f1556c; margin-bottom: 24px; }
        .activation-title { font-size: 1.5rem; font-weight: 700; color: #343a40; margin-bottom: 12px; }
        .activation-subtitle { color: #6c757d; margin-bottom: 8px; font-size: 1rem; }
        .contact-note {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="activation-card">
        <div class="activation-icon">
            <i class="mdi mdi-lock-alert-outline"></i>
        </div>
        <h1 class="activation-title">Application Unavailable</h1>
        <p class="activation-subtitle">
            Application activation data is invalid.
        </p>
        <p class="activation-subtitle">
            Please contact your software provider.
        </p>
        <div class="contact-note">
            <i class="mdi mdi-phone-outline me-1"></i>
            Contact your software provider for assistance.
        </div>
    </div>
</body>
</html>
