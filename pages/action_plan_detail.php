<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!$activeCompany) {
    header('Location: ' . APP_URL . '/onboarding'); exit;
}

$planId    = (int)($_GET['id'] ?? 0);
$companyId = $activeCompanyId;

if (!$planId) {
    header('Location: ' . url('action-plans')); exit;
}

$plan = ActionPlanManager::getById($planId);
if (!$plan || (int)$plan['company_id'] !== $companyId) {
    header('Location: ' . url('action-plans')); exit;
}

$pageTitle  = 'Action Plan Detail';
$role       = $currentUser['role'];
$success    = $error = '';

// Who can edit: admin, consultants, hierarchy roles, creator, or assignee
$canEdit   = in_array($role, ['admin','principal','associate','manager','consultant'])
          || (int)$plan['created_by'] === $currentUser['id'];
$canDelete = in_array($role, ['admin','principal','associate','manager','consultant'])
          || (int)$plan['created_by'] === $currentUser['id'];

// ── Handle POST ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf($_POST['csrf_token'] ?? '')) {
        $error = 'Session expired. Please refresh.';
    } else {
        $postAction = $_POST['post_action'] ?? '';

        if ($postAction === 'edit' && $canEdit) {
            $newStatus = $_POST['status'] ?? $plan['status'];
            $dueRaw    = trim($_POST['due_date'] ?? '');
            $assignTo  = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null;

            ActionPlanManager::update($planId, [
                'title'          => trim($_POST['title'] ?? $plan['title']),
                'description'    => trim($_POST['description'] ?? '') ?: null,
                'recommendation' => trim($_POST['recommendation'] ?? '') ?: null,
                'priority'       => $_POST['priority'] ?? $plan['priority'],
                'status'         => $newStatus,
                'due_date'       => $dueRaw ?: null,
                'assigned_to'    => $assignTo,
                'department_id'  => $_POST['department_id'] ? (int)$_POST['department_id'] : null,
            ]);

            // Notify new assignee if changed
            if ($assignTo && $assignTo !== (int)($plan['assigned_to'] ?? 0)) {
                NotificationManager::create(
                    $assignTo, 'action_plan',
                    'Action Plan Assigned',
                    $currentUser['name'] . ' assigned you: ' . trim($_POST['title'] ?? $plan['title']),
                    APP_URL . '/action-plan-detail?id=' . $planId,
                    $companyId
                );
            }

            $success = 'Action plan updated.';
            $plan    = ActionPlanManager::getById($planId);

        } elseif ($postAction === 'comment') {
            $commentText = trim($_POST['comment'] ?? '');
            if (strlen($commentText) > 0) {
                ActionPlanManager::addComment($planId, $companyId, $currentUser['id'], $commentText);
                // Notify assignee and creator (if not self)
                $notifyIds = array_filter(array_unique([
                    (int)($plan['assigned_to'] ?? 0),
                    (int)$plan['created_by'],
                ]), fn($id) => $id > 0 && $id !== $currentUser['id']);
                foreach ($notifyIds as $nid) {
                    NotificationManager::create(
                        $nid, 'action_plan',
                        'New Comment on Action Plan',
                        $currentUser['name'] . ' commented: ' . mb_substr($commentText, 0, 80),
                        APP_URL . '/action-plan-detail?id=' . $planId,
                        $companyId
                    );
                }
                $success = 'Comment posted.';
                $plan    = ActionPlanManager::getById($planId);
            }

        } elseif ($postAction === 'delete_comment') {
            $cmtId = (int)($_POST['comment_id'] ?? 0);
            if ($cmtId) {
                // Only commenter or privileged roles can delete
                Database::query(
                    in_array($role, ['admin','principal','associate','manager','consultant'])
                        ? 'DELETE FROM action_plan_comments WHERE id = ? AND action_plan_id = ?'
                        : 'DELETE FROM action_plan_comments WHERE id = ? AND action_plan_id = ? AND user_id = ?',
                    in_array($role, ['admin','principal','associate','manager','consultant'])
                        ? [$cmtId, $planId]
                        : [$cmtId, $planId, $currentUser['id']]
                );
            }
            header('Location: ' . url('action-plan-detail') . '?id=' . $planId); exit;

        } elseif ($postAction === 'delete_plan' && $canDelete) {
            Database::query('DELETE FROM action_plans WHERE id = ? AND company_id = ?', [$planId, $companyId]);
            header('Location: ' . url('action-plans') . '?deleted=1'); exit;
        }
    }
}

