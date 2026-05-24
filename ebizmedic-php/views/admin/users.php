<div class="pt-4">
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <form method="GET" action="<?= url('admin/users') ?>" class="flex gap-2 flex-wrap">
            <input type="hidden" name="url" value="admin/users">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search users..."
                   class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-56">
            <select name="role" class="px-4 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Roles</option>
                <?php foreach (['admin','medic','organisation','user'] as $r): ?>
                <option value="<?= $r ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Filter</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">User</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Role</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Phone</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Joined</th>
                    <th class="text-left px-5 py-3 font-medium text-gray-600">Status</th>
                    <th class="text-right px-5 py-3 font-medium text-gray-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($users)): ?>
                <tr><td colspan="6" class="text-center py-10 text-gray-400">No users found</td></tr>
                <?php else: ?>
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-5 py-3">
                        <p class="font-medium text-gray-900"><?= e($u['name']) ?></p>
                        <p class="text-xs text-gray-400"><?= e($u['email']) ?></p>
                    </td>
                    <td class="px-5 py-3">
                        <?php $roleColors = ['admin'=>'red','medic'=>'blue','organisation'=>'purple','user'=>'gray']; $rc = $roleColors[$u['role']] ?? 'gray'; ?>
                        <span class="text-xs bg-<?= $rc ?>-100 text-<?= $rc ?>-700 px-2 py-0.5 rounded-full capitalize"><?= $u['role'] ?></span>
                    </td>
                    <td class="px-5 py-3 text-gray-600"><?= e($u['phone'] ?? '—') ?></td>
                    <td class="px-5 py-3 text-gray-500 text-xs"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td class="px-5 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full <?= $u['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' ?>">
                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <?php if ($u['role'] !== 'admin'): ?>
                        <form method="POST" action="<?= url('admin/users/toggle') ?>" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button class="text-xs text-<?= $u['is_active'] ? 'red' : 'green' ?>-600 hover:underline">
                                <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
