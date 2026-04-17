/**
 * Kasih Gold Easy — Main JavaScript
 * Pure vanilla JS, no modules, browser-compatible
 */

'use strict';

// ============================================================
// GOLD CALCULATOR
// ============================================================
var GoldCalc = {
  pricePerG: 0,
  setPrice: function(p) { this.pricePerG = parseFloat(p) || 0; },
  pointsFromRM: function(rm) {
    if (!this.pricePerG || this.pricePerG <= 0) return '0.0000';
    var pts = (parseFloat(rm) / this.pricePerG) * 100;
    return isNaN(pts) ? '0.0000' : pts.toFixed(4);
  },
  rmFromPoints: function(pts) {
    if (!this.pricePerG || this.pricePerG <= 0) return '0.00';
    var rm = (parseFloat(pts) / 100) * this.pricePerG;
    return isNaN(rm) ? '0.00' : rm.toFixed(2);
  },
  gramsFromPoints: function(pts) {
    var g = parseFloat(pts) / 100;
    return isNaN(g) ? '0.000000' : g.toFixed(6);
  },
  formatPoints: function(pts) {
    var n = parseFloat(pts);
    if (isNaN(n)) return '0.00';
    return n.toLocaleString('en-MY', {minimumFractionDigits: 2, maximumFractionDigits: 4});
  },
  formatRM: function(rm) {
    var n = parseFloat(rm);
    if (isNaN(n)) return 'RM 0.00';
    return 'RM ' + n.toLocaleString('en-MY', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }
};

// ============================================================
// CONFIRMATION MODAL
// ============================================================
var ConfirmModal = {
  _el: null,
  _onConfirm: null,
  _inject: function() {
    if (this._el) return;
    var div = document.createElement('div');
    div.id = 'confirmModal';
    div.className = 'modal-overlay';
    div.style.display = 'none';
    div.innerHTML = [
      '<div class="modal-box">',
        '<div class="modal-title" id="confirmModalTitle">Pengesahan</div>',
        '<div id="confirmModalBody" style="color:#4B5563;margin-bottom:20px;font-size:0.9rem;line-height:1.6;"></div>',
        '<div style="display:flex;gap:10px;justify-content:flex-end;">',
          '<button id="confirmModalCancel" class="btn-gold-outline btn-sm">Batal</button>',
          '<button id="confirmModalOk" class="btn-gold btn-sm">Ya, Teruskan</button>',
        '</div>',
      '</div>'
    ].join('');
    document.body.appendChild(div);
    this._el = div;
    var self = this;
    div.querySelector('#confirmModalCancel').addEventListener('click', function() { self.hide(); });
    div.querySelector('#confirmModalOk').addEventListener('click', function() {
      self.hide();
      if (self._onConfirm) self._onConfirm();
    });
    div.addEventListener('click', function(e) { if (e.target === div) self.hide(); });
  },
  show: function(title, body, onConfirm) {
    this._inject();
    document.getElementById('confirmModalTitle').textContent = title || 'Pengesahan';
    document.getElementById('confirmModalBody').innerHTML = body || '';
    this._onConfirm = onConfirm;
    this._el.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  },
  hide: function() {
    if (this._el) this._el.style.display = 'none';
    document.body.style.overflow = '';
    this._onConfirm = null;
  }
};

// ============================================================
// LIVE GOLD CALCULATOR (Buy Gold Page)
// ============================================================
function initLiveCalculator() {
  var rmInput = document.getElementById('rm_amount_input');
  if (!rmInput) return;
  var priceEl = document.getElementById('current_gold_price');
  var price = parseFloat(rmInput.dataset.pricePerG || (priceEl ? priceEl.value : 0));
  GoldCalc.setPrice(price);

  function updateCalc() {
    var rm = rmInput.value.trim();
    var ptsEl   = document.getElementById('estimated_points');
    var gramsEl = document.getElementById('estimated_grams');
    var rmValEl = document.getElementById('estimated_rm_value');
    if (parseFloat(rm) > 0) {
      var pts = GoldCalc.pointsFromRM(rm);
      if (ptsEl)   ptsEl.textContent   = GoldCalc.formatPoints(pts) + ' pts';
      if (gramsEl) gramsEl.textContent = GoldCalc.gramsFromPoints(pts) + ' g';
      if (rmValEl) rmValEl.textContent = GoldCalc.formatRM(rm);
    } else {
      if (ptsEl)   ptsEl.textContent   = '—';
      if (gramsEl) gramsEl.textContent = '—';
      if (rmValEl) rmValEl.textContent = '—';
    }
  }

  rmInput.addEventListener('input', updateCalc);
  rmInput.addEventListener('change', updateCalc);
  updateCalc();
}

// ============================================================
// TRANSFER FORM VALIDATION
// ============================================================
function initTransferForm() {
  var form = document.getElementById('transfer_form');
  if (!form) return;
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var ptsInput = document.getElementById('transfer_points');
    var pts = parseFloat(ptsInput ? ptsInput.value : 0);
    var recipientEl = document.getElementById('recipient_identifier');
    var recipient = recipientEl ? recipientEl.value.trim() : '';
    if (!pts || pts <= 0) { alert('Sila masukkan jumlah mata yang sah.'); return; }
    if (!recipient) { alert('Sila masukkan kod rujukan atau e-mel penerima.'); return; }
    var priceEl = document.getElementById('current_gold_price');
    GoldCalc.setPrice(parseFloat(priceEl ? priceEl.value : 0));
    var rmVal = GoldCalc.formatRM(GoldCalc.rmFromPoints(pts));
    ConfirmModal.show(
      'Sahkan Pindahan',
      '<strong>Penerima:</strong> ' + escapeHtml(recipient) + '<br>' +
      '<strong>Jumlah Mata:</strong> ' + GoldCalc.formatPoints(pts) + ' pts<br>' +
      '<strong>Nilai Anggaran:</strong> ' + rmVal + '<br>' +
      '<small style="color:#9CA3AF">Pindahan tidak boleh dibatalkan setelah disahkan.</small>',
      function() { form.submit(); }
    );
  });
}

