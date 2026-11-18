<?php

return [
    'bank_transfer' => [
        'bank_name' => env('AOVU_BANK_NAME', ''),
        'bank_bin' => env('AOVU_BANK_BIN', ''),
        'account_number' => env('AOVU_BANK_ACCOUNT', ''),
        'account_name' => env('AOVU_BANK_ACCOUNT_NAME', ''),
        'qr_template' => env('AOVU_BANK_QR_TEMPLATE', 'compact2'),
    ],
];
