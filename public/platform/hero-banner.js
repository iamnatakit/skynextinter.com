(() => {
  'use strict';
  function init() {
    const root = document.getElementById('skynextHeroSlider');
    if (!root || !root.classList.contains('sn-banner-hero') || root.dataset.ready) return;
    root.dataset.ready = '1';
    const slides = [...root.querySelectorAll('.sn-banner-slide')];
    const dots = [...root.querySelectorAll('[data-slide]')];
    const pause = root.querySelector('[data-action="pause"]');
    const mobileLink = root.querySelector('.sn-banner-mobile-cta a');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    let current = 0, timer, paused = reduced.matches, hovering = false, focused = false;
    function schedule() {
      clearTimeout(timer);
      const playing = !paused && !hovering && !focused && !document.hidden;
      root.classList.toggle('is-playing', !paused);
      root.classList.toggle('is-suspended', !playing);
      if (playing) timer = setTimeout(() => show(current + 1), 3000);
    }
    function show(index) {
      current = (index + slides.length) % slides.length;
      slides.forEach((slide, i) => {
        slide.hidden = i !== current;
        slide.inert = i !== current;
        slide.classList.toggle('is-active', i === current);
        slide.querySelectorAll('a').forEach(link => { if(i===current) link.removeAttribute('tabindex'); else link.tabIndex=-1; });
      });
      dots.forEach((dot, i) => {
        dot.classList.remove('is-active');
        dot.setAttribute('aria-pressed', String(i === current));
      });
      // Restart the active indicator for both automatic and manual navigation.
      void root.offsetWidth;
      dots[current].classList.add('is-active');
      root.querySelector('.sn-banner-count').textContent = `${String(current + 1).padStart(2, '0')} / ${String(slides.length).padStart(2, '0')}`;
      mobileLink.href = slides[current].dataset.ctaUrl;
      mobileLink.replaceChildren(document.createTextNode(slides[current].dataset.cta + ' ↗'));
      root.dataset.current = String(current);
      schedule();
    }
    function setPaused(value) {
      paused = value;
      pause.setAttribute('aria-pressed', String(paused));
      pause.setAttribute('aria-label', paused ? 'เล่นสไลด์อัตโนมัติ' : 'หยุดสไลด์อัตโนมัติ');
      pause.textContent = paused ? '▶' : 'Ⅱ';
      schedule();
    }
    root.querySelector('[data-action="previous"]').addEventListener('click', () => show(current - 1));
    root.querySelector('[data-action="next"]').addEventListener('click', () => show(current + 1));
    dots.forEach((dot, index) => dot.addEventListener('click', () => show(index)));
    pause.addEventListener('click', () => setPaused(!paused));
    root.addEventListener('mouseenter', () => { hovering = true; schedule(); });
    root.addEventListener('mouseleave', () => { hovering = false; schedule(); });
    root.addEventListener('focusin', () => { focused = true; schedule(); });
    root.addEventListener('focusout', event => { focused = root.contains(event.relatedTarget); schedule(); });
    root.addEventListener('keydown', event => {
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
        event.preventDefault(); show(current + (event.key === 'ArrowRight' ? 1 : -1));
      }
    });
    let startX = null;
    root.querySelector('.sn-banner-stage').addEventListener('touchstart', event => { startX = event.touches[0].clientX; }, {passive:true});
    root.querySelector('.sn-banner-stage').addEventListener('touchend', event => {
      if (startX !== null && Math.abs(event.changedTouches[0].clientX - startX) > 45) show(current + (event.changedTouches[0].clientX < startX ? 1 : -1));
      startX = null;
    }, {passive:true});
    document.addEventListener('visibilitychange', schedule);
    reduced.addEventListener('change', event => setPaused(event.matches));
    show(0); setPaused(paused);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once:true});
  else init();
})();
