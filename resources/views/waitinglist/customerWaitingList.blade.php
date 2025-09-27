<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Waiting List</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @livewireStyles
    <link href="{{ asset('css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        body {
            background: linear-gradient(135deg, #1d2b64, #f8cdda);
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }

        .table-wrapper {
            max-height: 70vh;
            overflow-y: auto;
        }

        .table td, .table th {
            vertical-align: middle;
            font-size: 1.25rem;
        }

        .live-duration {
            font-weight: bold;
            color: #ffd700;
        }

        .screen-title {
            font-size: 3rem;
            font-weight: 700;
        }

        .sub-text {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .fullscreen-btn {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
        }

        /* Optional smooth scroll */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-thumb {
            background: #ffc107;
            border-radius: 4px;
        }
        .pulse {
            animation: pulseAnim 1s ease;
        }

        @keyframes pulseAnim {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }


        .table-wrapper {
            border-radius: 1rem;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .table-white {
            --bs-table-color: #fff !important;
            --bs-table-bg: #fff !important;
            --bs-table-border-color:#fff !important;
            --bs-table-striped-bg: #fff !important;
            --bs-table-striped-color: #fff !important;
            --bs-table-active-bg: #fff !important;
            --bs-table-active-color: #fff !important;
            --bs-table-hover-bg: #fff !important;
            --bs-table-hover-color: #fff !important;
            color: var(--bs-table-color);
            border-color: var(--bs-table-border-color);
        }

        .table thead th {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .table tbody tr:hover {
            background-color: #f1f1f1;
        }

        .badge {
            font-size: 1rem;
        }
    </style>
</head>
<body>

<livewire:invoice-and-sales.waiting-list-screen />
<!-- Manual fullscreen button -->
<button onclick="enterFullscreen()" class="btn btn-sm btn-warning fullscreen-btn d-print-none">
    Enter Fullscreen
</button>

@livewireScripts
<script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script>
    function enterFullscreen() {
        const docEl = document.documentElement;
        if (docEl.requestFullscreen) {
            docEl.requestFullscreen();
        } else if (docEl.webkitRequestFullscreen) {
            docEl.webkitRequestFullscreen();
        } else if (docEl.msRequestFullscreen) {
            docEl.msRequestFullscreen();
        }
    }

    // Hide/show button based on fullscreen state
    function toggleFullscreenButton() {
        const btn = document.querySelector('.fullscreen-btn');
        const isFullscreen = document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.msFullscreenElement;

        if (isFullscreen) {
            btn.classList.add('d-none');
        } else {
            btn.classList.remove('d-none');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Try fullscreen after small delay
        setTimeout(() => {
            enterFullscreen();
        }, 500);

        // Detect fullscreen toggle
        document.addEventListener('fullscreenchange', toggleFullscreenButton);
        document.addEventListener('webkitfullscreenchange', toggleFullscreenButton);
        document.addEventListener('msfullscreenchange', toggleFullscreenButton);

        // Initial check
        toggleFullscreenButton();
    });
</script>
<script>
    const durations = {};
    let serverClientOffset = 0;

    function syncWithServerTime() {
        const serverElement = document.getElementById('server-time');
        if (!serverElement) return;

        const serverTime = parseInt(serverElement.getAttribute('data-server-time')) * 1000; // to ms
        const clientTime = Date.now();
        serverClientOffset = clientTime - serverTime;
    }

    function updateDurations() {
        const now = Date.now() - serverClientOffset; // corrected "true" server time

        for (const [id, timestamp] of Object.entries(durations)) {
            const el = document.getElementById(`duration-${id}`);
            if (!el) continue;

            const diff = now - (timestamp * 1000);
            const totalSeconds = Math.floor(diff / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;

            if (minutes >= 10) {
                el.classList.add('text-danger');
            } else {
                el.classList.remove('text-danger');
            }

            el.classList.remove('pulse');
            void el.offsetWidth; // trigger reflow
            el.classList.add('pulse');

            el.textContent = `${hours}h ${minutes}m ${seconds}s`;
        }
    }

    function cacheDurations() {
        document.querySelectorAll('.live-duration').forEach(el => {
            const id = el.getAttribute('data-id');
            const timestamp = parseInt(el.getAttribute('data-time'));
            if (!durations[id]) {
                durations[id] = timestamp;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        syncWithServerTime();
        cacheDurations();
        updateDurations();
        setInterval(updateDurations, 1000);
    });

    Livewire.hook('message.processed', () => {
        cacheDurations();
        syncWithServerTime(); // refresh offset on every update
    });

    let scrollDirection = 'down';

    function autoScrollTable() {
        const container = document.getElementById('scrollContainer');

        if (!container) return;

        const scrollHeight = container.scrollHeight;
        const clientHeight = container.clientHeight;

        // Only scroll if scroll is needed
        if (scrollHeight <= clientHeight) return;

        const currentScroll = container.scrollTop;

        if (scrollDirection === 'down') {
            container.scrollBy({ top: 100, behavior: 'smooth' });

            if (currentScroll + clientHeight >= scrollHeight - 10) {
                scrollDirection = 'up';
            }
        } else {
            container.scrollBy({ top: -100, behavior: 'smooth' });

            if (currentScroll <= 10) {
                scrollDirection = 'down';
            }
        }
    }

    // Scroll every 4 seconds
    setInterval(autoScrollTable, 4000);


</script>
</body>
</html>
