(() => {
  document.querySelectorAll('[data-sn-affiliate-menu]').forEach(menu => {
    const button = menu.querySelector('.sn-affiliate-toggle');
    const links = menu.querySelector('.sn-affiliate-links');
    const setOpen = open => {
      links.hidden = !open;
      button.setAttribute('aria-expanded', String(open));
    };
    button.addEventListener('click', () => setOpen(links.hidden));
    button.addEventListener('keydown', event => {
      if (event.key === 'ArrowDown') {
        event.preventDefault();
        setOpen(true);
        links.querySelector('a').focus();
      }
    });
    menu.addEventListener('keydown', event => {
      if (event.key === 'Escape' && !links.hidden) {
        event.stopPropagation();
        setOpen(false);
        button.focus();
      }
    });
    menu.addEventListener('focusout', event => {
      if (!menu.contains(event.relatedTarget)) setOpen(false);
    });
    document.addEventListener('click', event => {
      if (!menu.contains(event.target)) setOpen(false);
    });
  });
})();
