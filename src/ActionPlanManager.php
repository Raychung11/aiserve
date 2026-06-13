<?php
class ActionPlanManager {

    public static function create(array $data): int {
        return Database::insert('action_plans', [
            'company_id'     => $data['company_id'],
            'indicator_id'   => $data['indicator_id']   ?? null,
            'department_id'  => $data['department_id']  ?? null,
            'created_by'     => $data['created_by'],
            'assigned_to'    => $data['assigned_to']    ?? null,
            'title'          => $data['title'],
            'description'    => $data['description']    ?? null,
            'recommendation' => $data['recommendation'] ?? null,
            'priority'       => $data['priority']       ?? 'medium',
            'status'         => 'open',
            'due_date'       => $data['due_date']       ?? null,
        ]);
    }

    public static function update(int $id, array $fields): void {
        $allowed = ['title','description','recommendation','priority','status','due_date','assigned_to','department_id'];
        $data = array_intersect_key($fields, array_flip($allowed));
        if (!empty($fields['status']) && $fields['status'] === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        if ($data) {
            Database::update('action_plans', $data, 'id = ?', [$id]);
        }
    }

    public static function getForCompany(int $companyId, ?string $status = null): array {
        $sql = 'SELECT ap.*,
                       uc.name  AS created_by_name,
                       ua.name  AS assigned_to_name,
                       d.name   AS department_name,  d.color  AS department_color
                FROM action_plans ap
                LEFT JOIN users uc ON uc.id = ap.created_by
                LEFT JOIN users ua ON ua.id = ap.assigned_to
                LEFT JOIN departments d ON d.id = ap.department_id
                WHERE ap.company_id = ?';
        $params = [$companyId];
        if ($status) { $sql .= ' AND ap.status = ?'; $params[] = $status; }
        $sql .= ' ORDER BY FIELD(ap.priority,"critical","high","medium","low"), ap.due_date IS NULL, ap.due_date ASC';
        return Database::fetchAll($sql, $params);
    }

    public static function getAssignedToUser(int $userId): array {
        return Database::fetchAll(
            'SELECT ap.*, c.name AS company_name,
                    uc.name AS created_by_name, d.name AS department_name
             FROM action_plans ap
             JOIN companies c ON c.id = ap.company_id
             LEFT JOIN users uc ON uc.id = ap.created_by
             LEFT JOIN departments d ON d.id = ap.department_id
             WHERE ap.assigned_to = ? AND ap.status NOT IN ("completed","deferred")
             ORDER BY FIELD(ap.priority,"critical","high","medium","low"), ap.due_date IS NULL, ap.due_date ASC',
            [$userId]
        );
    }

    public static function getStats(int $companyId): array {
        $rows = Database::fetchAll(
            'SELECT status, COUNT(*) AS cnt FROM action_plans WHERE company_id = ? GROUP BY status',
            [$companyId]
        );
        $stats = ['open' => 0, 'in_progress' => 0, 'completed' => 0, 'deferred' => 0, 'total' => 0, 'overdue' => 0];
        foreach ($rows as $r) {
            $stats[$r['status']] = (int)$r['cnt'];
            $stats['total'] += (int)$r['cnt'];
        }
        $overdue = Database::fetchOne(
            'SELECT COUNT(*) AS cnt FROM action_plans
             WHERE company_id = ? AND status NOT IN ("completed","deferred") AND due_date IS NOT NULL AND due_date < CURDATE()',
            [$companyId]
        );
        $stats['overdue'] = (int)($overdue['cnt'] ?? 0);
        return $stats;
    }

    public static function getById(int $id): ?array {
        return Database::fetchOne(
            'SELECT ap.*, uc.name AS created_by_name, ua.name AS assigned_to_name, d.name AS department_name
             FROM action_plans ap
             LEFT JOIN users uc ON uc.id = ap.created_by
             LEFT JOIN users ua ON ua.id = ap.assigned_to
             LEFT JOIN departments d ON d.id = ap.department_id
             WHERE ap.id = ?',
            [$id]
        );
    }

    public static function priorityBadge(string $priority): string {
        $map = [
            'critical' => ['#991b1b', '#fee2e2', 'Critical'],
            'high'     => ['#92400e', '#fef3c7', 'High'],
            'medium'   => ['#1e40af', '#dbeafe', 'Medium'],
            'low'      => ['#166534', '#dcfce7', 'Low'],
        ];
        [$c, $bg, $label] = $map[$priority] ?? ['#475569', '#f1f5f9', ucfirst($priority)];
        return "<span style='background:{$bg};color:{$c};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700'>{$label}</span>";
    }

    public static function addComment(int $planId, int $companyId, int $userId, string $comment): int {
        return Database::insert('action_plan_comments', [
            'action_plan_id' => $planId,
            'company_id'     => $companyId,
            'user_id'        => $userId,
            'comment'        => $comment,
        ]);
    }

    public static function getComments(int $planId): array {
        return Database::fetchAll(
            'SELECT apc.*, u.name AS author_name, u.role AS author_role
             FROM action_plan_comments apc
             JOIN users u ON u.id = apc.user_id
             WHERE apc.action_plan_id = ?
             ORDER BY apc.created_at ASC',
            [$planId]
        );
    }

    public static function statusBadge(string $status): string {
        $map = [
            'open'        => ['#1e40af', '#dbeafe', 'Open'],
            'in_progress' => ['#92400e', '#fef3c7', 'In Progress'],
            'completed'   => ['#166534', '#dcfce7', 'Completed'],
            'deferred'    => ['#374151', '#f3f4f6', 'Deferred'],
        ];
        [$c, $bg, $label] = $map[$status] ?? ['#475569', '#f1f5f9', ucfirst($status)];
        return "<span style='background:{$bg};color:{$c};padding:2px 8px;border-radius:4px;font-size:11px;font-weight:700'>{$label}</span>";
    }
}