// ============================================================
// AUTO-DISMISS FLASH MESSAGES
// ============================================================
function initFlashMessages() {
  var alerts = document.querySelectorAll('.alert');
  alerts.forEach(function(alert) {
    setTimeout(function() {
      alert.style.transition = 'opacity 0.5s ease';
      alert.style.opacity = '0';
      setTimeout(function() { if (alert.parentNode) alert.parentNode.removeChild(alert); }, 500);
    }, 5000);
  });
}

// ============================================================
// FILE UPLOAD PREVIEW
// ============================================================
function initFileUpload() {
  document.querySelectorAll('input[type="file"][data-preview]').forEach(function(input) {
    input.addEventListener('change', function() {
      var previewId = input.dataset.preview;
      var preview = document.getElementById(previewId);
      if (!preview) return;
      var file = input.files[0];
      if (file && file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });
  });
}

// ============================================================
// MOBILE MENU / SIDEBAR TOGGLE
// ============================================================
function initMobileMenu() {
  var btn     = document.getElementById('hamburger-btn');
  var sidebar = document.querySelector('.sidebar-kasih');

  // User page mobile nav is handled by inline kasihToggleNav() in layout.php
  // This function only handles admin/merchant sidebar sliding
  if (!btn || !sidebar) return;

  var overlay = document.getElementById('sidebar-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'sidebar-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:49;display:none;';
    document.body.appendChild(overlay);
  }

  // Override the onclick set in HTML for sidebar pages
  btn.onclick = null;
  btn.addEventListener('click', function() {
    var isOpen = sidebar.classList.toggle('open');
    overlay.style.display = isOpen ? 'block' : 'none';
    document.body.style.overflow = isOpen ? 'hidden' : '';
    btn.setAttribute('aria-expanded', String(isOpen));
  });
  overlay.addEventListener('click', function() {
    sidebar.classList.remove('open');
    overlay.style.display = 'none';
    document.body.style.overflow = '';
    btn.setAttribute('aria-expanded', 'false');
  });
}

// ============================================================
// TABLE SEARCH
// ============================================================
function initTableSearch() {
  document.querySelectorAll('.table-search-input').forEach(function(input) {
    var tableId = input.dataset.table;
    var table = tableId
      ? document.getElementById(tableId)
      : (input.closest('.card-kasih') || input.closest('div')).querySelector('table');
    if (!table) return;
    input.addEventListener('input', function() {
      var q = input.value.toLowerCase().trim();
      table.querySelectorAll('tbody tr').forEach(function(row) {
        row.style.display = (!q || row.textContent.toLowerCase().indexOf(q) !== -1) ? '' : 'none';
      });
    });
  });
}

// ============================================================
// FORMAT HELPERS
// ============================================================
function formatNumber(n, decimals) {
  decimals = (decimals !== undefined) ? decimals : 2;
  var num = parseFloat(n);
  return isNaN(num) ? '0.00' : num.toLocaleString('en-MY', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  });
}
function formatRM(n)     { return 'RM ' + formatNumber(n, 2); }
function formatPoints(n) { return formatNumber(n, 2) + ' pts'; }

function escapeHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// ============================================================
// CSRF FETCH WRAPPER
// ============================================================
function fetchWithCsrf(url, options) {
  options = options || {};
  options.headers = options.headers || {};
  var csrfMeta = document.querySelector('meta[name="csrf-token"]');
  if (csrfMeta) options.headers['X-CSRF-Token'] = csrfMeta.getAttribute('content');
  return fetch(url, options);
}

// ============================================================
// NUMERIC INPUT (clean on blur)
// ============================================================
function initNumericInputs() {
  document.querySelectorAll('input[data-numeric="true"]').forEach(function(input) {
    input.addEventListener('blur', function() {
      var decimals = parseInt(input.dataset.decimals || '2', 10);
      var val = parseFloat(input.value.replace(/,/g, ''));
      if (!isNaN(val)) input.value = val.toFixed(decimals);
    });
  });
}

// ============================================================
// COPY TO CLIPBOARD
// ============================================================
function copyToClipboard(text, btn) {
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(function() {
      if (btn) { var orig = btn.textContent; btn.textContent = 'Disalin!'; setTimeout(function() { btn.textContent = orig; }, 2000); }
    });
  } else {
    var el = document.createElement('textarea');
    el.value = text;
    el.style.cssText = 'position:fixed;left:-9999px;';
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
    if (btn) { var orig = btn.textContent; btn.textContent = 'Disalin!'; setTimeout(function() { btn.textContent = orig; }, 2000); }
  }
}

// ============================================================
// INIT ON DOM READY
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
  initLiveCalculator();
  initTransferForm();
  initFlashMessages();
  initFileUpload();
  initMobileMenu();
  initTableSearch();
  initNumericInputs();

  // Copy buttons
  document.querySelectorAll('[data-copy]').forEach(function(btn) {
    btn.addEventListener('click', function() {
      copyToClipboard(btn.dataset.copy, btn);
    });
  });

  // Confirm-action buttons (delete etc)
  document.querySelectorAll('[data-confirm]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      if (!confirm(btn.dataset.confirm)) e.preventDefault();
    });
  });
});
