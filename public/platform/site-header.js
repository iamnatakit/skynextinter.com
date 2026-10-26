(() => {
  const header = document.getElementById('sn-site-header');
  if (!header) return;
  const menu = header.querySelector('.sn-menu-toggle');
  const nav = header.querySelector('.sn-primary-nav');
  const search = header.querySelector('[data-sn-search]');
  const searchForm = header.querySelector('.sn-header-search');
  const closeMenu = () => {
    nav.classList.remove('is-open');
    menu.setAttribute('aria-expanded', 'false');
  };
  const closeSearch = () => {
    searchForm.hidden = true;
    search.setAttribute('aria-expanded', 'false');
  };

  if (menu && nav) {
    menu.addEventListener('click', () => {
      const open = menu.getAttribute('aria-expanded') !== 'true';
      closeSearch();
      nav.classList.toggle('is-open', open);
      menu.setAttribute('aria-expanded', String(open));
    });
  }

  if (search && searchForm) {
    search.addEventListener('click', () => {
      const open = searchForm.hidden;
      closeMenu();
      searchForm.hidden = !open;
      search.setAttribute('aria-expanded', String(open));
      if (open) searchForm.querySelector('input').focus();
    });
  }

  // Dropdown toggle on mobile
  header.querySelectorAll('.sn-dropdown').forEach(drop => {
    const link = drop.querySelector('.sn-nav-link');
    if (!link) return;
    link.addEventListener('click', (e) => {
      if (window.innerWidth <= 1320) {
        e.preventDefault();
        drop.classList.toggle('is-open');
      }
    });
  });

  document.addEventListener('click', e => {
    if (!header.contains(e.target)) {
      closeMenu();
      closeSearch();
    }
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      if (searchForm && !searchForm.hidden) {
        closeSearch();
        search.focus();
      }
      if (nav && nav.classList.contains('is-open')) {
        closeMenu();
        menu.focus();
      }
    }
  });
})();
