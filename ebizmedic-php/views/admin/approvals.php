<div class="pt-4">
    <?php if (empty($pending)): ?>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm text-center py-16">
        <i class="fa-solid fa-circle-check text-5xl text-green-400 mb-4 block"></i>
        <p class="font-semibold text-gray-700">All caught up!</p>
        <p class="text-sm text-gray-400 mt-1">No pending accounts to approve.</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($pending as $u): ?>
        <div class="bg-white rounded-2xl border border-yellow-200 shadow-sm p-5 flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-full bg-yellow-100 flex items-center justify-center text-yellow-600 font-bold text-lg flex-shrink-0">
                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                </div>
                <div>
                    <p class="font-semibold text-gray-900"><?= e($u['name']) ?></p>
                    <p class="text-sm text-gray-500"><?= e($u['email']) ?></p>
                    <div class="flex gap-2 mt-1">
                        <?php $roleColors = ['medic'=>'blue','organisation'=>'purple']; $rc = $roleColors[$u['role']] ?? 'gray'; ?>
                        <span class="text-xs bg-<?= $rc ?>-100 text-<?= $rc ?>-700 px-2 py-0.5 rounded-full capitalize"><?= $u['role'] ?></span>
                        <?php if ($u['role'] === 'medic' && $u['speciality']): ?>
                        <span class="text-xs text-gray-500"><?= e($u['speciality']) ?></span>
                        <?php elseif ($u['role'] === 'organisation' && $u['org_name']): ?>
                        <span class="text-xs text-gray-500"><?= e($u['org_name']) ?></span>
                        <?php endif; ?>
                        <span class="text-xs text-gray-400">Registered <?= ago($u['created_at']) ?></span>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <form method="POST" action="<?= url('admin/approvals/action') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button class="px-5 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 font-medium transition-colors">
                        <i class="fa-solid fa-check mr-1"></i> Approve
                    </button>
                </form>
                <form method="POST" action="<?= url('admin/approvals/action') ?>"
                      onsubmit="return confirm('Reject this account?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button class="px-5 py-2 border border-red-300 text-red-600 text-sm rounded-lg hover:bg-red-50 font-medium transition-colors">
                        <i class="fa-solid fa-xmark mr-1"></i> Reject
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
