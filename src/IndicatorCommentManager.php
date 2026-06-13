<?php
class IndicatorCommentManager {

    public static function add(int $companyId, string $indicatorId, int $userId, string $comment): int {
        return Database::insert('indicator_comments', [
            'company_id'   => $companyId,
            'indicator_id' => $indicatorId,
            'user_id'      => $userId,
            'comment'      => $comment,
        ]);
    }

    public static function getForIndicator(int $companyId, string $indicatorId): array {
        return Database::fetchAll(
            'SELECT ic.*, u.name AS author_name, u.role AS author_role
             FROM indicator_comments ic
             JOIN users u ON u.id = ic.user_id
             WHERE ic.company_id = ? AND ic.indicator_id = ?
             ORDER BY ic.created_at ASC',
            [$companyId, $indicatorId]
        );
    }

    public static function countForCompany(int $companyId): array {
        $rows = Database::fetchAll(
            'SELECT indicator_id, COUNT(*) AS cnt FROM indicator_comments WHERE company_id = ? GROUP BY indicator_id',
            [$companyId]
        );
        $map = [];
        foreach ($rows as $r) {
            $map[$r['indicator_id']] = (int)$r['cnt'];
        }
        return $map;
    }

    public static function delete(int $id, int $userId, string $userRole): void {
        $canDelete = in_array($userRole, ['admin', 'principal', 'associate', 'manager', 'consultant']);
        if ($canDelete) {
            Database::query('DELETE FROM indicator_comments WHERE id = ?', [$id]);
        } else {
            Database::query('DELETE FROM indicator_comments WHERE id = ? AND user_id = ?', [$id, $userId]);
        }
    }
}