$comments    = ActionPlanManager::getComments($planId);
$departments = DepartmentManager::getForCompany($companyId);
$companyUsers = Database::fetchAll(
    'SELECT u.id, u.name, u.role FROM user_companies uc JOIN users u ON u.id = uc.user_id WHERE uc.company_id = ? ORDER BY u.name',
    [$companyId]
);
$isOverdue = $plan['status'] !== 'completed'
          && $plan['status'] !== 'deferred'
          && !empty($plan['due_date'])
          && $plan['due_date'] < date('Y-m-d');

include __DIR__ . '/../includes/header.php';
?>
<style>
.detail-wrap   { max-width: 960px; }
.detail-grid   { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
@media (max-width: 768px) { .detail-grid { grid-template-columns: 1fr; } }

.detail-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.detail-card + .detail-card { margin-top: 16px; }

.detail-section-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .08em;
  color: #94a3b8;
  margin-bottom: 14px;
  padding-bottom: 8px;
  border-bottom: 1px solid #f1f5f9;
}

.meta-row { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px; font-size: 13px; }
.meta-row i { color: #94a3b8; margin-top: 2px; flex-shrink: 0; }
.meta-label { color: #64748b; min-width: 90px; flex-shrink: 0; }
.meta-val   { color: #0f172a; font-weight: 500; }

/* Comment thread */
.comment-thread { display: flex; flex-direction: column; gap: 12px; }
.comment-bubble {
  background: #f8fafc;
  border: 1px solid #e9ecef;
  border-radius: 10px;
  padding: 12px 14px;
  position: relative;
}
.comment-bubble.mine { background: #eff6ff; border-color: #bfdbfe; }
.comment-meta  { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.comment-author { font-size: 12px; font-weight: 700; color: #1e40af; }
.comment-time   { font-size: 11px; color: #94a3b8; }
.comment-text   { font-size: 13px; color: #374151; line-height: 1.6; white-space: pre-wrap; }
.comment-del    { position: absolute; top: 8px; right: 8px; opacity: 0; transition: opacity .15s; }
.comment-bubble:hover .comment-del { opacity: 1; }
.comment-add-form { margin-top: 14px; }

/* Priority dot */
.priority-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; vertical-align: middle; }

/* Status selector quick-update */
.status-quick { display: flex; gap: 6px; flex-wrap: wrap; }
.status-pill {
  padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
  cursor: pointer; border: 2px solid transparent; transition: all .15s;
}
.status-pill.active-open        { background:#dbeafe;border-color:#3b82f6;color:#1e40af; }
.status-pill.active-in_progress { background:#fef3c7;border-color:#f59e0b;color:#92400e; }
.status-pill.active-completed   { background:#dcfce7;border-color:#22c55e;color:#166534; }
.status-pill.active-deferred    { background:#f3f4f6;border-color:#9ca3af;color:#374151; }
.status-pill:not(.active-open):not(.active-in_progress):not(.active-completed):not(.active-deferred) {
  background:#f8fafc;color:#64748b;border-color:#e2e8f0;
}
.status-pill:hover { filter: brightness(.95); }
</style>

<div class="app-layout">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <button class="sidebar-toggle" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
      <div class="topbar-title">
        <h1><i class="bi bi-clipboard2-check me-2 text-warning"></i>Action Plan</h1>
        <span class="topbar-subtitle"><?= htmlspecialchars(mb_substr($plan['title'], 0, 50)) ?><?= mb_strlen($plan['title']) > 50 ? '…' : '' ?></span>
      </div>
      <div class="topbar-actions">
        <a href="<?= url('action-plans') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
      </div>
    </div>

    <div class="content-body">
      <div class="detail-wrap">

      <?php if ($success): ?>
      <div class="alert alert-success alert-dismissible fade show mb-3">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>

      <!-- ── Status quick-update bar ────────────────────────────────── -->
      <div class="detail-card mb-4 py-3 px-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <?= ActionPlanManager::priorityBadge($plan['priority']) ?>
            <?= ActionPlanManager::statusBadge($plan['status']) ?>
            <?php if ($isOverdue): ?>
            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Overdue</span>
            <?php endif; ?>
            <?php if ($plan['due_date']): ?>
            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i>Due <?= date('d M Y', strtotime($plan['due_date'])) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($canEdit): ?>
          <form method="POST" class="d-flex align-items-center gap-2">
            <input type="hidden" name="csrf_token" value="<?= Auth::csrfToken() ?>">
            <input type="hidden" name="post_action" value="quick_status">
            <div class="status-quick">
              <?php foreach (['open','in_progress','completed','deferred'] as $s): ?>
              <button type="submit" name="status_shortcut" value="<?= $s ?>"
                class="status-pill <?= $plan['status'] === $s ? 'active-' . $s : '' ?>"
                formaction="<?= url('action-plan-detail') ?>?id=<?= $planId ?>"
                onclick="this.form.elements['post_action'].value='edit';
                         this.form.elements['title'].value=<?= json_encode($plan['title']) ?>;
                         this.form.elements['status_val'].value=<?= json_encode($s) ?>;">
                <?= ucwords(str_replace('_',' ',$s)) ?>
              </button>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="title"      value="<?= htmlspecialchars($plan['title']) ?>">
            <input type="hidden" name="priority"   value="<?= htmlspecialchars($plan['priority']) ?>">
            <input type="hidden" name="status"     id="status_val" value="<?= htmlspecialchars($plan['status']) ?>">
            <input type="hidden" name="due_date"   value="<?= htmlspecialchars($plan['due_date'] ?? '') ?>">
            <input type="hidden" name="assigned_to"   value="<?= htmlspecialchars($plan['assigned_to'] ?? '') ?>">
            <input type="hidden" name="department_id" value="<?= htmlspecialchars($plan['department_id'] ?? '') ?>">
          </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="detail-grid">

        <!-- ── Left: Edit form + Comments ─────────────────────────── -->
        <div>
          <?php if ($canEdit): ?>
          <!-- Edit form -->
          <div class="detail-card">
            <div class="detail-section-label"><i class="bi bi-pencil-square me-1"></i>Edit Plan</div>
            <form method="POST" action="<?= url('action-plan-detail') ?>?id=<?= $planId ?>">
              <input type="hidden" name="csrf_token"   value="<?= Auth::csrfToken() ?>">
              <input type="hidden" name="post_action"  value="edit">
              <div class="mb-3">
                <label class="form-label fw-semibold small">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title" required
                       value="<?= htmlspecialchars($plan['title']) ?>">
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold small">Priority</label>
                  <select class="form-select" name="priority">
                    <?php foreach (['critical','high','medium','low'] as $p): ?>
                    <option value="<?= $p ?>" <?= $plan['priority'] === $p ? 'selected' : '' ?>><?= ucfirst($p) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold small">Status</label>
                  <select class="form-select" name="status">
                    <?php foreach (['open','in_progress','completed','deferred'] as $s): ?>
                    <option value="<?= $s ?>" <?= $plan['status'] === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold small">Assign To</label>
                  <select class="form-select" name="assigned_to">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($companyUsers as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (int)($plan['assigned_to'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($u['name']) ?> (<?= $u['role'] ?>)
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold small">Department</label>
                  <select class="form-select" name="department_id">
                    <option value="">— None —</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= (int)($plan['department_id'] ?? 0) === (int)$d['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($d['name']) ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold small">Due Date</label>
                  <input type="date" class="form-control" name="due_date"
                         value="<?= htmlspecialchars($plan['due_date'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label fw-semibold small">Related Indicator</label>
                  <input type="text" class="form-control" name="indicator_id"
                         placeholder="e.g. SEDG-E01 (optional)"
                         value="<?= htmlspecialchars($plan['indicator_id'] ?? '') ?>">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Description</label>
                <textarea class="form-control" name="description" rows="3"
                          placeholder="What needs to be done?"><?= htmlspecialchars($plan['description'] ?? '') ?></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Recommendation</label>
                <textarea class="form-control" name="recommendation" rows="3"
                          placeholder="How should this be approached?"><?= htmlspecialchars($plan['recommendation'] ?? '') ?></textarea>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-floppy me-1"></i>Save Changes
                </button>
              </div>
            </form>
          </div>
          <?php else: ?>
          <!-- Read-only view for non-editors -->
          <div class="detail-card">
            <div class="detail-section-label"><i class="bi bi-info-circle me-1"></i>Plan Details</div>
            <h5 class="fw-bold mb-2"><?= htmlspecialchars($plan['title']) ?></h5>
            <?php if ($plan['description']): ?>
            <p class="text-muted"><?= nl2br(htmlspecialchars($plan['description'])) ?></p>
            <?php endif; ?>
            <?php if ($plan['recommendation']): ?>
            <div class="alert alert-light py-2">
              <i class="bi bi-lightbulb text-warning me-1"></i>
              <strong>Recommendation:</strong> <?= nl2br(htmlspecialchars($plan['recommendation'])) ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Comment thread -->
          <div class="detail-card mt-4">
            <div class="detail-section-label">
              <i class="bi bi-chat-dots me-1"></i>Comments
              <?php if (count($comments) > 0): ?>
              <span class="badge bg-secondary ms-1"><?= count($comments) ?></span>
              <?php endif; ?>
            </div>

            <?php if (empty($comments)): ?>
            <p class="text-muted small mb-3">No comments yet. Be the first to add one.</p>
            <?php else: ?>
            <div class="comment-thread mb-3">
              <?php foreach ($comments as $cm):
                $isMine = (int)$cm['user_id'] === $currentUser['id'];
                $canDelCmt = $isMine || in_array($role, ['admin','principal','associate','manager','consultant']);
              ?>
              <div class="comment-bubble <?= $isMine ? 'mine' : '' ?>">
                <div class="comment-meta">
                  <div>
                    <span class="comment-author"><?= htmlspecialchars($cm['author_name']) ?></span>
                    <span class="badge bg-light text-secondary ms-1" style="font-size:10px"><?= ucfirst($cm['author_role']) ?></span>
                  </div>
                  <span class="comment-time"><?= date('d M Y, H:i', strtotime($cm['created_at'])) ?></span>
                </div>
                <div class="comment-text"><?= htmlspecialchars($cm['comment']) ?></div>
                <?php if ($canDelCmt): ?>
                <form method="POST" class="comment-del">
                  <input type="hidden" name="csrf_token"    value="<?= Auth::csrfToken() ?>">
                  <input type="hidden" name="post_action"   value="delete_comment">
                  <input type="hidden" name="comment_id"    value="<?= $cm['id'] ?>">
                  <button type="submit" class="btn btn-xs btn-link text-danger p-0"
                          onclick="return confirm('Delete this comment?')">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="comment-add-form">
              <form method="POST" action="<?= url('action-plan-detail') ?>?id=<?= $planId ?>">
                <input type="hidden" name="csrf_token"  value="<?= Auth::csrfToken() ?>">
                <input type="hidden" name="post_action" value="comment">
                <div class="input-group">
                  <textarea class="form-control" name="comment" rows="2"
                            placeholder="Write a comment…" required style="resize:none"></textarea>
                  <button type="submit" class="btn btn-primary align-self-end">
                    <i class="bi bi-send me-1"></i>Post
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- ── Right: Metadata panel ─────────────────────────────── -->
        <div>
          <div class="detail-card">
            <div class="detail-section-label"><i class="bi bi-info-circle me-1"></i>Details</div>

            <div class="meta-row">
              <i class="bi bi-person"></i>
              <span class="meta-label">Created by</span>
              <span class="meta-val"><?= htmlspecialchars($plan['created_by_name'] ?? '—') ?></span>
            </div>
            <div class="meta-row">
              <i class="bi bi-calendar3"></i>
              <span class="meta-label">Created</span>
              <span class="meta-val"><?= date('d M Y', strtotime($plan['created_at'])) ?></span>
            </div>
            <?php if ($plan['assigned_to_name']): ?>
            <div class="meta-row">
              <i class="bi bi-person-check"></i>
              <span class="meta-label">Assigned to</span>
              <span class="meta-val"><?= htmlspecialchars($plan['assigned_to_name']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($plan['department_name']): ?>
            <div class="meta-row">
              <i class="bi bi-diagram-3"></i>
              <span class="meta-label">Department</span>
              <span class="meta-val"><?= htmlspecialchars($plan['department_name']) ?></span>
            </div>
            <?php endif; ?>
            <?php if ($plan['indicator_id']): ?>
            <div class="meta-row">
              <i class="bi bi-hash"></i>
              <span class="meta-label">Indicator</span>
              <span class="meta-val">
                <a href="<?= url('data-entry') ?>?focus=<?= htmlspecialchars($plan['indicator_id']) ?>">
                  <?= htmlspecialchars($plan['indicator_id']) ?>
                </a>
              </span>
            </div>
            <?php endif; ?>
            <?php if ($plan['completed_at']): ?>
            <div class="meta-row">
              <i class="bi bi-check-circle text-success"></i>
              <span class="meta-label">Completed</span>
              <span class="meta-val text-success"><?= date('d M Y', strtotime($plan['completed_at'])) ?></span>
            </div>
            <?php endif; ?>
          </div>

          <?php if ($plan['description'] && $canEdit): ?>
          <div class="detail-card mt-0">
            <div class="detail-section-label"><i class="bi bi-file-text me-1"></i>Description</div>
            <p class="text-muted small mb-0" style="white-space:pre-wrap"><?= htmlspecialchars($plan['description']) ?></p>
          </div>
          <?php endif; ?>

          <?php if ($plan['recommendation']): ?>
          <div class="detail-card">
            <div class="detail-section-label"><i class="bi bi-lightbulb me-1"></i>Recommendation</div>
            <p class="text-muted small mb-0" style="white-space:pre-wrap"><?= htmlspecialchars($plan['recommendation']) ?></p>
          </div>
          <?php endif; ?>

          <?php if ($canDelete): ?>
          <div class="detail-card" style="border-color:#fee2e2;background:#fff5f5">
            <div class="detail-section-label text-danger"><i class="bi bi-exclamation-octagon me-1"></i>Danger Zone</div>
            <form method="POST" action="<?= url('action-plan-detail') ?>?id=<?= $planId ?>">
              <input type="hidden" name="csrf_token"  value="<?= Auth::csrfToken() ?>">
              <input type="hidden" name="post_action" value="delete_plan">
              <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                      onclick="return confirm('Permanently delete this action plan and all its comments?')">
                <i class="bi bi-trash me-1"></i>Delete Action Plan
              </button>
            </form>
          </div>
          <?php endif; ?>
        </div>

      </div><!-- /.detail-grid -->
      </div><!-- /.detail-wrap -->
    </div><!-- /.content-body -->
  </div><!-- /.main-content -->
</div><!-- /.app-layout -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
