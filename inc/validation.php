<?php
declare(strict_types=1);

/**
 * KASIH GOLD EASY — Validation Helpers
 *
 * Provides input validation and sanitisation utilities used across the platform.
 */

// ============================================================
// REQUIRED FIELD VALIDATION
// ============================================================

/**
 * Checks that each field listed in $fields is present and non-empty in $data.
 *
 * Returns an associative array of field => error message for every failing field.
 * Returns an empty array when all required fields pass.
 *
 * @param  array<string, mixed>  $data    The data array to validate (e.g. $_POST).
 * @param  string[]              $fields  List of field keys that are required.
 * @return array<string, string>
 */
function validate_required(array $data, array $fields): array {
    $errors = [];
    foreach ($fields as $field) {
        $value = $data[$field] ?? '';
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value === '' || $value === null || $value === false) {
            // Build a human-readable label from the field key
            $label          = ucwords(str_replace(['_', '-'], ' ', $field));
            $errors[$field] = "{$label} wajib diisi";
        }
    }
    return $errors;
}

// ============================================================
// FORMAT VALIDATORS
// ============================================================

/**
 * Returns true if $email is a syntactically valid e-mail address.
 */
function validate_email(string $email): bool {
    return filter_var(trim($email), FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Returns true if $phone is a valid Malaysian mobile number.
 *
 * Accepted formats:
 *   +601x-xxxxxxx   (with country code, optional dash)
 *   601x-xxxxxxx
 *   01x-xxxxxxx
 *   01xxxxxxxxx
 *
 * Total digits (excluding leading +): 10–11.
 */
function validate_phone(string $phone): bool {
    // Strip spaces, dashes, and dots for normalisation
    $normalised = preg_replace('/[\s\-\.]/', '', $phone);

    if ($normalised === null) {
        return false;
    }

    // Allow optional leading +
    // Must start with +601, 601, or 01
    // Followed by 7–9 digits (making the whole number 10–11 digits without country code)
    $pattern = '/^(\+?60|0)1[0-9]{8,9}$/';

    return (bool)preg_match($pattern, $normalised);
}

/**
 * Returns true if $password meets the minimum strength requirements:
 *   - At least 8 characters
 *   - At least one uppercase letter (A–Z)
 *   - At least one lowercase letter (a–z)
 *   - At least one digit (0–9)
 */
function validate_password_strength(string $password): bool {
    if (strlen($password) < 8) {
        return false;
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    return true;
}

// ============================================================
// SANITISATION
// ============================================================

/**
 * Trims, strips HTML/PHP tags, and truncates a string to $max_length characters.
 */
function sanitize_string(string $str, int $max_length = 255): string {
    $str = trim($str);
    $str = strip_tags($str);
    return substr($str, 0, $max_length);
}

/**
 * Sanitises and validates a numeric (decimal) value.
 *
 * Returns a numeric string formatted to $decimals decimal places, or false if
 * $val is not a valid number or is negative.
 *
 * @param  mixed $val      The raw value (string, int, float, etc.).
 * @param  int   $decimals Number of decimal places to retain.
 * @return string|false
 */
function sanitize_decimal(mixed $val, int $decimals = 4): string|false {
    if ($val === null || $val === '' || $val === false) {
        return false;
    }

    // Accept string representations such as "1,234.56" — strip commas
    if (is_string($val)) {
        $val = str_replace(',', '', trim($val));
    }

    if (!is_numeric($val)) {
        return false;
    }

    $float = (float)$val;

    // Reject negative values
    if ($float < 0) {
        return false;
    }

    return number_format($float, $decimals, '.', '');
}

// ============================================================
// ERROR RENDERING
// ============================================================

/**
 * Renders an HTML <ul> list of validation errors, or returns an empty string
 * when $errors is empty.
 *
 * Each error value may be a string or a nested array of strings.
 *
 * @param  array<string, string|string[]> $errors
 */
function validation_errors(array $errors): string {
    if (empty($errors)) {
        return '';
    }

    $items = '';
    foreach ($errors as $error) {
        if (is_array($error)) {
            foreach ($error as $msg) {
                $items .= '<li>' . htmlspecialchars((string)$msg, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</li>';
            }
        } else {
            $items .= '<li>' . htmlspecialchars((string)$error, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</li>';
        }
    }

    return '<ul class="validation-errors" style="color:#dc2626;list-style:disc;padding-left:1.25rem;margin:.5rem 0;">'
         . $items
         . '</ul>';
}
