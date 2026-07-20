<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Shared bootstrap for AI JSON endpoints
|--------------------------------------------------------------------------
| AI calls (especially image generation) can run for many seconds. Raise the
| limits and guarantee a JSON body even if the script hits a fatal error or
| the execution-time limit — otherwise the browser receives an empty response
| and fails with "Unexpected end of JSON input".
*/

@set_time_limit(300);
@ini_set('memory_limit', '256M');
@ini_set('display_errors', '0');

register_shutdown_function(function (): void {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
        }
        echo json_encode([
            'ok'    => false,
            'error' => 'The request timed out or failed on the server. If you were generating an image, switch the Image Model to "dall-e-3" in AI API Settings (it is faster) and try again.',
        ]);
    }
});
