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

        $companyId = Database::insert('companies', [
            'name'             => htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8'),
            'registration_no'  => $data['registration_no'] ?? null,
            'industry'         => $data['industry'],
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
        $allowed = ['name', 'registration_no', 'industry', 'revenue_tier', 'employee_count',
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
     * Flatten all indicators for a given framework
     */
    public static function getFrameworkIndicators(string $framework): array {
        $indicators = [];
        $frameworks = [];
        if ($framework === 'BURSA_SEDG' || $framework === 'BOTH') {
            $sedg = require __DIR__ . '/../config/indicators/bursa_sedg.php';
            foreach ($sedg as $cat => $items) {
                foreach ($items as $item) {
                    $item['category']  = $cat;
                    $item['framework'] = 'BURSA_SEDG';
                    $indicators[]      = $item;
                }
            }
        }
        if ($framework === 'GRI' || $framework === 'BOTH') {
            $gri = require __DIR__ . '/../config/indicators/gri.php';
            foreach ($gri as $cat => $items) {
                foreach ($items as $item) {
                    // Avoid duplicates in BOTH mode
                    $existing = array_search($item['indicator_id'], array_column($indicators, 'indicator_id'));
                    if ($existing === false) {
                        $item['category']  = $cat;
                        $item['framework'] = 'GRI';
                        $indicators[]      = $item;
                    }
                }
            }
        }
        return $indicators;
    }
}
