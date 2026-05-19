<?php
/**
 * Company model
 * CRUD + access control for multi-tenant support
 */
class Company {

    /**
     * Create a new company and assign the creator
     */
    public static function create(array $data, int $userId): array {
        $required = ['name', 'industry', 'revenue_tier', 'employee_count', 'framework'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '{$field}' is required."];
            }
        }

        $validSectors = require __DIR__ . '/../config/bursa_sectors.php';
        $companyId = Database::insert('companies', [
            'name'             => htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8'),
            'registration_no'  => $data['registration_no'] ?? null,
            'industry'         => $data['industry'],
            'bursa_sector'     => in_array($data['bursa_sector'] ?? '', $validSectors) ? $data['bursa_sector'] : null,
            'reporting_scope'  => in_array($data['reporting_scope'] ?? '', ['hq','factory','group']) ? $data['reporting_scope'] : 'hq',
            'revenue_tier'     => $data['revenue_tier'],
            'employee_count'   => (int)$data['employee_count'],
            'framework'        => $data['framework'],
            'reporting_year'   => $data['reporting_year'] ?? date('Y'),
            'created_by'       => $userId,
            'is_pre_ipo'       => (int)($data['is_pre_ipo'] ?? 0),
        ]);

        // Assign creator as owner
        Database::insert('user_companies', [
            'user_id'    => $userId,
            'company_id' => $companyId,
            'role'       => 'owner',
        ]);

        Database::insert('activity_log', [
            'user_id'     => $userId,
            'company_id'  => $companyId,
            'action'      => 'COMPANY_CREATED',
            'description' => 'Company created: ' . $data['name'],
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        return ['success' => true, 'company_id' => $companyId];
    }

    /**
     * Get all companies accessible to a user
     */
    public static function getForUser(int $userId, string $role): array {
        if ($role === 'admin') {
            return Database::fetchAll('SELECT * FROM companies ORDER BY name ASC');
        }
        return Database::fetchAll(
            'SELECT c.*, uc.role AS user_role
             FROM companies c
             JOIN user_companies uc ON c.id = uc.company_id
             WHERE uc.user_id = ?
             ORDER BY c.name ASC',
            [$userId]
        );
    }

    /**
     * Get a single company by ID (with access check)
     */
    public static function getById(int $companyId, int $userId, string $role): ?array {
        if ($role === 'admin') {
            return Database::fetchOne('SELECT * FROM companies WHERE id = ?', [$companyId]);
        }
        return Database::fetchOne(
            'SELECT c.* FROM companies c
             JOIN user_companies uc ON c.id = uc.company_id
             WHERE c.id = ? AND uc.user_id = ?',
            [$companyId, $userId]
        );
    }

    /**
     * Update company
     */
    public static function update(int $companyId, array $data): bool {
        $allowed = ['name', 'registration_no', 'industry', 'bursa_sector', 'reporting_scope',
                    'report_level', 'revenue_tier', 'employee_count',
                    'framework', 'reporting_year', 'is_pre_ipo'];
        $update  = array_intersect_key($data, array_flip($allowed));
        if (empty($update)) return false;
        Database::update('companies', $update, 'id = ?', [$companyId]);
        return true;
    }

    /**
     * Assign a user to a company (consultant workflow)
     */
    public static function assignUser(int $companyId, int $userId, string $role = 'editor'): bool {
        try {
            Database::insert('user_companies', [
                'user_id'    => $userId,
                'company_id' => $companyId,
                'role'       => $role,
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get ESG completion summary for dashboard
     */
    public static function getESGSummary(int $companyId, string $framework, string $period = '2024'): array {
        $indicators = self::getFrameworkIndicators($framework);
        $entered    = Database::fetchAll(
            'SELECT indicator_id FROM esg_data WHERE company_id = ? AND period = ? AND value IS NOT NULL AND value != ""',
            [$companyId, $period]
        );
        $enteredIds = array_column($entered, 'indicator_id');

        $total    = count($indicators);
        $done     = count(array_intersect($enteredIds, array_column($indicators, 'indicator_id')));
        $required = array_filter($indicators, fn($i) => $i['required']);
        $reqDone  = count(array_intersect($enteredIds, array_column(array_values($required), 'indicator_id')));

        $byCategory = ['ENVIRONMENT' => ['total' => 0, 'done' => 0],
                       'SOCIAL'      => ['total' => 0, 'done' => 0],
                       'GOVERNANCE'  => ['total' => 0, 'done' => 0]];
        foreach ($indicators as $ind) {
            $cat = $ind['category'];
            $byCategory[$cat]['total']++;
            if (in_array($ind['indicator_id'], $enteredIds)) $byCategory[$cat]['done']++;
        }

        return [
            'total'      => $total,
            'completed'  => $done,
            'score'      => $total > 0 ? round(($done / $total) * 100, 1) : 0,
            'req_total'  => count($required),
            'req_done'   => $reqDone,
            'by_category'=> $byCategory,
        ];
    }

    /**
     * Map framework ID to its indicator file(s)
     * Returns array of [framework_id => file_key] pairs to load
     */
    private static function resolveIndicatorFiles(string $framework): array {
        // Map each framework ID to its config/indicators/ filename (without .php)
        $fileMap = [
            'BURSA_SEDG'         => 'bursa_sedg',
            'GRI'                => 'gri',
            'ISSB'               => 'issb',
            'TCFD'               => 'tcfd',
            'UN_SDGS'            => 'un_sdgs',
            'SASB_MANUFACTURING' => 'sasb_manufacturing',
            'SASB_FOOD'          => 'sasb_food',
            'SASB_TECH'          => 'sasb_tech',
            'CDP'                => 'cdp',
            'ESRS'               => 'esrs',
        ];

        // Legacy combined modes
        if ($framework === 'BOTH') {
            return ['BURSA_SEDG' => 'bursa_sedg', 'GRI' => 'gri'];
        }

        if (isset($fileMap[$framework])) {
            return [$framework => $fileMap[$framework]];
        }

        return [];
    }

    /**
     * Flatten all indicators for a given framework
     * Supports all 10 frameworks + legacy BOTH mode
     */
    public static function getFrameworkIndicators(string $framework): array {
        $indicators  = [];
        $usedIds     = [];
        $files       = self::resolveIndicatorFiles($framework);

        foreach ($files as $frameworkId => $fileName) {
            $path = __DIR__ . '/../config/indicators/' . $fileName . '.php';
            if (!file_exists($path)) continue;

            $data = require $path;
            foreach ($data as $cat => $items) {
                foreach ($items as $item) {
                    if (empty($item)) continue;
                    // Skip duplicates when merging frameworks
                    if (in_array($item['indicator_id'], $usedIds, true)) continue;
                    $item['category']  = $cat;
                    $item['framework'] = $frameworkId;
                    $indicators[]      = $item;
                    $usedIds[]         = $item['indicator_id'];
                }
            }
        }

        return $indicators;
    }

    /**
     * Get all available frameworks from registry
     */
    public static function getAllFrameworks(): array {
        static $cache = null;
        if ($cache === null) {
            $cache = require __DIR__ . '/../config/frameworks.php';
        }
        return $cache;
    }

    /**
     * Get a single framework definition by ID
     */
    public static function getFramework(string $frameworkId): ?array {
        $all = self::getAllFrameworks();
        return $all[$frameworkId] ?? null;
    }

    /**
     * Get frameworks grouped by category for UI display
     */
    public static function getFrameworksByCategory(): array {
        $grouped = [];
        foreach (self::getAllFrameworks() as $fw) {
            $grouped[$fw['category_label']][] = $fw;
        }
        return $grouped;
    }
}
