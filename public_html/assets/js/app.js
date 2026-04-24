/**
 * MM2H 管家 Platform — app.js
 */

document.addEventListener('DOMContentLoaded', () => {

  // ── Highlight active nav link ─────────────────────────────────────────────
  const path = window.location.pathname;
  document.querySelectorAll('.navbar .nav-link, .sidebar .nav-link').forEach(link => {
    try {
      const href = new URL(link.href).pathname;
      if (href !== '/' && path.startsWith(href)) {
        link.classList.add('active');
      }
    } catch { /* ignore */ }
  });

  // ── Auto-dismiss alerts ───────────────────────────────────────────────────
  document.querySelectorAll('.alert-auto-dismiss').forEach(el => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
      bsAlert.close();
    }, 5000);
  });

  // ── AJAX form: AI eligibility check ──────────────────────────────────────
  const eligibilityForm = document.getElementById('eligibilityForm');
  if (eligibilityForm) {
    eligibilityForm.addEventListener('submit', async e => {
      e.preventDefault();
      const btn = eligibilityForm.querySelector('[type=submit]');
      const resultBox = document.getElementById('eligibilityResult');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Checking…';

      const formData = new FormData(eligibilityForm);
      try {
        const res  = await fetch('/api/ai_onboarding.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (resultBox) {
          resultBox.innerHTML = renderEligibilityResult(data);
          resultBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      } catch {
        if (resultBox) resultBox.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
      } finally {
        btn.disabled = false;
        btn.innerHTML = btn.getAttribute('data-original-text') || 'Check Eligibility';
      }
    });

    // Store original button text
    const submitBtn = eligibilityForm.querySelector('[type=submit]');
    if (submitBtn) submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
  }

  // ── Render eligibility result ─────────────────────────────────────────────
  function renderEligibilityResult(data) {
    const classMap = {
      eligible:     'eligible',
      potential:    'potential',
      consult:      'consult',
      insufficient: 'insufficient',
    };
    const iconMap = {
      eligible:     '✅',
      potential:    '🟡',
      consult:      'ℹ️',
      insufficient: '❓',
    };
    const cls  = classMap[data.result] || 'consult';
    const icon = iconMap[data.result]  || 'ℹ️';
    let html = `<div class="eligibility-result ${cls}">
      <div class="result-icon">${icon}</div>
      <div class="result-title">${escHtml(data.title || data.result)}</div>
      <p class="text-muted">${escHtml(data.message || '')}</p>`;

    if (data.suggested_route) {
      html += `<div class="mt-3 text-start">
        <h6 class="fw-bold">Suggested Route</h6>
        <p class="mb-1">${escHtml(data.suggested_route)}</p>
      </div>`;
    }
    if (data.documents && data.documents.length) {
      html += `<div class="mt-3 text-start">
        <h6 class="fw-bold">Documents Needed</h6>
        <ul>${data.documents.map(d => `<li>${escHtml(d)}</li>`).join('')}</ul>
      </div>`;
    }
    if (data.next_action) {
      html += `<div class="mt-3">
        <a href="${escHtml(data.cta_url || '/register')}" class="btn btn-gold">
          ${escHtml(data.next_action)}
        </a>
      </div>`;
    }
    html += '</div>';
    return html;
  }

  // ── Onboarding multi-step form ────────────────────────────────────────────
  const onboardingSteps = document.querySelectorAll('.onboarding-panel');
  if (onboardingSteps.length) {
    let currentStep = 0;

    function showStep(idx) {
      onboardingSteps.forEach((p, i) => {
        p.style.display = (i === idx) ? 'block' : 'none';
      });
      document.querySelectorAll('.onboarding-step').forEach((s, i) => {
        s.classList.remove('active', 'done');
        if (i < idx)  s.classList.add('done');
        if (i === idx) s.classList.add('active');
      });
      // Update prev/next buttons
      const prevBtn = document.getElementById('stepPrev');
      const nextBtn = document.getElementById('stepNext');
      const submitBtn = document.getElementById('stepSubmit');
      if (prevBtn)   prevBtn.style.display   = idx > 0 ? 'inline-flex' : 'none';
      if (nextBtn)   nextBtn.style.display   = idx < onboardingSteps.length - 1 ? 'inline-flex' : 'none';
      if (submitBtn) submitBtn.style.display = idx === onboardingSteps.length - 1 ? 'inline-flex' : 'none';
    }

    showStep(0);

    document.getElementById('stepNext')?.addEventListener('click', () => {
      if (currentStep < onboardingSteps.length - 1) {
        currentStep++;
        showStep(currentStep);
      }
    });
    document.getElementById('stepPrev')?.addEventListener('click', () => {
      if (currentStep > 0) {
        currentStep--;
        showStep(currentStep);
      }
    });
  }

  // ── File upload preview ───────────────────────────────────────────────────
  document.querySelectorAll('.file-upload-input').forEach(input => {
    input.addEventListener('change', function () {
      const preview = document.getElementById(this.dataset.preview);
      if (!preview) return;
      if (this.files && this.files[0]) {
        preview.textContent = this.files[0].name + ' (' + formatBytes(this.files[0].size) + ')';
        preview.classList.remove('d-none');
      }
    });
  });

  function formatBytes(bytes) {
    if (bytes < 1024)        return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  }

  // ── Confirm delete ────────────────────────────────────────────────────────
  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
      if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
    });
  });

  // ── Tooltip init ──────────────────────────────────────────────────────────
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    bootstrap.Tooltip.getOrCreateInstance(el);
  });

  // ── XSS safe escape helper ────────────────────────────────────────────────
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // ── Referral code copy ────────────────────────────────────────────────────
  document.querySelectorAll('.copy-referral').forEach(btn => {
    btn.addEventListener('click', () => {
      const code = btn.dataset.code;
      if (code) {
        navigator.clipboard.writeText(code).then(() => {
          const orig = btn.innerHTML;
          btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
          setTimeout(() => { btn.innerHTML = orig; }, 2000);
        });
      }
    });
  });

});
