<?php
/**
 * Hierarchy — consultant org structure helpers
 * Levels: principal → associate → manager
 */
class Hierarchy {

    /** Direct reports one level down */
    public static function getDirectReports(int $userId): array {
        return Database::fetchAll(
            'SELECT id, name, email, role, created_at FROM users WHERE parent_id = ? AND is_active = 1 ORDER BY name',
            [$userId]
        );
    }

    /** All companies directly linked to one user via user_companies */
    public static function getDirectCompanies(int $userId): array {
        return Database::fetchAll(
            'SELECT c.* FROM companies c
             JOIN user_companies uc ON c.id = uc.company_id
             WHERE uc.user_id = ?
             ORDER BY c.name',
            [$userId]
        );
    }

    /**
     * All companies visible to an associate: their own + all managers under them
     */
    public static function getAssociateCompanies(int $associateId): array {
        $managers = self::getDirectReports($associateId);
        $userIds  = array_merge([$associateId], array_column($managers, 'id'));
        return self::companiesForUserIds($userIds);
    }

    /**
     * All companies visible to a principal: every user under them recursively
     */
    public static function getPrincipalCompanies(int $principalId): array {
        $userIds = self::descendantIds($principalId);
        return self::companiesForUserIds($userIds);
    }

    /** Convenience dispatcher */
    public static function getAccessibleCompanies(int $userId, string $role): array {
        return match ($role) {
            'principal' => self::getPrincipalCompanies($userId),
            'associate' => self::getAssociateCompanies($userId),
            default     => self::getDirectCompanies($userId),
        };
    }

    /**
     * Associates under a principal with lightweight stats
     */
    public static function getAssociatesWithStats(int $principalId): array {
        $associates = self::getDirectReports($principalId);
        foreach ($associates as &$assoc) {
            $managers = self::getDirectReports($assoc['id']);
            $companies = self::getAssociateCompanies($assoc['id']);
            $assoc['manager_count']  = count($managers);
            $assoc['company_count']  = count($companies);
            $assoc['avg_esg']        = self::avgEsgScore($companies);
        }
        unset($assoc);
        return $associates;
    }

    /**
     * Managers under an associate with company counts
     */
    public static function getManagersWithStats(int $associateId): array {
        $managers = self::getDirectReports($associateId);
        foreach ($managers as &$mgr) {
            $companies = self::getDirectCompanies($mgr['id']);
            $mgr['company_count'] = count($companies);
            $mgr['avg_esg']       = self::avgEsgScore($companies);
        }
        unset($mgr);
        return $managers;
    }

    /**
     * Portfolio summary for an associate or manager: companies with per-company ESG scores
     */
    public static function getPortfolioSummary(array $companies): array {
        $out = [];
        foreach ($companies as $co) {
            if (empty($co['framework'])) continue;
            $period = $co['reporting_year'] ?? date('Y');
            $stats  = ESGDataManager::getCompletionStats($co['id'], $co['framework'], $period);
            $score  = ESGDataManager::calcOverallScore($stats);
            $out[]  = array_merge($co, [
                'overall_score' => $score,
                'env_score'     => $stats['ENVIRONMENT']['score'] ?? 0,
                'soc_score'     => $stats['SOCIAL']['score']      ?? 0,
                'gov_score'     => $stats['GOVERNANCE']['score']  ?? 0,
                'critical_gaps' => (function(int $cid): int {
                    $row = Database::fetchOne(
                        "SELECT analysis_json FROM gap_analyses WHERE company_id = ? ORDER BY generated_at DESC LIMIT 1",
                        [$cid]
                    );
                    if (!$row || empty($row['analysis_json'])) return 0;
                    $gaps = json_decode($row['analysis_json'], true)['gaps'] ?? [];
                    return count(array_filter($gaps, fn($g) => ($g['priority'] ?? '') === 'critical'));
                })($co['id']),
            ]);
        }
        usort($out, fn($a, $b) => $a['overall_score'] <=> $b['overall_score']);
        return $out;
    }

    // ─── private helpers ───────────────────────────────────────────

    private static function companiesForUserIds(array $userIds): array {
        if (empty($userIds)) return [];
        $ph = implode(',', array_fill(0, count($userIds), '?'));
        return Database::fetchAll(
            "SELECT DISTINCT c.*, u.name AS owner_name, uc.user_id AS owner_id
             FROM companies c
             JOIN user_companies uc ON c.id = uc.company_id
             JOIN users u ON uc.user_id = u.id
             WHERE uc.user_id IN ($ph)
             ORDER BY c.name",
            $userIds
        );
    }

    private static function descendantIds(int $userId): array {
        $ids    = [$userId];
        $queue  = [$userId];
        while ($queue) {
            $next  = array_shift($queue);
            $kids  = Database::fetchAll('SELECT id FROM users WHERE parent_id = ? AND is_active = 1', [$next]);
            foreach ($kids as $k) {
                $ids[]   = $k['id'];
                $queue[] = $k['id'];
            }
        }
        return $ids;
    }

    private static function avgEsgScore(array $companies): int {
        $total = $count = 0;
        foreach ($companies as $co) {
            if (empty($co['framework'])) continue;
            $period = $co['reporting_year'] ?? date('Y');
            $stats  = ESGDataManager::getCompletionStats($co['id'], $co['framework'], $period);
            $total += ESGDataManager::calcOverallScore($stats);
            $count++;
        }
        return $count ? (int) round($total / $count) : 0;
    }
}
