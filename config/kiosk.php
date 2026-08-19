<?php

return [
    'requisition_label' => [
        'width_mm' => 100,
        'height_mm' => 100,
        'dpi' => (int) env('KIOSK_REQUISITION_LABEL_DPI', 203),
        'default_printer_name' => env('KIOSK_REQUISITION_PRINTER_NAME'),
        'claim_timeout_seconds' => 45,
    ],
];
