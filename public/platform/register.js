(() => {
  const box = document.getElementById('existing-membership');
  if (!box) return;
  const choices = document.querySelectorAll('input[name="membership_kind"]');
  const update = () => {
    const selected = document.querySelector('input[name="membership_kind"]:checked')?.value;
    const existing = selected === 'existing';
    box.hidden = !existing;
    box.querySelectorAll('input').forEach(input => { input.disabled = !existing; input.required = existing; });
    document.getElementById('new-membership-help').hidden = selected !== 'new';
    document.getElementById('customer-membership-help').hidden = selected !== 'customer';
  };
  choices.forEach(input => input.addEventListener('change', update));
  update();
})();
