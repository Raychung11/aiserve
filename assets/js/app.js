/**
 * AiServe ESG OS — Main JavaScript
 */

'use strict';

// ============================================================
// Sidebar Toggle (mobile)
// ============================================================
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');
  if (!sidebar) return;
  sidebar.classList.toggle('open');
  if (overlay) overlay.classList.toggle('show');
  document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
}

// Close sidebar on overlay click
document.addEventListener('DOMContentLoaded', function() {
  const overlay = document.getElementById('sidebarOverlay');
  if (overlay) {
    overlay.addEventListener('click', function() {
      const sidebar = document.getElementById('sidebar');
      if (sidebar) sidebar.classList.remove('open');
      overlay.classList.remove('show');
      document.body.style.overflow = '';
    });
  }

  // Close sidebar on ESC
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const sidebar = document.getElementById('sidebar');
      if (sidebar && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('show');
        document.body.style.overflow = '';
      }
    }
  });

  // Initialize Bootstrap tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
    new bootstrap.Tooltip(el);
  });

  // Progress bar animation
  document.querySelectorAll('.progress-bar').forEach(function(bar) {
    const target = bar.style.width;
    bar.style.width = '0';
    setTimeout(function() {
      bar.style.transition = 'width 0.8s ease';
      bar.style.width = target;
    }, 100);
  });

  // Auto-dismiss success alerts after 5s
  document.querySelectorAll('.alert-success[role="alert"]').forEach(function(alert) {
    if (alert.querySelector('.btn-close')) {
      setTimeout(function() {
        const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        if (bsAlert) bsAlert.close();
      }, 5000);
    }
  });

  // Data entry: track unsaved changes
  const dataEntryForm = document.getElementById('dataEntryForm');
  if (dataEntryForm) {
    let hasUnsaved = false;
    dataEntryForm.addEventListener('change', function() {
      hasUnsaved = true;
    });
    window.addEventListener('beforeunload', function(e) {
      if (hasUnsaved) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Leave page?';
      }
    });
    dataEntryForm.addEventListener('submit', function() {
      hasUnsaved = false;
    });
  }

  // Framework option selectors
  setupSelectorHighlight('framework');
  setupSelectorHighlight('revenue_tier');
  setupSelectorHighlight('role');
});

/**
 * Highlight selected radio option in custom selectors
 */
function setupSelectorHighlight(name) {
  const radios = document.querySelectorAll('[name="' + name + '"]');
  radios.forEach(function(radio) {
    radio.addEventListener('change', function() {
      radios.forEach(function(r) {
        const parent = r.closest('label');
        if (parent) parent.classList.remove('selected');
      });
      const parent = this.closest('label');
      if (parent) parent.classList.add('selected');
    });
  });
}

/**
 * Toggle recommended indicators section
 */
function toggleRecommended() {
  const sec  = document.getElementById('recommendedSection');
  const icon = document.getElementById('recToggleIcon');
  if (!sec) return;
  const hidden = sec.style.display === 'none';
  sec.style.display = hidden ? '' : 'none';
  if (icon) icon.className = hidden ? 'bi bi-chevron-down' : 'bi bi-chevron-right';
}

/**
 * Copy text to clipboard
 */
function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(function() {
    showToast('Copied to clipboard!', 'success');
  }).catch(function() {
    showToast('Copy failed.', 'danger');
  });
}

/**
 * Show a toast notification
 */
function showToast(message, type = 'success') {
  const toastEl = document.createElement('div');
  toastEl.className = 'toast align-items-center text-bg-' + type + ' border-0 position-fixed bottom-0 end-0 m-3';
  toastEl.setAttribute('role', 'alert');
  toastEl.setAttribute('aria-live', 'assertive');
  toastEl.innerHTML = '<div class="d-flex"><div class="toast-body">' + message + '</div>' +
    '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
  document.body.appendChild(toastEl);
  const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
  toast.show();
  toastEl.addEventListener('hidden.bs.toast', function() { toastEl.remove(); });
}

/**
 * Confirm delete action
 */
function confirmDelete(message) {
  return confirm(message || 'Are you sure you want to delete this?');
}

/**
 * Filter gap list by search term
 */
function filterGaps(query) {
  const items = document.querySelectorAll('.gap-item');
  const q = query.toLowerCase();
  items.forEach(function(item) {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(q) ? '' : 'none';
  });
}
