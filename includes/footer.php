    </main>
  </div>
</div>

<script>
function openMobile() {
  document.getElementById('sidebar').classList.remove('-translate-x-full');
  document.getElementById('overlay').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeMobile() {
  document.getElementById('sidebar').classList.add('-translate-x-full');
  document.getElementById('overlay').classList.add('hidden');
  document.body.style.overflow = '';
}

// ── Live Search AJAX (1s debounce) ──
document.querySelectorAll('form[method="GET"]').forEach(form => {
  const searchInput = form.querySelector('input[type="search"]');
  if (!searchInput) return;

  let timer;
  const targetId = 'search-results';

  const doSearch = async () => {
    const formData = new FormData(form);
    const params = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
      if (value) params.set(key, value);
    }
    const url = `${form.action || window.location.pathname}?${params.toString()}`;

    // Actualizar URL sin recargar
    history.replaceState(null, '', url);

    const container = document.getElementById(targetId);
    if (!container) return;
    
    container.style.opacity = '0.5';
    container.style.transition = 'opacity 0.2s';

    try {
      const res = await fetch(url);
      const html = await res.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const newContent = doc.getElementById(targetId);

      if (newContent) {
        container.innerHTML = newContent.innerHTML;
      }
    } catch (e) {
      console.error('Error en búsqueda:', e);
    } finally {
      container.style.opacity = '1';
    }
  };

  form.addEventListener('input', (e) => {
    if (e.target.type === 'search' || e.target.tagName === 'SELECT') {
      clearTimeout(timer);
      timer = setTimeout(doSearch, 1000);
    }
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    clearTimeout(timer);
    doSearch();
  });
});
</script>
</body>
</html>
