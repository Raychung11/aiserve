<?php /* Public colour-theme switcher + scroll-reveal interactivity */ ?>
<style>
/* ---- Accent themes: override the brand tokens per selected theme ---- */
:root[data-theme="ocean"]{   --primary:#0369a1; --primary2:#0ea5e9; --primary3:#e0f2fe; }
:root[data-theme="emerald"]{ --primary:#059669; --primary2:#10b981; --primary3:#d1fae5; }
:root[data-theme="sunset"]{  --primary:#db2777; --primary2:#f97316; --primary3:#ffe4e6; }

/* Smooth accent recolour when switching themes */
a, .btn, .btn-primary, .btn-secondary, .card, .icon, .eyebrow, .pill,
input, select, textarea, .brand-mark, .brand-fallback {
    transition: background-color .35s ease, border-color .35s ease, color .35s ease, box-shadow .35s ease;
}

/* ---- Scroll-reveal (progressive enhancement: hidden state added by JS) ---- */
.js-reveal{ opacity:0; transform:translateY(18px); transition:opacity .6s ease, transform .6s ease; }
.js-reveal.is-visible{ opacity:1; transform:none; }

/* Staggered feel inside grids */
.grid-3 .js-reveal:nth-child(2), .grid-4 .js-reveal:nth-child(2){ transition-delay:.08s; }
.grid-3 .js-reveal:nth-child(3), .grid-4 .js-reveal:nth-child(3){ transition-delay:.16s; }
.grid-4 .js-reveal:nth-child(4){ transition-delay:.24s; }

/* ---- Animated hero glow ---- */
.hero-section{ position:relative; overflow:hidden; }
.hero-section::after{
    content:""; position:absolute; top:-40%; right:-8%;
    width:55%; height:180%;
    background:radial-gradient(circle, rgba(255,255,255,.18), transparent 60%);
    animation:heroFloat 9s ease-in-out infinite;
    pointer-events:none;
}
@keyframes heroFloat{ 0%,100%{ transform:translateY(0);} 50%{ transform:translateY(34px);} }

/* ---- Theme switcher control ---- */
.theme-switcher{ position:fixed; left:18px; bottom:18px; z-index:60; }
.theme-toggle{
    width:48px; height:48px; border-radius:50%;
    border:1px solid var(--line); background:#fff; cursor:pointer;
    box-shadow:var(--shadow); display:grid; place-items:center; font-size:20px;
    transition:transform .2s ease;
}
.theme-toggle:hover{ transform:translateY(-2px) rotate(-8deg); }
.theme-panel{
    position:absolute; left:0; bottom:58px;
    background:#fff; border:1px solid var(--line); border-radius:16px;
    box-shadow:var(--shadow-lg); padding:12px;
    display:none; grid-template-columns:repeat(4,1fr); gap:12px;
}
.theme-panel.open{ display:grid; }
.theme-panel-label{ grid-column:1/-1; font-size:12px; font-weight:800; color:var(--muted); text-transform:uppercase; letter-spacing:.08em; }
.theme-dot{
    width:30px; height:30px; border-radius:50%; cursor:pointer;
    border:2px solid transparent; box-shadow:0 2px 6px rgba(0,0,0,.12);
    transition:transform .15s ease;
}
.theme-dot:hover{ transform:scale(1.12); }
.theme-dot.active{ border-color:var(--dark); }

@media (prefers-reduced-motion: reduce){
    .js-reveal{ opacity:1 !important; transform:none !important; transition:none; }
    .hero-section::after{ animation:none; }
}
</style>

<div class="theme-switcher">
    <div class="theme-panel" id="themePanel">
        <span class="theme-panel-label">Colour theme</span>
        <span class="theme-dot" data-theme="violet"  title="Violet"  style="background:linear-gradient(135deg,#6d28d9,#8b5cf6)"></span>
        <span class="theme-dot" data-theme="ocean"   title="Ocean"   style="background:linear-gradient(135deg,#0369a1,#0ea5e9)"></span>
        <span class="theme-dot" data-theme="emerald" title="Emerald" style="background:linear-gradient(135deg,#059669,#10b981)"></span>
        <span class="theme-dot" data-theme="sunset"  title="Sunset"  style="background:linear-gradient(135deg,#db2777,#f97316)"></span>
    </div>
    <button class="theme-toggle" type="button" aria-label="Change colour theme" title="Change colour theme">🎨</button>
</div>

<script>
(function(){
    const KEY = 'aiserve_theme';
    const valid = ['violet', 'ocean', 'emerald', 'sunset'];

    function apply(theme){
        if (!valid.includes(theme)) theme = 'violet';
        if (theme === 'violet') {
            document.documentElement.removeAttribute('data-theme');
        } else {
            document.documentElement.setAttribute('data-theme', theme);
        }
        document.querySelectorAll('.theme-dot').forEach(function(d){
            d.classList.toggle('active', d.dataset.theme === theme);
        });
    }

    let current = 'violet';
    try { current = localStorage.getItem(KEY) || 'violet'; } catch(e){}
    apply(current);

    const toggle = document.querySelector('.theme-toggle');
    const panel = document.getElementById('themePanel');

    if (toggle && panel) {
        toggle.addEventListener('click', function(e){
            e.stopPropagation();
            panel.classList.toggle('open');
        });
        document.addEventListener('click', function(){ panel.classList.remove('open'); });
        panel.addEventListener('click', function(e){ e.stopPropagation(); });
    }

    document.querySelectorAll('.theme-dot').forEach(function(d){
        d.addEventListener('click', function(){
            const t = d.dataset.theme;
            try { localStorage.setItem(KEY, t); } catch(e){}
            apply(t);
            if (panel) panel.classList.remove('open');
        });
    });

    // Scroll reveal
    const targets = document.querySelectorAll('.card, .section-head, .form-box, .hero-card');
    if ('IntersectionObserver' in window && targets.length) {
        const io = new IntersectionObserver(function(entries){
            entries.forEach(function(en){
                if (en.isIntersecting) {
                    en.target.classList.add('is-visible');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.12 });
        targets.forEach(function(el){
            el.classList.add('js-reveal');
            io.observe(el);
        });
    }
})();
</script>
