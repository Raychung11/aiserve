<div class="max-w-7xl mx-auto px-4 py-10">

    <!-- Header + Search -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Find a Doctor</h1>
        <p class="text-gray-500 text-sm">Browse our network of trusted healthcare professionals</p>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= url('doctors') ?>" class="bg-white rounded-xl border border-gray-200 p-4 mb-8 flex flex-wrap gap-3">
        <input type="hidden" name="url" value="doctors">
        <div class="flex-1 min-w-48">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by name or speciality..."
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>
        <select name="speciality" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Specialities</option>
            <?php foreach ($specialities as $s): ?>
            <option value="<?= e($s['speciality']) ?>" <?= $speciality === $s['speciality'] ? 'selected' : '' ?>>
                <?= e($s['speciality']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="px-4 py-2.5 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">All Types</option>
            <option value="online" <?= $type === 'online' ? 'selected' : '' ?>>Online</option>
            <option value="onsite" <?= $type === 'onsite' ? 'selected' : '' ?>>Onsite</option>
        </select>
        <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors font-medium">
            <i class="fa-solid fa-magnifying-glass mr-1"></i> Search
        </button>
        <?php if ($search || $speciality || $type): ?>
        <a href="<?= url('doctors') ?>" class="px-4 py-2.5 border border-gray-300 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Results count -->
    <p class="text-sm text-gray-500 mb-4"><?= $paging['total'] ?> doctor<?= $paging['total'] !== 1 ? 's' : '' ?> found</p>

    <?php if (empty($doctors)): ?>
    <div class="text-center py-16 text-gray-400">
        <i class="fa-solid fa-user-doctor text-5xl mb-4 block"></i>
        <p class="font-medium">No doctors found</p>
        <p class="text-sm mt-1">Try adjusting your search filters</p>
    </div>
    <?php else: ?>

    <!-- Doctor grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <?php foreach ($doctors as $doc): ?>
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow overflow-hidden">
            <div class="p-5">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-lg font-bold flex-shrink-0">
                        <?= strtoupper(substr($doc['name'], 0, 1)) ?>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-gray-900 text-sm truncate"><?= e($doc['name']) ?></h3>
                        <p class="text-xs text-blue-600"><?= e($doc['speciality'] ?? 'General Practitioner') ?></p>
                        <?php if ($doc['org_name']): ?>
                        <p class="text-xs text-gray-400 truncate"><?= e($doc['org_name']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($doc['experience_years']): ?>
                <p class="text-xs text-gray-500"><i class="fa-solid fa-briefcase-medical text-gray-400 mr-1"></i><?= $doc['experience_years'] ?> years exp.</p>
                <?php endif; ?>

                <?php if (($doc['avg_rating'] ?? 0) > 0): ?>
                <div class="flex items-center gap-1 mt-1.5">
                    <?= stars((float)$doc['avg_rating'], (int)$doc['rating_count']) ?>
                </div>
                <?php endif; ?>

                <div class="flex gap-1.5 mt-3">
                    <?php if ($doc['is_available_online']): ?>
                    <span class="text-xs bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full">Online</span>
                    <?php endif; ?>
                    <?php if ($doc['is_available_onsite']): ?>
                    <span class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full">Onsite</span>
                    <?php endif; ?>
                </div>

                <?php if ($doc['consultation_fee'] > 0): ?>
                <p class="text-xs text-gray-500 mt-3">From <span class="font-semibold text-gray-700">RM <?= number_format($doc['consultation_fee'], 2) ?></span></p>
                <?php endif; ?>
            </div>
            <div class="border-t border-gray-100 flex">
                <a href="<?= url('doctors/show?id=' . $doc['id']) ?>"
                   class="flex-1 py-2.5 text-center text-xs text-gray-600 hover:bg-gray-50 transition-colors">
                    View Profile
                </a>
                <a href="<?= url('booking?doctor_id=' . $doc['id']) ?>"
                   class="flex-1 py-2.5 text-center text-xs text-white bg-blue-600 hover:bg-blue-700 transition-colors font-medium">
                    Book Now
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($paging['pages'] > 1): ?>
    <div class="flex justify-center gap-2 mt-10">
        <?php if ($paging['has_prev']): ?>
        <a href="?url=doctors&page=<?= $paging['current'] - 1 ?>&search=<?= urlencode($search) ?>&speciality=<?= urlencode($speciality) ?>&type=<?= urlencode($type) ?>"
           class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">&larr; Prev</a>
        <?php endif; ?>
        <span class="px-4 py-2 text-sm text-gray-500">Page <?= $paging['current'] ?> of <?= $paging['pages'] ?></span>
        <?php if ($paging['has_next']): ?>
        <a href="?url=doctors&page=<?= $paging['current'] + 1 ?>&search=<?= urlencode($search) ?>&speciality=<?= urlencode($speciality) ?>&type=<?= urlencode($type) ?>"
           class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Next &rarr;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>

</div>
