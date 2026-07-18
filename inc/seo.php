<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

/*
|--------------------------------------------------------------------------
| SEO + AI-search helpers (single source of truth for <head> metadata)
|--------------------------------------------------------------------------
*/

/**
 * Per-page SEO title/description, overridable from the admin SEO editor.
 */
function seo_meta(string $pageKey, string $defaultTitle, string $defaultDescription): array {
    $stmt = db()->prepare("SELECT seo_title, seo_description FROM seo_pages WHERE page_key = ? LIMIT 1");
    $stmt->execute([$pageKey]);
    $row = $stmt->fetch();

    return [
        'title' => $row && !empty($row['seo_title']) ? (string)$row['seo_title'] : $defaultTitle,
        'description' => $row && !empty($row['seo_description']) ? (string)$row['seo_description'] : $defaultDescription,
    ];
}

/** Site base URL with no trailing slash (APP_URL keeps a trailing slash). */
function site_base_url(): string {
    return rtrim(APP_URL, '/');
}

/** Build an absolute URL from a site-relative path. */
function site_url(string $path = ''): string {
    if ($path === '') {
        return site_base_url() . '/';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path; // already absolute
    }
    return site_base_url() . '/' . ltrim($path, '/');
}

/**
 * Canonical URL for the current request (path only, query string dropped so
 * tracking/pagination params don't fragment ranking signals).
 */
function canonical_url(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return site_base_url() . $path;
}

/** Default Open Graph image (admin setting -> site logo -> empty). */
function seo_default_og_image(): string {
    $img = get_setting('seo_default_og_image', '');
    if ($img === '') {
        $img = get_setting('site_logo_url', '');
    }
    return $img !== '' ? site_url($img) : '';
}

/**
 * Render the full SEO <head> block: canonical, robots, Open Graph, Twitter
 * cards, theme-color and JSON-LD structured data.
 *
 * Reads (all optional) globals set by the page before including the header:
 *   $pageTitle, $pageDescription, $canonicalUrl, $ogImage, $ogType,
 *   $metaRobots, $pageJsonLd (string of one or more <script> blocks)
 */
function seo_head_tags(): string {
    $title       = (string)($GLOBALS['pageTitle'] ?? 'AiServe.my');
    $description = (string)($GLOBALS['pageDescription'] ?? '');
    $canonical   = (string)($GLOBALS['canonicalUrl'] ?? canonical_url());
    $ogType      = (string)($GLOBALS['ogType'] ?? 'website');
    $robots      = (string)($GLOBALS['metaRobots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
    $ogImage     = (string)($GLOBALS['ogImage'] ?? seo_default_og_image());
    $pageJsonLd  = (string)($GLOBALS['pageJsonLd'] ?? '');

    $siteName = 'AiServe.my';

    $out  = '<link rel="canonical" href="' . h($canonical) . '">' . "\n";
    $out .= '<meta name="robots" content="' . h($robots) . '">' . "\n";
    $out .= '<meta name="theme-color" content="#6d28d9">' . "\n";
    $out .= '<meta name="author" content="' . h(COMPANY_NAME) . '">' . "\n";

    // Open Graph
    $out .= '<meta property="og:site_name" content="' . h($siteName) . '">' . "\n";
    $out .= '<meta property="og:type" content="' . h($ogType) . '">' . "\n";
    $out .= '<meta property="og:title" content="' . h($title) . '">' . "\n";
    $out .= '<meta property="og:description" content="' . h($description) . '">' . "\n";
    $out .= '<meta property="og:url" content="' . h($canonical) . '">' . "\n";
    $out .= '<meta property="og:locale" content="en_MY">' . "\n";
    if ($ogImage !== '') {
        $out .= '<meta property="og:image" content="' . h($ogImage) . '">' . "\n";
    }

    // Twitter
    $out .= '<meta name="twitter:card" content="' . ($ogImage !== '' ? 'summary_large_image' : 'summary') . '">' . "\n";
    $out .= '<meta name="twitter:title" content="' . h($title) . '">' . "\n";
    $out .= '<meta name="twitter:description" content="' . h($description) . '">' . "\n";
    if ($ogImage !== '') {
        $out .= '<meta name="twitter:image" content="' . h($ogImage) . '">' . "\n";
    }

    // Sitewide structured data + any page-specific JSON-LD
    $out .= seo_jsonld_block(seo_organization_data()) . "\n";
    $out .= seo_jsonld_block(seo_website_data()) . "\n";
    if ($pageJsonLd !== '') {
        $out .= $pageJsonLd . "\n";
    }

    return $out;
}

/** Wrap an associative array as a JSON-LD <script> tag. */
function seo_jsonld_block(array $data): string {
    $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return '';
    }
    // Escape the closing tag sequence to keep the script block well-formed.
    $json = str_replace('</', '<\/', $json);
    return '<script type="application/ld+json">' . $json . '</script>';
}

/** Organization entity — helps search + AI engines identify the brand. */
function seo_organization_data(): array {
    $logo = get_setting('site_logo_url', '');
    $org = [
        '@context'  => 'https://schema.org',
        '@type'     => 'Organization',
        'name'      => 'AiServe.my',
        'legalName' => COMPANY_NAME,
        'url'       => site_url(),
        'description' => 'AiServe.my is the AI Business Operating System by ' . COMPANY_NAME
            . ', building AI-powered systems for customer service, workflow automation, reporting and operational intelligence.',
        'contactPoint' => [
            '@type'       => 'ContactPoint',
            'telephone'   => '+60 13-386 6827',
            'contactType' => 'sales',
            'email'       => 'hello@aiserve.my',
            'areaServed'  => 'MY',
            'availableLanguage' => ['en', 'ms'],
        ],
        'sameAs' => [
            'https://wa.me/60133866827',
        ],
    ];
    if ($logo !== '') {
        $org['logo'] = site_url($logo);
    }
    return $org;
}

/** WebSite entity with a SearchAction so engines can surface site search. */
function seo_website_data(): array {
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => 'AiServe.my',
        'url'      => site_url(),
        'potentialAction' => [
            '@type'  => 'SearchAction',
            'target' => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => site_url('search.php') . '?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

/** Article/BlogPosting structured data for a single blog/case post. */
function seo_article_data(array $post): array {
    $title = (string)($post['title'] ?? '');
    $slug  = (string)($post['slug'] ?? '');
    $excerpt = trim((string)($post['excerpt'] ?? ''));
    $image = trim((string)($post['featured_image'] ?? ''));
    $published = (string)($post['published_at'] ?? $post['created_at'] ?? '');
    $updated = (string)($post['updated_at'] ?? $published);

    $data = [
        '@context' => 'https://schema.org',
        '@type'    => 'BlogPosting',
        'headline' => $title,
        'mainEntityOfPage' => site_url('blog/' . rawurlencode($slug)),
        'url'      => site_url('blog/' . rawurlencode($slug)),
        'author' => [
            '@type' => 'Organization',
            'name'  => 'AiServe.my',
        ],
        'publisher' => [
            '@type'     => 'Organization',
            'name'      => 'AiServe.my',
            'legalName' => COMPANY_NAME,
        ],
    ];
    if ($excerpt !== '') {
        $data['description'] = $excerpt;
    }
    if ($image !== '') {
        $data['image'] = site_url($image);
    }
    if ($published !== '') {
        $ts = strtotime($published);
        if ($ts) {
            $data['datePublished'] = date('c', $ts);
        }
    }
    if ($updated !== '') {
        $ts = strtotime($updated);
        if ($ts) {
            $data['dateModified'] = date('c', $ts);
        }
    }
    return $data;
}
