<?php
declare(strict_types=1);

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function admin_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function admin_require_login(): void {
    if (!admin_logged_in()) {
        redirect('/admin_login.php');
    }
}

function admin_user(): ?array {
    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    return [
        'id'    => $_SESSION['admin_id'] ?? null,
        'name'  => $_SESSION['admin_name'] ?? '',
        'email' => $_SESSION['admin_email'] ?? '',
        'role'  => $_SESSION['admin_role'] ?? '',
    ];
}

function count_table(string $table): int {
    $allowed = [
        'contacts', 'admin_users', 'email_campaigns', 'email_campaign_recipients',
        'site_settings', 'content_blocks', 'blog_posts', 'landing_pages',
        'campaign_queue', 'contact_notes', 'whatsapp_templates', 'media_library',
        'wa_webhook_logs', 'wa_contacts', 'wa_conversations', 'wa_messages',
        'wa_action_queue', 'wa_outbound_logs', 'wa_ai_queue', 'wa_ai_logs',
        'wa_followups', 'wa_lead_profiles', 'wa_internal_notifications',
        'wa_conversation_memory', 'wa_conversation_notes', 'wa_assets',
        'wa_sales_tasks', 'wa_quote_requests', 'wa_sla_logs',
        'wa_quote_approvals', 'wa_file_requests', 'wa_voice_notes', 'wa_department_routes',
        'wa_generated_quotes', 'wa_template_library', 'wa_knowledge_base'
    ];
    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    $stmt = db()->query("SELECT COUNT(*) AS total FROM {$table}");
    $row = $stmt->fetch();
    return (int)($row['total'] ?? 0);
}

function count_contacts_by_status(string $status): int {
    $stmt = db()->prepare("SELECT COUNT(*) AS total FROM contacts WHERE status = ?");
    $stmt->execute([$status]);
    $row = $stmt->fetch();
    return (int)($row['total'] ?? 0);
}

function count_campaigns_by_status(string $status): int {
    $stmt = db()->prepare("SELECT COUNT(*) AS total FROM email_campaigns WHERE status = ?");
    $stmt->execute([$status]);
    $row = $stmt->fetch();
    return (int)($row['total'] ?? 0);
}

function count_recipients_by_send_status(string $status): int {
    $stmt = db()->prepare("SELECT COUNT(*) AS total FROM email_campaign_recipients WHERE send_status = ?");
    $stmt->execute([$status]);
    $row = $stmt->fetch();
    return (int)($row['total'] ?? 0);
}

function contact_status_options(): array {
    return ['new', 'contacted', 'qualified', 'closed'];
}

function campaign_status_options(): array {
    return ['draft', 'ready', 'sent'];
}

function ensure_unsubscribe_token(int $contactId): string {
    $stmt = db()->prepare("SELECT unsubscribe_token FROM contacts WHERE id = ? LIMIT 1");
    $stmt->execute([$contactId]);
    $row = $stmt->fetch();

    if (!empty($row['unsubscribe_token'])) {
        return (string)$row['unsubscribe_token'];
    }

    $token = bin2hex(random_bytes(16));
    $stmt = db()->prepare("UPDATE contacts SET unsubscribe_token = ? WHERE id = ?");
    $stmt->execute([$token, $contactId]);

    return $token;
}

function get_setting(string $key, string $default = ''): string {
    $stmt = db()->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ? LIMIT 1");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['setting_value'] : $default;
}

function set_setting(string $key, string $value): void {
    $stmt = db()->prepare("
        INSERT INTO site_settings (setting_key, setting_value, updated_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
    ");
    $stmt->execute([$key, $value]);
}

function campaign_already_logged(int $campaignId, int $contactId): bool {
    $stmt = db()->prepare("
        SELECT id FROM email_campaign_recipients
        WHERE campaign_id = ? AND contact_id = ?
        LIMIT 1
    ");
    $stmt->execute([$campaignId, $contactId]);
    return (bool)$stmt->fetch();
}

function make_slug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'page-' . time();
}