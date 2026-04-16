<?php
declare(strict_types=1);

/**
 * KASIH GOLD EASY — CSRF Protection
 *
 * Provides token generation, hidden-field rendering, and verification.
 * Requires an active session before any function is called.
 */

/**
 * Generates and stores a CSRF token in the session if one does not exist yet.
 */
function csrf_generate(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (function_exists('auth_start_session')) {
            auth_start_session();
        } else {
            session_start();
        }
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Returns the current CSRF token, generating one if it does not exist.
 */
function csrf_token(): string
{
    csrf_generate();
    return (string)$_SESSION['csrf_token'];
}

/**
 * Returns a hidden HTML input field containing the CSRF token.
 * Safe to embed directly into any <form>.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">';
}

/**
 * Verifies the CSRF token submitted with a POST request.
 *
 * If the token is missing or does not match, responds with HTTP 403 and
 * terminates execution. The response format (JSON or HTML) is chosen based
 * on the request's Accept header and whether it appears to be an AJAX call.
 */
function csrf_verify(): void
{
    csrf_generate(); // ensure a token exists in session

    $submitted = (string)($_POST['csrf_token'] ?? '');
    $expected  = (string)($_SESSION['csrf_token'] ?? '');

    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);

        $isAjax = (
            (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        );

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Permintaan tidak sah. Token CSRF tidak sepadan.',
                'code'    => 403,
            ]);
        } else {
            echo '<!DOCTYPE html><html lang="ms"><head><meta charset="UTF-8">'
                . '<title>403 – Permintaan Ditolak</title>'
                . '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#fef2f2;}'
                . '.box{background:#fff;border:1px solid #fca5a5;border-radius:8px;padding:2rem 3rem;text-align:center;max-width:420px;}'
                . 'h1{color:#dc2626;font-size:1.5rem;margin:0 0 .5rem}p{color:#6b7280;margin:0 0 1rem}'
                . 'a{color:#d97706;font-weight:600;text-decoration:none}</style></head>'
                . '<body><div class="box">'
                . '<h1>&#128683; Permintaan Ditolak</h1>'
                . '<p>Token keselamatan tidak sah atau telah tamat tempoh.<br>Sila muat semula halaman dan cuba lagi.</p>'
                . '<a href="javascript:history.back()">&#8592; Kembali</a>'
                . '</div></body></html>';
        }

        exit;
    }
}
