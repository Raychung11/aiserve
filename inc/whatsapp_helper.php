<?php
declare(strict_types=1);

function wa_link(string $phone = '', string $message = ''): string {
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone) ?? '';
    return 'https://wa.me/' . $cleanPhone . '?text=' . rawurlencode($message);
}

function wa_fill_template(string $template, array $data = []): string {
    $replace = [
        '[Name]' => $data['name'] ?? '',
        '[Company]' => $data['company'] ?? '',
        '[Interest]' => $data['interest'] ?? '',
        '[Email]' => $data['email'] ?? '',
        '[Phone]' => $data['phone'] ?? '',
    ];

    return strtr($template, $replace);
}