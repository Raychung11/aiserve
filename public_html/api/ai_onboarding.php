<?php
/**
 * MM2H 管家 — AI Eligibility Check API
 * MVP: rule-based logic. Swap in OpenAI/Claude call via get_setting('openai_key').
 */

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
start_secure_session();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// CSRF check for AJAX
$token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!hash_equals(csrf_token(), $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$age            = (int)($_POST['age'] ?? 0);
$nationality    = trim($_POST['nationality'] ?? '');
$monthly_income = $_POST['monthly_income'] ?? '';
$liquid_assets  = $_POST['liquid_assets'] ?? '';
$fd_readiness   = $_POST['fd_readiness'] ?? 'no';
$purpose        = $_POST['purpose'] ?? '';

// ── Rule-based eligibility engine ────────────────────────────────────────────
$score     = 0;
$max_score = 10;
$flags     = [];
$documents = [];

// Age (minimum 25 for most MM2H routes)
if ($age >= 35) { $score += 2; }
elseif ($age >= 25) { $score += 1; }
else { $flags[] = 'Applicant may be below the recommended age (25+) for most MM2H routes.'; }

// Income
$income_scores = ['under_2000' => 0, '2000_5000' => 1, '5000_10000' => 2, 'over_10000' => 2];
$score += $income_scores[$monthly_income] ?? 0;
if (($income_scores[$monthly_income] ?? 0) === 0) {
    $flags[] = 'Income may be below the recommended threshold. We recommend at least USD 2,000/month.';
}

// Liquid assets
$asset_scores = ['under_100k' => 0, '100k_300k' => 1, '300k_500k' => 2, 'over_500k' => 3];
$score += $asset_scores[$liquid_assets] ?? 0;
if (($asset_scores[$liquid_assets] ?? 0) === 0) {
    $flags[] = 'Liquid assets may be below the recommended level of USD 100,000+.';
}

// FD readiness (MM2H requires MYR 500,000 Fixed Deposit)
if ($fd_readiness === 'yes') {
    $score += 2;
} elseif ($fd_readiness === 'maybe') {
    $score += 1;
    $flags[] = 'Fixed Deposit (MYR 500,000) is mandatory. We recommend confirming fund availability before applying.';
} else {
    $flags[] = 'The MM2H Fixed Deposit of MYR 500,000 is a mandatory requirement. Please plan accordingly.';
}

// Purpose bonus
if (in_array($purpose, ['retirement', 'investment', 'business'], true)) { $score += 1; }

// ── Determine result ─────────────────────────────────────────────────────────
if ($score >= 8) {
    $result         = 'eligible';
    $title          = 'Likely Eligible for MM2H';
    $message        = 'Based on your initial assessment, you appear to meet the key criteria for the MM2H programme. We recommend proceeding with a full consultation.';
    $suggested_route= 'Standard MM2H Programme (Premium Track)';
    $next_action    = 'Start My Application';
    $cta_url        = '/register';
} elseif ($score >= 5) {
    $result         = 'potential';
    $title          = 'Potentially Eligible';
    $message        = 'You may qualify for MM2H, though some areas may need strengthening. A consultation with our team will clarify the best approach.';
    $suggested_route= 'MM2H Standard Track — with agent consultation recommended';
    $next_action    = 'Book a Consultation';
    $cta_url        = '/contact';
} elseif ($score >= 2) {
    $result         = 'consult';
    $title          = 'Needs Consultation';
    $message        = 'Your profile needs further review. Our concierge team can explore alternative routes or help you prepare for eligibility.';
    $suggested_route= 'Alternative MM2H planning — financial strengthening recommended';
    $next_action    = 'Speak to Our Team';
    $cta_url        = '/contact';
} else {
    $result         = 'insufficient';
    $title          = 'More Information Needed';
    $message        = 'We need more details to assess your eligibility. Please complete the full onboarding profile or book a free consultation.';
    $suggested_route= null;
    $next_action    = 'Complete Your Profile';
    $cta_url        = '/register';
}

// Documents needed
$documents = [
    'Passport copy (all pages)',
    'Proof of income (latest 3 months)',
    'Bank statements (6 months)',
    'Passport photo (white background)',
];
if (in_array($purpose, ['business', 'investment'], true)) {
    $documents[] = 'Business registration documents';
    $documents[] = 'Company financial statements';
}
if ($purpose === 'retirement') {
    $documents[] = 'Pension statement or retirement fund evidence';
}
$documents[] = 'Medical report (from registered physician)';
$documents[] = 'Police clearance certificate';

// ── Optional: delegate to OpenAI/Claude if configured ────────────────────────
if (get_setting('enable_ai') === '1' && get_setting('openai_key')) {
    // Placeholder for AI integration
    // $ai_response = call_openai_or_claude($age, $nationality, $monthly_income, $liquid_assets, $fd_readiness, $purpose);
    // Override rule-based result with AI response if available
}

// ── Log the check ─────────────────────────────────────────────────────────────
log_activity('eligibility_check', null, null);

echo json_encode([
    'result'         => $result,
    'title'          => $title,
    'message'        => $message,
    'suggested_route'=> $suggested_route,
    'documents'      => $documents,
    'next_action'    => $next_action,
    'cta_url'        => $cta_url,
    'score'          => $score,
    'flags'          => $flags,
], JSON_UNESCAPED_UNICODE);
