<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function site_setting_value(string $key, string $default = ''): string {
    static $cache = null;

    if (!is_array($cache)) {
        $cache = [];
        try {
            $stmt = db()->query("SELECT setting_key, setting_value FROM site_settings");
            foreach ($stmt->fetchAll() as $row) {
                $cache[(string)$row['setting_key']] = (string)($row['setting_value'] ?? '');
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }

    return $cache[$key] ?? $default;
}

function aiserve_site_knowledge_from_db(): array {
    $docs = [];

    $docs[] = [
        'source_type' => 'core',
        'title' => 'About AiServe.my',
        'url' => 'https://aiserve.my/',
        'content' => implode("\n", array_filter([
            'AiServe.my is the AI services website of SLV Group Sdn Bhd.',
            'AiServe focuses on AI customer service, AI workflow support, AI + BI business enablement, and practical business deployment.',
            'The business positioning emphasizes real deployment, operational usefulness, and industry-specific AI solutions.',
            site_setting_value('hero_banner_title', ''),
            site_setting_value('hero_banner_subtitle', ''),
        ])),
    ];

    try {
        $stmt = db()->query("
            SELECT title, slug, short_description, full_description, badge_text
            FROM industry_pages
            WHERE status = 'published'
            ORDER BY is_featured DESC, sort_order ASC, id DESC
        ");

        foreach ($stmt->fetchAll() as $row) {
            $docs[] = [
                'source_type' => 'industry',
                'title' => 'Industry: ' . (string)$row['title'],
                'url' => 'https://aiserve.my/industries.php',
                'content' => implode("\n", array_filter([
                    'Industry title: ' . (string)$row['title'],
                    'Slug: ' . (string)($row['slug'] ?? ''),
                    'Badge: ' . (string)($row['badge_text'] ?? ''),
                    (string)($row['short_description'] ?? ''),
                    (string)($row['full_description'] ?? ''),
                ])),
                'meta' => [
                    'industry_title' => (string)$row['title'],
                    'slug' => (string)($row['slug'] ?? ''),
                    'short_description' => (string)($row['short_description'] ?? ''),
                ],
            ];
        }
    } catch (Throwable $e) {
    }

    try {
        $stmt = db()->query("
            SELECT title, slug, category, short_description, badge_text, live_url
            FROM demo_sites
            WHERE status = 'published'
            ORDER BY is_featured DESC, sort_order ASC, id DESC
        ");

        foreach ($stmt->fetchAll() as $row) {
            $docs[] = [
                'source_type' => 'demo',
                'title' => 'Demo: ' . (string)$row['title'],
                'url' => 'https://aiserve.my/demos.php',
                'content' => implode("\n", array_filter([
                    'Demo title: ' . (string)$row['title'],
                    'Slug: ' . (string)($row['slug'] ?? ''),
                    'Category: ' . (string)($row['category'] ?? ''),
                    'Badge: ' . (string)($row['badge_text'] ?? ''),
                    (string)($row['short_description'] ?? ''),
                    'Live URL: ' . (string)($row['live_url'] ?? ''),
                ])),
                'meta' => [
                    'demo_title' => (string)$row['title'],
                    'category' => (string)($row['category'] ?? ''),
                    'short_description' => (string)($row['short_description'] ?? ''),
                    'live_url' => (string)($row['live_url'] ?? ''),
                ],
            ];
        }
    } catch (Throwable $e) {
    }

    try {
        $stmt = db()->query("
            SELECT title, slug, company_name, category, short_description, full_description,
                   badge_text, tag_1, tag_2, tag_3, cta_text, cta_link
            FROM ai_cs_showcases
            WHERE status = 'published'
            ORDER BY is_featured DESC, sort_order ASC, id DESC
        ");

        foreach ($stmt->fetchAll() as $row) {
            $docs[] = [
                'source_type' => 'showcase',
                'title' => 'AI Customer Service Showcase: ' . (string)$row['title'],
                'url' => 'https://aiserve.my/ai-customer-service-demo.php',
                'content' => implode("\n", array_filter([
                    'Showcase title: ' . (string)$row['title'],
                    'Slug: ' . (string)($row['slug'] ?? ''),
                    'Company: ' . (string)($row['company_name'] ?? ''),
                    'Category: ' . (string)($row['category'] ?? ''),
                    'Badge: ' . (string)($row['badge_text'] ?? ''),
                    (string)($row['short_description'] ?? ''),
                    (string)($row['full_description'] ?? ''),
                    'Tag: ' . (string)($row['tag_1'] ?? ''),
                    'Tag: ' . (string)($row['tag_2'] ?? ''),
                    'Tag: ' . (string)($row['tag_3'] ?? ''),
                    'CTA Text: ' . (string)($row['cta_text'] ?? ''),
                    'CTA Link: ' . (string)($row['cta_link'] ?? ''),
                ])),
                'meta' => [
                    'title' => (string)$row['title'],
                    'company_name' => (string)($row['company_name'] ?? ''),
                    'category' => (string)($row['category'] ?? ''),
                    'short_description' => (string)($row['short_description'] ?? ''),
                ],
            ];
        }
    } catch (Throwable $e) {
    }

    try {
        $stmt = db()->query("
            SELECT title, slug, excerpt, content
            FROM blog_posts
            WHERE status = 'published'
            ORDER BY published_at DESC, id DESC
            LIMIT 50
        ");

        foreach ($stmt->fetchAll() as $row) {
            $slug = trim((string)($row['slug'] ?? ''));
            $docs[] = [
                'source_type' => 'blog',
                'title' => 'Blog: ' . (string)$row['title'],
                'url' => $slug !== '' ? 'https://aiserve.my/blog/' . $slug : 'https://aiserve.my/blog.php',
                'content' => implode("\n", array_filter([
                    'Blog title: ' . (string)$row['title'],
                    (string)($row['excerpt'] ?? ''),
                    mb_substr((string)($row['content'] ?? ''), 0, 3000),
                ])),
                'meta' => [
                    'blog_title' => (string)$row['title'],
                    'excerpt' => (string)($row['excerpt'] ?? ''),
                ],
            ];
        }
    } catch (Throwable $e) {
    }

    try {
        $stmt = db()->query("
            SELECT company_name, badge_text, website_url
            FROM client_logo_strips
            WHERE status = 'published'
            ORDER BY sort_order ASC, id DESC
        ");

        $lines = [];
        $companies = [];

        foreach ($stmt->fetchAll() as $row) {
            $company = trim((string)($row['company_name'] ?? ''));
            $badge = trim((string)($row['badge_text'] ?? ''));
            $url = trim((string)($row['website_url'] ?? ''));

            if ($company === '') {
                continue;
            }

            $companies[] = $company;

            $line = $company;
            if ($badge !== '') $line .= ' | ' . $badge;
            if ($url !== '') $line .= ' | ' . $url;
            $lines[] = $line;
        }

        if ($lines) {
            $docs[] = [
                'source_type' => 'client',
                'title' => 'Supported Brands and Organisations',
                'url' => 'https://aiserve.my/ai-customer-service-demo.php',
                'content' => "Selected supported brands and organisations:\n" . implode("\n", $lines),
                'meta' => [
                    'companies' => $companies,
                ],
            ];
        }
    } catch (Throwable $e) {
    }

    return $docs;
}