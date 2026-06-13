<?php
class DepartmentManager {

    /** Default department templates auto-created on company setup. */
    public static function defaultTemplates(): array {
        return [
            ['type' => 'hr_admin',    'name' => 'HR & Admin',    'color' => '#7c3aed', 'sort_order' => 1,
             'description' => 'Training hours, turnover rate, gender diversity, parental leave, CSR spend'],
            ['type' => 'production',  'name' => 'Production',    'color' => '#0ea5e9', 'sort_order' => 2,
             'description' => 'Electricity usage, waste generated, water consumption, natural gas, GHG emissions'],
            ['type' => 'hse',         'name' => 'HSE',           'color' => '#ef4444', 'sort_order' => 3,
             'description' => 'Safety incidents, LTIFR, fatalities, HSE training, near-miss reports'],
            ['type' => 'energy',      'name' => 'Energy & Emissions', 'color' => '#f59e0b', 'sort_order' => 4,
             'description' => 'Scope 1, 2 & 3 GHG emissions, energy intensity, renewable energy usage'],
            ['type' => 'procurement', 'name' => 'Procurement',   'color' => '#10b981', 'sort_order' => 5,
             'description' => 'Sustainable sourcing %, supply chain ESG assessment, supplier screening'],
            ['type' => 'logistics',   'name' => 'Logistics',     'color' => '#6366f1', 'sort_order' => 6,
             'description' => 'Fleet fuel consumption, freight emissions, last-mile carbon footprint'],
        ];
    }

    public static function setupDefaults(int $companyId): void {
        foreach (self::defaultTemplates() as $d) {
            Database::insert('departments', array_merge($d, ['company_id' => $companyId, 'is_active' => 1]));
        }
    }

    public static function getForCompany(int $companyId, bool $activeOnly = true): array {
        $sql = 'SELECT d.*, COUNT(du.id) AS member_count
                FROM departments d
                LEFT JOIN department_users du ON du.department_id = d.id
                WHERE d.company_id = ?'
             . ($activeOnly ? ' AND d.is_active = 1' : '')
             . ' GROUP BY d.id ORDER BY d.sort_order, d.name';
        return Database::fetchAll($sql, [$companyId]);
    }

    public static function getById(int $id): ?array {
        return Database::fetchOne('SELECT * FROM departments WHERE id = ?', [$id]);
    }

    public static function create(int $companyId, string $name, string $type, string $color, string $description): int {
        return Database::insert('departments', [
            'company_id'  => $companyId,
            'name'        => $name,
            'type'        => $type,
            'color'       => $color,
            'description' => $description,
            'is_active'   => 1,
        ]);
    }

    public static function update(int $id, array $fields): void {
        $allowed = ['name', 'type', 'color', 'description', 'is_active'];
        $data = array_intersect_key($fields, array_flip($allowed));
        if ($data) {
            Database::update('departments', $data, 'id = ?', [$id]);
        }
    }

    public static function assignUser(int $departmentId, int $userId, string $role = 'member'): void {
        Database::upsert('department_users', [
            'department_id' => $departmentId,
            'user_id'       => $userId,
            'role'          => $role,
        ], ['department_id', 'user_id']);
    }

    public static function removeUser(int $departmentId, int $userId): void {
        Database::query('DELETE FROM department_users WHERE department_id = ? AND user_id = ?', [$departmentId, $userId]);
    }

    public static function getMembers(int $departmentId): array {
        return Database::fetchAll(
            'SELECT u.id, u.name, u.email, u.role, du.role AS dept_role
             FROM department_users du JOIN users u ON u.id = du.user_id
             WHERE du.department_id = ? ORDER BY du.role DESC, u.name',
            [$departmentId]
        );
    }

    public static function hasAnyDepartments(int $companyId): bool {
        $row = Database::fetchOne('SELECT COUNT(*) AS c FROM departments WHERE company_id = ? AND is_active = 1', [$companyId]);
        return ($row['c'] ?? 0) > 0;
    }

    public static function typeLabel(string $type): string {
        return match($type) {
            'hr_admin'    => 'HR & Admin',
            'production'  => 'Production',
            'hse'         => 'HSE',
            'energy'      => 'Energy & Emissions',
            'procurement' => 'Procurement',
            'logistics'   => 'Logistics',
            'finance'     => 'Finance',
            'it'          => 'IT',
            default       => 'Custom',
        };
    }
}
