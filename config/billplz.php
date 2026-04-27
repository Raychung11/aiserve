<?php
// Billplz FPX payment settings
define('BILLPLZ_API_KEY',       '');   // from billplz.com/enterprise/settings
define('BILLPLZ_COLLECTION_ID', '');   // your collection ID
define('BILLPLZ_X_SIGNATURE',   '');   // X Signature Key
define('BILLPLZ_SANDBOX',       true); // set false in production
define('BILLPLZ_BASE_URL',      BILLPLZ_SANDBOX ? 'https://www.billplz-sandbox.com/api/v3' : 'https://www.billplz.com/api/v3');
