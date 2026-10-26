// SkyNext Modern Hero Slider Logic
(() => {
  'use strict';

  // Global helper for opening SkyNext Chatbot
  window.openSkyNextChat = function(initialMsg) {
    const launch = document.querySelector('.sn-launch');
    const panel = document.querySelector('.sn-chat');
    if (launch) {
      if (panel && panel.hidden) {
        launch.click();
      }
    }
    if (initialMsg) {
      const input = document.querySelector('.sn-send input[name="message"]');
      if (input) {
        input.value = initialMsg;
        input.focus();
      }
    }
  };

  document.addEventListener('DOMContentLoaded', initHeroSlider);
  if (document.readyState === 'interactive' || document.readyState === 'complete') {
    initHeroSlider();
  }

  function initHeroSlider() {
    const slider = document.getElementById('skynextHeroSlider');
    if (!slider || slider.dataset.init === '1') return;
    slider.dataset.init = '1';

    const slides = Array.from(slider.querySelectorAll('.hero-slide'));
    const indicators = Array.from(slider.querySelectorAll('.hero-indicator'));
    const prevBtn = slider.querySelector('.hero-nav-arrow.prev');
    const nextBtn = slider.querySelector('.hero-nav-arrow.next');
    const counterEl = slider.querySelector('.hero-slide-counter');

    if (!slides.length) return;

    let currentIndex = 0;
    const duration = 6000; // 6s per slide
    let timer = null;
    let startTime = 0;
    let animFrame = null;
    let isPaused = false;

    function showSlide(index) {
      if (index < 0) index = slides.length - 1;
      if (index >= slides.length) index = 0;
      currentIndex = index;

      slides.forEach((s, i) => {
        s.classList.toggle('is-active', i === currentIndex);
      });

      indicators.forEach((ind, i) => {
        ind.classList.toggle('is-active', i === currentIndex);
        const bar = ind.querySelector('.hero-indicator-progress');
        if (bar) {
          bar.style.width = i === currentIndex ? '0%' : (i < currentIndex ? '100%' : '0%');
        }
      });

      if (counterEl) {
        counterEl.textContent = `0${currentIndex + 1} / 0${slides.length}`;
      }

      startProgress();
    }

    function startProgress() {
      cancelAnimationFrame(animFrame);
      clearTimeout(timer);
      if (isPaused) return;

      startTime = performance.now();

      function updateBar(now) {
        if (isPaused) return;
        const elapsed = now - startTime;
        const pct = Math.min(100, (elapsed / duration) * 100);
        const activeInd = indicators[currentIndex];
        if (activeInd) {
          const bar = activeInd.querySelector('.hero-indicator-progress');
          if (bar) bar.style.width = pct + '%';
        }

        if (elapsed < duration) {
          animFrame = requestAnimationFrame(updateBar);
        } else {
          showSlide(currentIndex + 1);
        }
      }

      animFrame = requestAnimationFrame(updateBar);
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', e => {
        e.preventDefault();
        showSlide(currentIndex - 1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener('click', e => {
        e.preventDefault();
        showSlide(currentIndex + 1);
      });
    }

    indicators.forEach((ind, i) => {
      ind.addEventListener('click', () => {
        showSlide(i);
      });
    });

    // Pause on mouse enter
    slider.addEventListener('mouseenter', () => {
      isPaused = true;
      cancelAnimationFrame(animFrame);
    });

    slider.addEventListener('mouseleave', () => {
      isPaused = false;
      startProgress();
    });

    // Keyboard navigation
    slider.addEventListener('keydown', e => {
      if (e.key === 'ArrowLeft') showSlide(currentIndex - 1);
      if (e.key === 'ArrowRight') showSlide(currentIndex + 1);
    });

    // Touch Swipe support
    let touchStartX = 0;
    let touchEndX = 0;

    slider.addEventListener('touchstart', e => {
      touchStartX = e.changedTouches[0].screenX;
      isPaused = true;
      cancelAnimationFrame(animFrame);
    }, { passive: true });

    slider.addEventListener('touchend', e => {
      touchEndX = e.changedTouches[0].screenX;
      isPaused = false;
      const diff = touchStartX - touchEndX;
      if (Math.abs(diff) > 45) {
        if (diff > 0) {
          showSlide(currentIndex + 1); // swipe left -> next
        } else {
          showSlide(currentIndex - 1); // swipe right -> prev
        }
      } else {
        startProgress();
      }
    }, { passive: true });

    // Initialize first slide
    showSlide(0);
  }
})();
