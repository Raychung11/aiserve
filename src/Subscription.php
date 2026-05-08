<?php
/**
 * Subscription — plan + indicator collection access control
 */
class Subscription {

    private static array $plansCache      = [];
    private static array $collectionsCache = [];

    public static function getAllPlans(): array {
        if (empty(self::$plansCache)) {
            self::$plansCache = require __DIR__ . '/../config/plans.php';
        }
        return self::$plansCache;
    }

    public static function getAllCollections(): array {
        if (empty(self::$collectionsCache)) {
            self::$collectionsCache = require __DIR__ . '/../config/collections.php';
        }
        return self::$collectionsCache;
    }

    /**
     * Get the active plan for a company.
     * Priority: active subscription → 14-day new-company trial → starter (free).
     */
    public static function getActivePlan(int $companyId): array {
        $plans = self::getAllPlans();

        // Check active subscription in DB
        $sub = Database::fetchOne(
            "SELECT * FROM subscriptions
             WHERE company_id = ? AND status = 'active'
               AND (expires_at IS NULL OR expires_at > NOW())
             ORDER BY created_at DESC LIMIT 1",
            [$companyId]
        );

        if ($sub && isset($plans[$sub['plan_code']])) {
            $plan = $plans[$sub['plan_code']];
            $plan['_subscription'] = $sub;
            $plan['_source'] = 'subscription';
            return $plan;
        }

        // 14-day trial for brand-new companies
        $company = Database::fetchOne('SELECT created_at FROM companies WHERE id = ?', [$companyId]);
        if ($company) {
            $daysSince = (time() - strtotime($company['created_at'])) / 86400;
            if ($daysSince <= 14) {
                $plan = $plans['professional'];
                $plan['_source']      = 'trial';
                $plan['_trial_ends']  = date('Y-m-d', strtotime($company['created_at']) + (14 * 86400));
                $plan['_days_left']   = max(0, (int)(14 - $daysSince));
                return $plan;
            }
        }

        $plan = $plans['starter'];
        $plan['_source'] = 'free';
        return $plan;
    }

    /**
     * Returns array of unlocked indicator IDs, or the string 'all' for unrestricted plans.
     */
    public static function getUnlockedIndicatorIds(int $companyId): array|string {
        $plan = self::getActivePlan($companyId);

        if ($plan['indicator_ids'] === 'all') {
            return 'all';
        }

        $unlocked = $plan['indicator_ids'];

        // Add indicators unlocked by purchased collections
        $activeCollections = self::getActiveCollections($companyId);
        $allCollections    = self::getAllCollections();
        foreach ($activeCollections as $code) {
            if (isset($allCollections[$code])) {
                $unlocked = array_merge($unlocked, $allCollections[$code]['indicator_ids']);
            }
        }

        return array_unique($unlocked);
    }

    public static function isIndicatorUnlocked(int $companyId, string $indicatorId): bool {
        $ids = self::getUnlockedIndicatorIds($companyId);
        return $ids === 'all' || in_array($indicatorId, (array)$ids, true);
    }

    /** Returns array of collection codes active for this company */
    public static function getActiveCollections(int $companyId): array {
        $rows = Database::fetchAll(
            "SELECT collection_code FROM company_collections
             WHERE company_id = ? AND status = 'active'
               AND (expires_at IS NULL OR expires_at > NOW())",
            [$companyId]
        );
        return array_column($rows, 'collection_code');
    }

    /** Admin: activate or extend a plan for a company */
    public static function activatePlan(int $companyId, string $planCode, int $months, int $adminUserId): bool {
        $plans = self::getAllPlans();
        if (!isset($plans[$planCode])) return false;

        // Expire existing active subscriptions
        Database::query(
            "UPDATE subscriptions SET status = 'cancelled' WHERE company_id = ? AND status = 'active'",
            [$companyId]
        );

        Database::insert('subscriptions', [
            'company_id'  => $companyId,
            'plan_code'   => $planCode,
            'status'      => 'active',
            'starts_at'   => date('Y-m-d H:i:s'),
            'expires_at'  => date('Y-m-d H:i:s', strtotime("+{$months} months")),
            'created_by'  => $adminUserId,
        ]);

        Database::insert('activity_log', [
            'user_id'     => $adminUserId,
            'company_id'  => $companyId,
            'action'      => 'PLAN_ACTIVATED',
            'description' => "Plan '{$planCode}' activated for {$months} months",
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        return true;
    }

    /** Admin: activate a collection for a company */
    public static function activateCollection(int $companyId, string $collectionCode, int $months, int $adminUserId): bool {
        $collections = self::getAllCollections();
        if (!isset($collections[$collectionCode])) return false;

        // Upsert: update or insert
        $existing = Database::fetchOne(
            'SELECT id FROM company_collections WHERE company_id = ? AND collection_code = ?',
            [$companyId, $collectionCode]
        );

        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$months} months"));

        if ($existing) {
            Database::update('company_collections',
                ['status' => 'active', 'expires_at' => $expiresAt],
                'id = ?', [$existing['id']]
            );
        } else {
            Database::insert('company_collections', [
                'company_id'      => $companyId,
                'collection_code' => $collectionCode,
                'status'          => 'active',
                'expires_at'      => $expiresAt,
                'purchased_by'    => $adminUserId,
            ]);
        }

        Database::insert('activity_log', [
            'user_id'     => $adminUserId,
            'company_id'  => $companyId,
            'action'      => 'COLLECTION_ACTIVATED',
            'description' => "Collection '{$collectionCode}' activated for {$months} months",
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        return true;
    }

    public static function planBadgeClass(string $planCode): string {
        return ['starter' => 'secondary', 'standard' => 'success', 'professional' => 'purple'][$planCode] ?? 'secondary';
    }
}
