document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form');
  const btn = document.getElementById('submit-btn');
  if (!form || !btn) return;

  const spinner = btn.querySelector('[data-spinner]');

  form.addEventListener('submit', (e) => {
    if (!form.checkValidity()) return;

    btn.disabled = true;
    btn.setAttribute('aria-disabled', 'true');
    form.setAttribute('aria-busy', 'true');

    spinner?.classList.remove('hidden');
  });
});