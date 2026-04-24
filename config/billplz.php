<?php

return [
    'api_key'       => env('BILLPLZ_API_KEY', ''),
    'collection_id' => env('BILLPLZ_COLLECTION_ID', ''),
    'sandbox'       => env('BILLPLZ_SANDBOX', true),
    'x_signature'   => env('BILLPLZ_X_SIGNATURE', ''),
];
