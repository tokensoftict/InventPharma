<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ESC/POS Thermal Printing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the ESC/POS direct thermal printing system.
    | This works alongside the existing PDF/Chrome printing.
    |
    */

    'escpos' => [
        /*
         * Paper width in mm. Supported: 58, 80
         * Determines characters per line (32 for 58mm, 48 for 80mm)
         */
        'paper_width' => (int) env('ESCPOS_PAPER_WIDTH', 80),

        /*
         * Character encoding for the thermal printer.
         * Options: UTF-8, CP437, CP850, CP858, Windows-1252
         * UTF-8 is recommended for modern printers (supports ₦)
         */
        'encoding' => env('ESCPOS_ENCODING', 'UTF-8'),

        /*
         * Authentication token for the Go Print Agent.
         * Must match the token configured in the agent's config.yaml
         */
        'print_agent_token' => env('PRINT_AGENT_TOKEN', ''),

        /*
         * URL of the local Go Print Agent.
         * Default: http://127.0.0.1:9100
         */
        'print_agent_url' => env('PRINT_AGENT_URL', 'http://127.0.0.1:9100'),
    ],
];
