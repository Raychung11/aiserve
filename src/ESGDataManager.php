<?php
/**
 * ESG Data Manager
 * Save, retrieve, and validate ESG indicator data entries
 */
class ESGDataManager {

    /**
     * Save or update a single indicator value
     */
    public static function save(int $companyId, string $indicatorId, string $framework,
                                string $category, $value, array $meta = []): array {
        if (empty($indicatorId)) {
            return ['success' => false, 'message' => 'Indicator ID required.'];
        }

        $entry = [
            'company_id'   => $companyId,
            'indicator_id' => $indicatorId,
            'framework'    => $framework,
            'category'     => $category,
            'value'        => (string)$value,
            'unit'         => $meta['unit'] ?? null,
            'notes'        => isset($meta['notes']) ? htmlspecialchars($meta['notes'], ENT_QUOTES, 'UTF-8') : null,
            'data_source'  => isset($meta['source']) ? htmlspecialchars($meta['source'], ENT_QUOTES, 'UTF-8') : null,
            'period'       => $meta['period'] ?? date('Y'),
            'verified'     => isset($meta['verified']) ? (int)$meta['verified'] : 0,
            'entered_by'   => $meta['user_id'] ?? null,
        ];

        Database::upsert('esg_data', $entry, ['company_id', 'indicator_id', 'period']);
        return ['success' => true];
    }

    /**
     * Save multiple indicators at once (bulk save from form submission)
     */
    public static function saveBulk(int $companyId, string $framework, string $category,
                                     array $entries, int $userId, string $period): array {
        $saved  = 0;
        $errors = [];

        foreach ($entries as $indicatorId => $value) {
            if (str_starts_with($indicatorId, '__')) continue; // Skip meta fields

            $result = self::save($companyId, $indicatorId, $framework, $category, $value, [
                'period'  => $period,
                'user_id' => $userId,
                'source'  => $entries['__source_' . $indicatorId] ?? null,
                'notes'   => $entries['__notes_' . $indicatorId] ?? null,
                'verified'=> isset($entries['__verified_' . $indicatorId]) ? 1 : 0,
            ]);

            if ($result['success']) {
                $saved++;
            } else {
                $errors[] = $indicatorId . ': ' . $result['message'];
            }
        }

        Database::insert('activity_log', [
            'user_id'     => $userId,
            'company_id'  => $companyId,
            'action'      => 'ESG_DATA_SAVED',
            'description' => "Saved {$saved} indicators for {$category} ({$framework})",
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        return ['success' => empty($errors), 'saved' => $saved, 'errors' => $errors];
    }

    /**
     * Get all ESG data for a company + period
     */
    public static function getAll(int $companyId, string $period = '2024'): array {
        $rows = Database::fetchAll(
            'SELECT * FROM esg_data WHERE company_id = ? AND period = ?',
            [$companyId, $period]
        );
        // Key by indicator_id for easy lookup
        $keyed = [];
        foreach ($rows as $row) {
            $keyed[$row['indicator_id']] = $row;
        }
        return $keyed;
    }

    /**
     * Get ESG data for a specific category
     */
    public static function getByCategory(int $companyId, string $category, string $period = '2024'): array {
        $rows = Database::fetchAll(
            'SELECT * FROM esg_data WHERE company_id = ? AND category = ? AND period = ?',
            [$companyId, $category, $period]
        );
        $keyed = [];
        foreach ($rows as $row) {
            $keyed[$row['indicator_id']] = $row;
        }
        return $keyed;
    }

    /**
     * Get completion stats per category
     */
    public static function getCompletionStats(int $companyId, string $framework, string $period = '2024'): array {
        $indicators = Company::getFrameworkIndicators($framework);
        $savedData  = self::getAll($companyId, $period);

        $stats = [
            'ENVIRONMENT' => ['total' => 0, 'required_total' => 0, 'completed' => 0, 'required_done' => 0],
            'SOCIAL'      => ['total' => 0, 'required_total' => 0, 'completed' => 0, 'required_done' => 0],
            'GOVERNANCE'  => ['total' => 0, 'required_total' => 0, 'completed' => 0, 'required_done' => 0],
        ];

        foreach ($indicators as $ind) {
            $cat  = $ind['category'];
            $id   = $ind['indicator_id'];
            $done = isset($savedData[$id]) && $savedData[$id]['value'] !== '' && $savedData[$id]['value'] !== null;

            $stats[$cat]['total']++;
            if ($ind['required']) $stats[$cat]['required_total']++;
            if ($done) $stats[$cat]['completed']++;
            if ($done && $ind['required']) $stats[$cat]['required_done']++;
        }

        // Calculate scores
        foreach ($stats as $cat => &$data) {
            $data['score']          = $data['total'] > 0 ? round(($data['completed'] / $data['total']) * 100, 1) : 0;
            $data['required_score'] = $data['required_total'] > 0
                ? round(($data['required_done'] / $data['required_total']) * 100, 1) : 0;
        }

        return $stats;
    }

    /**
     * Calculate overall weighted ESG score
     */
    public static function calcOverallScore(array $stats): float {
        $e = $stats['ENVIRONMENT']['score'] ?? 0;
        $s = $stats['SOCIAL']['score'] ?? 0;
        $g = $stats['GOVERNANCE']['score'] ?? 0;

        return round(
            ($e * WEIGHT_ENVIRONMENT + $s * WEIGHT_SOCIAL + $g * WEIGHT_GOVERNANCE) / 100,
            1
        );
    }

    /**
     * Get score label and color class
     */
    public static function scoreLabel(float $score): array {
        if ($score >= SCORE_EXCELLENT) return ['label' => 'Excellent', 'class' => 'success', 'color' => '#16a34a'];
        if ($score >= SCORE_GOOD)      return ['label' => 'Good',      'class' => 'info',    'color' => '#0891b2'];
        if ($score >= SCORE_MODERATE)  return ['label' => 'Moderate',  'class' => 'warning', 'color' => '#d97706'];
        return                                ['label' => 'Needs Work', 'class' => 'danger',  'color' => '#dc2626'];
    }
}
