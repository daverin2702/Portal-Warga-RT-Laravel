/* Portal Warga RT: lonceng notifikasi, tombol darurat, dan chat pop-up.
   Data diambil berkala (polling) dari rute Laravel yang dikirim lewat window.RT. */
(function () {
  if (!window.RT) return;
  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const jam = t => { const d = new Date(t); return isNaN(d) ? '' : d.toLocaleString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }); };
  const store = {
    get(k, d) { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch (e) { return d; } },
    set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} },
  };

  async function api(url, body) {
    const opt = { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } };
    if (body !== undefined) {
      opt.method = 'POST';
      opt.headers['Content-Type'] = 'application/json';
      opt.headers['X-CSRF-TOKEN'] = RT.csrf;
      opt.body = JSON.stringify(body);
    }
    const r = await fetch(url, opt);
    if (r.status === 401 || r.status === 419) { location.reload(); throw new Error('Sesi berakhir.'); }
    const j = await r.json().catch(() => ({ error: 'Server tidak merespons.' }));
    if (!r.ok || j.error) throw new Error(j.error || (j.errors ? Object.values(j.errors).flat().join(' ') : j.message) || 'Gagal memuat.');
    return j;
  }

  // ---------- suara ----------
  let ctx;
  function bunyi(sirene) {
    try {
      ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
      const n = sirene ? 6 : 1;
      for (let i = 0; i < n; i++) {
        const o = ctx.createOscillator(), g = ctx.createGain(), t = ctx.currentTime + i * 0.45;
        o.type = sirene ? 'sawtooth' : 'sine';
        o.frequency.setValueAtTime(sirene ? 880 : 740, t);
        if (sirene) o.frequency.linearRampToValueAtTime(560, t + 0.4);
        g.gain.setValueAtTime(sirene ? 0.25 : 0.12, t); g.gain.exponentialRampToValueAtTime(0.001, t + 0.42);
        o.connect(g).connect(ctx.destination); o.start(t); o.stop(t + 0.43);
      }
    } catch (e) {}
  }
  document.addEventListener('click', () => { try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); ctx.resume(); } catch (e) {} }, { once: true });

  // ---------- notifikasi ----------
  const IKON = { darurat: 'bi-exclamation-octagon-fill text-danger', persetujuan: 'bi-check2-square text-primary', tagihan: 'bi-wallet2 text-warning',
    pengumuman: 'bi-megaphone text-info', aduan: 'bi-chat-left-text text-secondary', info: 'bi-info-circle text-secondary' };
  const kunciTerakhir = 'rt_notif_terakhir_' + RT.me;
  let dilihat = new Set(store.get(kunciTerakhir, [])), pertama = true;
  function gambarNotif(d) {
    const b = $('bellBadge'); b.classList.toggle('d-none', !d.unread); b.textContent = d.unread > 9 ? '9+' : d.unread;
    $('notifList').innerHTML = d.items.length ? d.items.map(n =>
      `<a class="notif-item ${n.read ? '' : 'baru'}" href="${esc(n.url || '#')}" data-id="${esc(n.id)}"><i class="bi ${IKON[n.kind] || IKON.info}"></i><div><div class="fw-semibold">${esc(n.title)}</div>${n.body ? `<div class="small text-secondary">${esc(n.body)}</div>` : ''}<div class="small text-secondary">${jam(n.at)}</div></div></a>`).join('')
      : '<div class="p-3 small text-secondary">Belum ada notifikasi.</div>';
    const baru = d.items.filter(n => !n.read && !dilihat.has(n.id));
    if (baru.length && !pertama) {
      bunyi(false);
      if ('Notification' in window && Notification.permission === 'granted') {
        baru.slice(0, 3).forEach(n => { try { new Notification(n.title, { body: n.body || '', tag: 'rt-' + n.id }); } catch (e) {} });
      }
    }
    d.items.forEach(n => dilihat.add(n.id));
    store.set(kunciTerakhir, [...dilihat].slice(-60));
  }
  $('notifList').addEventListener('click', e => { const a = e.target.closest('.notif-item'); if (a) api(RT.url.read, { id: a.dataset.id }).catch(() => {}); });
  $('bacaSemua').onclick = () => api(RT.url.read, { all: true }).then(poll).catch(() => {});
  $('izinBrowser').onclick = e => {
    e.preventDefault();
    if (!('Notification' in window)) { alert('Browser ini tidak mendukung notifikasi.'); return; }
    Notification.requestPermission().then(p => { e.target.textContent = p === 'granted' ? 'Notifikasi browser aktif' : 'Izin notifikasi ditolak'; });
  };
  if ('Notification' in window && Notification.permission === 'granted') $('izinBrowser').textContent = 'Notifikasi browser aktif';

  // ---------- darurat ----------
  let sudahDiakui = store.get('rt_sos_diakui', []);
  function gambarDarurat(list) {
    const aktif = list.filter(x => !sudahDiakui.includes(x.id));
    const L = $('sosLayer');
    if (!aktif.length) { L.classList.add('d-none'); return; }
    const x = aktif[0], tampilBaru = L.classList.contains('d-none') || L.dataset.id != x.id;
    L.dataset.id = x.id;
    $('sosIsi').innerHTML = `<div class="small text-uppercase fw-bold" style="letter-spacing:.1em">Sinyal darurat</div>
      <h2 class="display-6 fw-bold my-1">${esc(x.type)}</h2>
      <div class="fs-5 fw-semibold">${esc(x.name)}</div><div>${esc(x.location || 'Lokasi tidak tercatat')}</div>
      ${x.message ? `<div class="mt-2 fst-italic">"${esc(x.message)}"</div>` : ''}
      <div class="small mt-2 opacity-75">${jam(x.at)}${aktif.length > 1 ? ' &middot; ' + (aktif.length - 1) + ' sinyal lain' : ''}</div>
      <div class="d-flex flex-wrap gap-2 justify-content-center mt-3">
        <button type="button" class="btn btn-light fw-semibold" data-ack="${x.id}">Saya mengerti</button>
        ${RT.board ? `<button type="button" class="btn btn-outline-light fw-semibold" data-aman="${x.id}">Tandai situasi aman</button>` : ''}
        <a class="btn btn-outline-light" href="tel:112">Telepon 112</a></div>`;
    L.classList.remove('d-none');
    if (tampilBaru) {
      bunyi(true);
      if ('Notification' in window && Notification.permission === 'granted') {
        try { new Notification('DARURAT: ' + x.type, { body: (x.name || '') + ' - ' + (x.location || ''), requireInteraction: true, tag: 'sos-' + x.id }); } catch (e) {}
      }
    }
  }
  $('sosLayer').addEventListener('click', e => {
    const a = e.target.closest('[data-ack]'), m = e.target.closest('[data-aman]');
    if (a) { sudahDiakui.push(+a.dataset.ack); store.set('rt_sos_diakui', sudahDiakui.slice(-50)); poll(); }
    if (m && confirm('Tandai keadaan darurat ini sudah ditangani? Semua warga akan diberi tahu.')) {
      api(RT.url.resolve + '/' + m.dataset.aman + '/aman', {}).then(poll).catch(err => alert(err.message));
    }
  });
  $('kirimDarurat').onclick = async () => {
    const pilih = document.querySelector('input[name=jd]:checked');
    const jenis = pilih ? pilih.value : 'Lainnya';
    if (!confirm('Kirim sinyal darurat "' + jenis + '" ke semua pengurus dan warga?')) return;
    const b = $('kirimDarurat'); b.disabled = true;
    try {
      const r = await api(RT.url.emergency, { type: jenis, message: $('pesanDarurat').value });
      bootstrap.Modal.getOrCreateInstance($('mPanic')).hide(); $('pesanDarurat').value = ''; alert(r.message); poll();
    } catch (err) { alert(err.message); } finally { b.disabled = false; }
  };

  // ---------- polling ----------
  let timer;
  async function poll() {
    clearTimeout(timer);
    try {
      const d = await api(RT.url.poll);
      gambarNotif(d); gambarDarurat(d.emergencies);
      const c = $('chatBadge'); c.classList.toggle('d-none', !d.chat || chatTerbuka); c.textContent = d.chat > 9 ? '9+' : d.chat;
      pertama = false;
    } catch (e) {}
    timer = setTimeout(poll, document.hidden ? 45000 : 15000);
  }
  document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });

  // ---------- chat ----------
  let chatTerbuka = false, ruangAktif = null, idTerakhir = 0, chatTimer;
  const pop = $('chatPop'), body = $('chatBody');
  $('btnChat').onclick = () => { chatTerbuka = !chatTerbuka; pop.classList.toggle('d-none', !chatTerbuka); if (chatTerbuka) bukaDaftar(); else tutupRuang(); };
  $('chatClose').onclick = () => { chatTerbuka = false; pop.classList.add('d-none'); tutupRuang(); poll(); };
  $('chatBack').onclick = () => { tutupRuang(); bukaDaftar(); };
  function tutupRuang() { ruangAktif = null; clearTimeout(chatTimer); }
  async function bukaDaftar() {
    $('chatTitle').textContent = 'Chat'; $('chatBack').classList.add('d-none'); $('chatForm').classList.add('d-none');
    body.innerHTML = '<div class="p-3 small text-secondary">Memuat...</div>';
    try {
      const d = await api(RT.url.chat);
      const ada = new Set(d.rooms.map(x => x.room));
      body.innerHTML = '<div class="chat-sec">Percakapan</div>' + d.rooms.map(x =>
        `<button type="button" class="chat-row" data-room="${esc(x.room)}" data-name="${esc(x.name)}"><span class="ava">${x.group ? '<i class="bi bi-people-fill"></i>' : esc(x.name.slice(0, 1))}</span><span class="flex-grow-1 text-start"><span class="d-block fw-semibold">${esc(x.name)}</span><span class="d-block small text-secondary text-truncate">${esc(x.last) || 'Belum ada pesan'}</span></span>${x.unread ? `<span class="badge text-bg-danger">${x.unread}</span>` : ''}</button>`).join('')
        + '<div class="chat-sec">' + (RT.board ? 'Pesan pribadi' : 'Hubungi pengurus') + '</div>'
        + (d.contacts.filter(x => !ada.has(x.room)).map(x =>
          `<button type="button" class="chat-row" data-room="${esc(x.room)}" data-name="${esc(x.name)}"><span class="ava">${esc(x.name.slice(0, 1))}</span><span class="flex-grow-1 text-start"><span class="d-block fw-semibold">${esc(x.name)}</span><span class="d-block small text-secondary">${esc(x.label)}</span></span></button>`).join('')
          || '<div class="px-3 pb-3 small text-secondary">Tidak ada kontak lain.</div>');
    } catch (e) { body.innerHTML = `<div class="p-3 small text-danger">${esc(e.message)}</div>`; }
  }
  body.addEventListener('click', e => { const r = e.target.closest('.chat-row'); if (r) bukaRuang(r.dataset.room, r.dataset.name); });
  function bukaRuang(room, nama) {
    ruangAktif = room; idTerakhir = 0;
    $('chatTitle').textContent = nama; $('chatBack').classList.remove('d-none'); $('chatForm').classList.remove('d-none');
    body.innerHTML = ''; ambil(); $('chatInput').focus();
  }
  async function ambil() {
    clearTimeout(chatTimer);
    if (!ruangAktif) return;
    const room = ruangAktif, grup = !room.startsWith('dm-');
    try {
      const d = await api(RT.url.chat + '/' + encodeURIComponent(room) + '?setelah=' + idTerakhir);
      if (room !== ruangAktif) return;
      if (!idTerakhir) body.innerHTML = d.messages.length ? '' : '<div class="p-3 small text-secondary text-center">Belum ada pesan. Mulai percakapan.</div>';
      d.messages.forEach(m => {
        body.insertAdjacentHTML('beforeend', `<div class="msg ${m.mine ? 'saya' : ''}">${!m.mine && grup ? `<div class="small fw-semibold">${esc(m.name)}</div>` : ''}<div>${esc(m.body).replace(/\n/g, '<br>')}</div><div class="t">${jam(m.at)}</div></div>`);
        idTerakhir = Math.max(idTerakhir, m.id);
      });
      if (d.messages.length) body.scrollTop = body.scrollHeight;
    } catch (e) {}
    chatTimer = setTimeout(ambil, document.hidden ? 15000 : 4000);
  }
  $('chatForm').onsubmit = async e => {
    e.preventDefault();
    const i = $('chatInput'), isi = i.value.trim();
    if (!isi || !ruangAktif) return;
    if (!idTerakhir && body.querySelector('.text-center')) body.innerHTML = '';
    i.value = '';
    try { await api(RT.url.chat + '/' + encodeURIComponent(ruangAktif), { body: isi }); ambil(); } catch (err) { i.value = isi; alert(err.message); }
  };

  poll();
})();
