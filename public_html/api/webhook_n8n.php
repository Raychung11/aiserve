<?php
/**
 * MM2H 管家 — n8n Webhook Endpoint
 * Receives event notifications from n8n workflows.
 * Secured by shared secret token in settings.
 */

require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
start_secure_session();

header('Content-Type: application/json; charset=utf-8');

// Only POST allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Authenticate via Bearer token (store secret in settings)
$n8n_webhook_url = get_setting('n8n_webhook');
$auth_header     = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$provided_token  = str_replace('Bearer ', '', $auth_header);
$expected_token  = get_setting('n8n_webhook_secret', '');

if ($expected_token && !hash_equals($expected_token, $provided_token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Parse incoming payload
$raw     = file_get_contents('php://input');
$payload = json_decode($raw, true);

if (!$payload || !isset($payload['event'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload — event field required']);
    exit;
}

$event = $payload['event'] ?? '';
$data  = $payload['data'] ?? [];

$pdo = db();
$response = ['received' => true, 'event' => $event];

// ── Event handlers ────────────────────────────────────────────────────────────
switch ($event) {

    case 'case.status_update':
        // n8n updates a case status from external workflow
        $case_id    = (int)($data['case_id'] ?? 0);
        $new_status = $data['status'] ?? '';
        $note       = $data['note'] ?? '';
        $valid_statuses = ['new_lead','initial_consult','doc_collection','eligibility_review',
                           'submitted_agent','gov_processing','conditional','fd_stage',
                           'medical','final_approval','completed','rejected'];
        if ($case_id && in_array($new_status, $valid_statuses, true)) {
            $pdo->prepare('UPDATE mm2h_cases SET current_status=?, updated_at=NOW() WHERE id=?')
                ->execute([$new_status, $case_id]);
            if ($note) {
                $pdo->prepare('INSERT INTO case_notes (case_id, author_id, note, is_internal) VALUES (?,1,?,0)')
                    ->execute([$case_id, $note]);
            }
            log_activity("webhook_case_status:$new_status", 'case', $case_id);
            $response['updated'] = true;
        } else {
            http_response_code(422);
            $response['error'] = 'Invalid case_id or status';
        }
        break;

    case 'user.created':
        // n8n confirms a new user was processed downstream
        $user_id = (int)($data['user_id'] ?? 0);
        log_activity("webhook_user_created", 'user', $user_id);
        $response['acknowledged'] = true;
        break;

    case 'commission.approved':
        // n8n approves a commission from payment confirmation
        $comm_id = (int)($data['commission_id'] ?? 0);
        if ($comm_id) {
            $pdo->prepare('UPDATE commissions SET status="approved", updated_at=NOW() WHERE id=?')
                ->execute([$comm_id]);
            log_activity("webhook_commission_approved", 'commission', $comm_id);
            $response['approved'] = true;
        }
        break;

    case 'ping':
        // Health check from n8n
        $response['pong'] = true;
        $response['timestamp'] = date('c');
        break;

    default:
        http_response_code(422);
        $response['error'] = "Unknown event: $event";
        break;
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
