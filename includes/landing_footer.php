<footer style="background:#0f172a;color:#94a3b8;padding:4rem 0 2rem;">
  <div class="container">
    <div class="row g-4 mb-4">

      <!-- Brand col -->
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <i class="bi bi-house-heart-fill" style="color:#6366f1;font-size:1.4rem;"></i>
          <span class="text-white fw-bold" style="font-size:1.1rem;">Roomee</span>
        </div>
        <p style="font-size:.875rem;line-height:1.7;color:#64748b;">
          Malaysia's intelligent property management platform for STR, mid-term,
          sublet and corporate leasing — built for property managers who want clarity, not chaos.
        </p>
        <div class="d-flex gap-2 mt-3">
          <span style="background:rgba(99,102,241,.15);color:#a5b4fc;font-size:.7rem;padding:.25rem .6rem;border-radius:20px;font-weight:600;">14-day free trial</span>
          <span style="background:rgba(99,102,241,.15);color:#a5b4fc;font-size:.7rem;padding:.25rem .6rem;border-radius:20px;font-weight:600;">No credit card</span>
        </div>
      </div>

      <!-- Product links -->
      <div class="col-6 col-lg-2 offset-lg-1">
        <div class="text-white fw-semibold mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.07em;">Product</div>
        <ul class="list-unstyled" style="font-size:.875rem;">
          <li class="mb-2"><a href="#features"  style="color:#64748b;text-decoration:none;" onmouseover="this.style.color='#a5b4fc'" onmouseout="this.style.color='#64748b'">Features</a></li>
          <li class="mb-2"><a href="#pricing"   style="color:#64748b;text-decoration:none;" onmouseover="this.style.color='#a5b4fc'" onmouseout="this.style.color='#64748b'">Pricing</a></li>
          <li class="mb-2"><a href="#how"       style="color:#64748b;text-decoration:none;" onmouseover="this.style.color='#a5b4fc'" onmouseout="this.style.color='#64748b'">How It Works</a></li>
        </ul>
      </div>

      <!-- Platform links -->
      <div class="col-6 col-lg-2">
        <div class="text-white fw-semibold mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.07em;">Platform</div>
        <ul class="list-unstyled" style="font-size:.875rem;">
          <li class="mb-2"><a href="<?= APP_URL ?>/login"    style="color:#64748b;text-decoration:none;" onmouseover="this.style.color='#a5b4fc'" onmouseout="this.style.color='#64748b'">Sign In</a></li>
          <li class="mb-2"><a href="<?= APP_URL ?>/register" style="color:#64748b;text-decoration:none;" onmouseover="this.style.color='#a5b4fc'" onmouseout="this.style.color='#64748b'">Register</a></li>
          <li class="mb-2 mt-3">
            <a href="https://roomee.my/colive" target="_blank"
               style="color:#c084fc;text-decoration:none;font-weight:600;display:flex;align-items:center;gap:.4rem;"
               onmouseover="this.style.color='#e879f9'" onmouseout="this.style.color='#c084fc'">
              <i class="bi bi-house-heart-fill" style="font-size:.85rem;"></i> CoLive OS
              <i class="bi bi-box-arrow-up-right" style="font-size:.65rem;opacity:.7;"></i>
            </a>
          </li>
        </ul>
      </div>

      <!-- CTA col -->
      <div class="col-lg-3">
        <div class="text-white fw-semibold mb-3" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.07em;">Get Started</div>
        <p style="font-size:.8rem;color:#64748b;margin-bottom:1rem;">Start managing your properties smarter — free for 14 days.</p>
        <a href="<?= APP_URL ?>/register" class="btn btn-sm" style="background:#6366f1;color:#fff;border-radius:8px;padding:.5rem 1.25rem;font-weight:600;font-size:.85rem;">
          <i class="bi bi-rocket-takeoff me-1"></i> Start Free Trial
        </a>
      </div>

    </div>

    <div class="border-top d-flex flex-column flex-md-row align-items-center justify-content-between pt-3 gap-2"
         style="border-color:rgba(255,255,255,.07)!important;">
      <div style="font-size:.78rem;color:#334155;">
        &copy; <?= date('Y') ?> Roomee. All rights reserved.
      </div>
      <div style="font-size:.78rem;color:#334155;">
        Built for Malaysian property managers 🇲🇾
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
