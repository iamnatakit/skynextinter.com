(() => {
  document.querySelectorAll('[data-blog-slideshow]').forEach(root => {
    const slides = [...root.querySelectorAll('.sn-blog-slide')];
    const dots = [...root.querySelectorAll('[data-slide-to]')];
    const toggle = root.querySelector('[data-slide-toggle]');
    const count = root.querySelector('[data-slide-count]');
    const status = root.querySelector('[data-slide-status]');
    const stage = root.querySelector('.sn-blog-slideshow-stage');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    let index = 0, paused = reduced.matches, hovering = false, focused = false, timer;
    let touchStart = null;
    const schedule = () => {
      clearInterval(timer);
      if (!paused && !hovering && !focused && !document.hidden) {
        timer = setInterval(() => show(index + 1), 6000);
      }
    };
    const show = (next, announce = false) => {
      index = (next + slides.length) % slides.length;
      slides.forEach((slide, i) => { slide.hidden = i !== index; });
      dots.forEach((dot, i) => {
        if (i === index) dot.setAttribute('aria-current', 'true');
        else dot.removeAttribute('aria-current');
      });
      count.textContent = `${index + 1} / ${slides.length}`;
      if (announce) status.textContent = `สไลด์ ${index + 1} จาก ${slides.length}: ${slides[index].querySelector('img').alt}`;
    };
    const navigate = next => { show(next, true); schedule(); };
    const updateToggle = () => {
      toggle.textContent = paused ? 'เล่นอัตโนมัติ' : 'หยุดอัตโนมัติ';
      toggle.setAttribute('aria-label', paused ? 'เล่นสไลด์อัตโนมัติ' : 'หยุดสไลด์อัตโนมัติ');
    };
    root.querySelector('[data-slide-prev]').addEventListener('click', () => navigate(index - 1));
    root.querySelector('[data-slide-next]').addEventListener('click', () => navigate(index + 1));
    dots.forEach((dot, i) => dot.addEventListener('click', () => navigate(i)));
    toggle.addEventListener('click', () => { paused = !paused; updateToggle(); schedule(); });
    root.addEventListener('keydown', event => {
      if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
        event.preventDefault(); navigate(index + (event.key === 'ArrowRight' ? 1 : -1));
      }
    });
    root.addEventListener('mouseenter', () => { hovering = true; schedule(); });
    root.addEventListener('mouseleave', () => { hovering = false; schedule(); });
    root.addEventListener('focusin', () => { focused = true; schedule(); });
    root.addEventListener('focusout', event => {
      focused = root.contains(event.relatedTarget); schedule();
    });
    stage.addEventListener('pointerdown', event => {
      if (event.pointerType === 'touch') touchStart = [event.clientX, event.clientY];
    });
    stage.addEventListener('pointerup', event => {
      if (!touchStart) return;
      const dx = event.clientX - touchStart[0], dy = event.clientY - touchStart[1];
      touchStart = null;
      if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) navigate(index + (dx < 0 ? 1 : -1));
    });
    stage.addEventListener('pointercancel', () => { touchStart = null; });
    document.addEventListener('visibilitychange', schedule);
    reduced.addEventListener('change', () => { paused = reduced.matches; updateToggle(); schedule(); });
    root.querySelectorAll('[data-slide-prev],[data-slide-next],[data-slide-controls]').forEach(control => { control.hidden = false; });
    updateToggle(); schedule();
  });
})();
