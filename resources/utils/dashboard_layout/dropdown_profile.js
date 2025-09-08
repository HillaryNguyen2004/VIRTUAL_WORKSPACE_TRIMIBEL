document.addEventListener('DOMContentLoaded', () => {
  const wrap = document.getElementById('userMenu');
  const btn  = document.getElementById('userButton');
  const menu = document.getElementById('userList');

  const close  = () => { menu.classList.add('hidden'); btn.setAttribute('aria-expanded','false'); };
  const toggle = (e) => { e.stopPropagation(); const open = menu.classList.toggle('hidden') === false; btn.setAttribute('aria-expanded', open ? 'true':'false'); };

  btn.addEventListener('click', toggle);
  document.addEventListener('click', (e) => { if (!wrap.contains(e.target)) close(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
});