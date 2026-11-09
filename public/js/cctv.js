/* Layar pantau CCTV: memutar HLS, MJPEG, snapshot, dan iframe (gateway RTSP, embed, YouTube).
   Pemutar hanya dijalankan saat ubin terlihat di layar dan dihentikan saat tab disembunyikan. */
(function () {
  const grid = document.getElementById('cctvGrid');
  if (!grid) return;
  const store = { get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }, set(k, v) { try { localStorage.setItem(k, v); } catch (e) {} } };

  // ---------- tata letak & filter ----------
  const setCol = n => {
    grid.dataset.col = n; store.set('rt_cctv_col', n);
    document.querySelectorAll('#kolom [data-col]').forEach(b => b.classList.toggle('active', b.dataset.col == n));
  };
  setCol(store.get('rt_cctv_col') || (window.innerWidth < 768 ? 1 : 2));
  document.getElementById('kolom').addEventListener('click', e => { const b = e.target.closest('[data-col]'); if (b) setCol(b.dataset.col); });
  document.getElementById('filterArea').addEventListener('click', e => {
    const b = e.target.closest('[data-area]'); if (!b) return;
    document.querySelectorAll('#filterArea [data-area]').forEach(x => { x.classList.toggle('btn-primary', x === b); x.classList.toggle('btn-light', x !== b); });
    grid.querySelectorAll('.cctv-tile').forEach(t => { t.hidden = b.dataset.area !== '' && t.dataset.area !== b.dataset.area; });
  });
  const jam = document.getElementById('jamCctv');
  const tick = () => { jam.textContent = new Date().toLocaleString('id-ID', { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit', second: '2-digit' }); };
  tick(); setInterval(tick, 1000);

  // ---------- pemutar ----------
  function pesan(v, teks, ulang) {
    const m = v.querySelector('.cctv-msg');
    m.innerHTML = teks ? `<i class="bi bi-camera-video-off fs-3"></i><span></span>${ulang ? '<button type="button" class="btn btn-sm btn-light mt-1">Coba lagi</button>' : ''}` : '';
    if (teks) m.querySelector('span').textContent = teks;
    m.hidden = !teks; v.classList.toggle('mati', !!teks);
    const b = m.querySelector('button'); if (b) b.onclick = () => { stop(v); start(v); };
  }
  function media(v) { return v.querySelector('video,img.feed,iframe'); }

  function start(v) {
    if (v._on) return; v._on = true;
    const kind = v.dataset.kind, src = v.dataset.src;
    v.querySelector('.cctv-msg').innerHTML = '<div class="spinner-border spinner-border-sm"></div><span>Menghubungkan...</span>';
    v.querySelector('.cctv-msg').hidden = false; v.classList.remove('mati');
    if (kind === 'error' || !src) { pesan(v, v.dataset.error || 'Sumber kamera belum diatur.', false); return; }

    if (kind === 'iframe') {
      const f = document.createElement('iframe');
      f.src = src; f.allow = 'autoplay; fullscreen; picture-in-picture'; f.allowFullscreen = true;
      f.referrerPolicy = 'no-referrer'; f.title = 'CCTV';
      f.onload = () => pesan(v, '');
      v.prepend(f);
      setTimeout(() => { if (v._on && !v.classList.contains('mati')) pesan(v, ''); }, 6000);
    } else if (kind === 'hls') {
      const el = document.createElement('video');
      el.muted = true; el.autoplay = true; el.playsInline = true; el.setAttribute('playsinline', '');
      el.onplaying = () => pesan(v, '');
      el.onerror = () => pesan(v, 'Siaran tidak bisa diputar.', true);
      v.prepend(el);
      if (window.Hls && Hls.isSupported()) {
        const h = new Hls({ liveDurationInfinity: true, lowLatencyMode: true });
        h.on(Hls.Events.ERROR, (e, d) => { if (d.fatal) pesan(v, 'Siaran terputus.', true); });
        h.loadSource(src); h.attachMedia(el); v._hls = h;
      } else if (el.canPlayType('application/vnd.apple.mpegurl')) {
        el.src = src;
      } else {
        pesan(v, 'Browser ini tidak mendukung HLS (pustaka hls.js belum termuat).', false); return;
      }
      el.play().catch(() => {});
    } else {
      const img = document.createElement('img');
      img.className = 'feed'; img.alt = 'CCTV';
      img.onload = () => { pesan(v, ''); v._gagal = 0; };
      img.onerror = () => { if (kind === 'snapshot' && ++v._gagal < 3) return; pesan(v, 'Kamera tidak merespons.', true); };
      v._gagal = 0;
      const muat = () => { img.src = src + (src.includes('?') ? '&' : '?') + '_t=' + Date.now(); };
      muat();
      if (kind === 'snapshot') v._timer = setInterval(() => { if (!document.hidden) muat(); }, Math.max(1, +v.dataset.refresh || 5) * 1000);
      v.prepend(img);
    }
  }
  function stop(v) {
    v._on = false; clearInterval(v._timer);
    if (v._hls) { v._hls.destroy(); v._hls = null; }
    const m = media(v);
    if (m) { if (m.tagName === 'IMG') m.src = ''; m.remove(); }   // memutus aliran MJPEG / iframe
  }

  const views = [...grid.querySelectorAll('.cctv-view')];
  const terlihat = new Set();
  const io = 'IntersectionObserver' in window ? new IntersectionObserver(es => es.forEach(e => {
    if (e.isIntersecting) { terlihat.add(e.target); if (!document.hidden) start(e.target); }
    else { terlihat.delete(e.target); stop(e.target); }
  }), { rootMargin: '100px' }) : null;
  views.forEach(v => { io ? io.observe(v) : (terlihat.add(v), start(v)); });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) views.forEach(v => { if (v.dataset.kind !== 'snapshot') stop(v); });
    else terlihat.forEach(start);
  });

  // ---------- layar penuh ----------
  grid.addEventListener('click', e => {
    const b = e.target.closest('.cctv-fs'); if (!b) return;
    const v = b.closest('.cctv-view');
    if (document.fullscreenElement) document.exitFullscreen();
    else if (v.requestFullscreen) v.requestFullscreen().catch(() => v.classList.toggle('besar'));
    else v.classList.toggle('besar');
  });
})();
