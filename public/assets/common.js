const BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

const LABEL = {
  admin: {
    menunggu: ['Menunggu', 'blue'],
    memenuhi: ['Memenuhi', 'green'],
    perbaikan: ['Perlu perbaikan', 'amber'],
    tidak_memenuhi: ['Tidak memenuhi', 'red'],
  },
  berkas: {
    sesuai: ['Sesuai', 'green'],
    perbaikan: ['Perlu perbaikan', 'amber'],
    tidak_sesuai: ['Tidak sesuai', 'red'],
  },
  seleksi: {
    lolos: ['Lolos ke wawancara', 'green'],
    tidak_lolos: ['Tidak lolos ke wawancara', 'red'],
  },
  hasil: {
    diterima: ['Diterima', 'green'],
    tidak_diterima: ['Tidak diterima', 'red'],
  },
  kesediaan: {
    bersedia: ['Bersedia', 'green'],
    tidak_bersedia: ['Mengundurkan diri', 'red'],
  },
  rekomendasi: {
    sangat: 'Sangat direkomendasikan',
    direkomendasikan: 'Direkomendasikan',
    tidak: 'Tidak direkomendasikan',
  },
};

const BERKAS = [
  { jenis: 'cv', verif: 'Curriculum Vitae', judul: 'Data Diri Lengkap (Curriculum Vitae)', singkat: 'Curriculum Vitae',
    ket: 'Riwayat pendidikan, organisasi, pengalaman, dan keterampilan.',
    periksa: 'data diri, riwayat pendidikan, pengalaman organisasi, keterampilan.' },
  { jenis: 'motivation', verif: 'Motivation Letter', judul: 'Motivation Letter', singkat: 'Motivation Letter',
    ket: 'Maksud dan tujuan PKL, harapan atau target yang akan dicapai, serta bidang pekerjaan yang diminati.',
    periksa: 'maksud dan tujuan PKL · harapan atau target · bidang pekerjaan yang diminati.' },
  { jenis: 'pengantar', verif: 'Surat Pengantar Perguruan Tinggi', judul: 'Surat Pengantar dari Perguruan Tinggi', singkat: 'Surat Pengantar',
    ket: 'Mencakup Nama, NPM, Fakultas/Program Studi, Semester, serta durasi dan periode PKL (2 bulan).',
    periksa: 'nama · NPM · fakultas/program studi · semester · durasi dan periode PKL (2 bulan) · tanda tangan dan cap.' },
  { jenis: 'transkrip', verif: 'Transkrip Nilai Terakhir', judul: 'Transkrip Nilai Terakhir', singkat: 'Transkrip Nilai',
    ket: 'Transkrip nilai sampai semester terakhir yang telah ditempuh.',
    periksa: 'terbaru, identitas sesuai, terbaca jelas.' },
  { jenis: 'ktm', verif: 'Kartu Tanda Mahasiswa (KTM)', judul: 'Kartu Tanda Mahasiswa (KTM)', singkat: 'KTM',
    ket: 'Pindaian KTM dalam satu berkas PDF.',
    periksa: 'nama dan NPM sesuai data pendaftaran; terbaca jelas.' },
];

let csrfToken = null;
async function ambilCsrf(paksa = false) {
  if (csrfToken && !paksa) return csrfToken;
  try {
    const r = await fetch('api/auth/csrf.php', { credentials: 'same-origin' });
    csrfToken = (await r.json()).token || null;
  } catch (e) { csrfToken = null; }
  return csrfToken;
}

async function api(url, options = {}, ulang = true) {
  const opts = { credentials: 'same-origin', ...options };
  if (opts.json !== undefined) {
    opts.method = opts.method || 'POST';
    opts.headers = { 'Content-Type': 'application/json', ...(opts.headers || {}) };
    opts.body = JSON.stringify(opts.json);
    delete opts.json;
  }
  const method = (opts.method || 'GET').toUpperCase();
  if (method !== 'GET' && method !== 'HEAD') {
    opts.headers = { ...(opts.headers || {}), 'X-CSRF-Token': await ambilCsrf() || '' };
  }
  let res;
  try {
    res = await fetch(url, opts);
  } catch (e) {
    return { success: false, message: 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda lalu coba lagi.', status: 0 };
  }
  if (res.status === 419 && ulang) {
    await ambilCsrf(true);
    return api(url, options, false);
  }
  let data;
  try { data = await res.json(); }
  catch (e) { data = { success: false, message: 'Respons server tidak valid.' }; }
  if (typeof data.csrf === 'string') csrfToken = data.csrf;
  data.status = res.status;
  return data;
}

function esc(v) {
  return String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function fmtTanggal(s) {
  if (!s) return '';
  const [y, m, d] = String(s).slice(0, 10).split('-').map(Number);
  return `${d} ${BULAN[m - 1]} ${y}`;
}

function fmtAngka(n, digits = 2) {
  if (n === null || n === undefined || n === '') return '–';
  return Number(n).toFixed(digits).replace('.', ',');
}

const IKON_TAMU = '<svg viewBox="0 0 40 40" aria-hidden="true"><rect width="40" height="40" fill="#DFE5E7"/><circle cx="20" cy="15.5" r="7" fill="#fff"/><path d="M5 40c1.2-9.5 7.2-14.5 15-14.5S33.8 30.5 35 40z" fill="#fff"/></svg>';

function avatarHtml(url) {
  return url ? `<img src="${esc(url)}" alt="">` : IKON_TAMU;
}

function pasangAvatarSaya(el, user) {
  let foto = user.foto;
  const url = () => foto ? 'api/auth/foto_profil.php?v=' + encodeURIComponent(foto) : null;
  el.innerHTML = avatarHtml(url());
  el.onclick = () => {
    const d = document.createElement('dialog');
    d.className = 'dialog foto-profil';
    d.setAttribute('aria-label', 'Foto profil');
    const gambar = () => {
      d.innerHTML = `<div class="isi"><span class="avatar xl">${avatarHtml(url())}</span><h2>Foto profil</h2>
          <p id="fotoInfo">JPG, PNG, atau WebP. Foto dipotong persegi.</p></div>
        <div class="aksi"><button type="button" class="btn btn-primary" value="unggah">${foto ? 'Ganti foto' : 'Unggah foto'}</button>
          ${foto ? '<button type="button" class="btn btn-outline" value="hapus">Hapus foto</button>' : ''}
          <button type="button" class="btn btn-outline" value="tutup">Tutup</button></div>
        <input type="file" accept="image/jpeg,image/png,image/webp" hidden>`;
      const input = d.querySelector('input');
      d.querySelector('[value="tutup"]').onclick = () => d.close();
      d.querySelector('[value="unggah"]').onclick = () => input.click();
      input.onchange = async () => {
        if (!input.files[0]) return;
        const fd = new FormData();
        fd.append('foto', input.files[0]);
        const res = await api('api/auth/foto_profil.php', { method: 'POST', body: fd });
        selesai(res);
      };
      const hapus = d.querySelector('[value="hapus"]');
      if (hapus) hapus.onclick = async () => selesai(await api('api/auth/foto_profil.php', { json: { hapus: true } }));
    };
    const selesai = (res) => {
      // toast tertutup backdrop dialog, jadi galat ditulis di dalam dialog
      if (!res.success) { const p = d.querySelector('#fotoInfo'); p.textContent = res.message; p.className = 'galat'; return; }
      foto = res.foto;
      el.innerHTML = avatarHtml(url());
      d.close();
      toast(res.message);
    };
    gambar();
    document.body.appendChild(d);
    d.addEventListener('close', () => d.remove());
    d.addEventListener('click', e => { if (e.target === d) d.close(); });
    d.showModal();
  };
}

function pill(map, key, fallback = '') {
  const item = map[key];
  if (!item) return fallback;
  return `<span class="pill ${item[1]}">${esc(item[0])}</span>`;
}

let toastTimer;
function toast(message, isError = false) {
  let el = document.getElementById('toast');
  if (!el) {
    el = document.createElement('div');
    el.id = 'toast';
    el.className = 'toast';
    el.setAttribute('role', 'status');
    document.body.appendChild(el);
  }
  el.innerHTML = (window.ikon ? ikon(isError ? 'peringatan' : 'centang', 16) : '') + `<span>${esc(message)}</span>`;
  el.classList.toggle('error', isError);
  el.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('show'), 3200);
}

async function logout(target) {
  await api('api/auth/logout.php', { method: 'POST' });
  window.location.href = target;
}

function todayISO() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function skorSandi(v) {
  if (!v) return 0;
  if (v.length < 8) return 1;
  let ragam = 0;
  if (/[a-z]/.test(v)) ragam++;
  if (/[A-Z]/.test(v)) ragam++;
  if (/\d/.test(v)) ragam++;
  if (/[^A-Za-z0-9]/.test(v)) ragam++;
  let skor = ragam >= 3 ? 3 : 2;
  if (v.length >= 12 && ragam >= 3) skor = 4;
  if (/^(.)\1+$/.test(v) || /^(12345678|password|katasandi|qwerty)/i.test(v)) skor = 1;
  return skor;
}
function pasangKekuatanSandi(input, wadah) {
  const LABEL = ['', 'Terlalu lemah', 'Cukup', 'Kuat', 'Sangat kuat'];
  wadah.innerHTML = '<span class="ruas"><i></i><i></i><i></i><i></i></span><b></b>';
  const perbarui = () => {
    const s = skorSandi(input.value);
    wadah.classList.toggle('aktif', s > 0);
    wadah.dataset.skor = s;
    wadah.querySelector('b').textContent = s === 1 && input.value.length < 8 ? `${input.value.length}/8 karakter` : LABEL[s];
  };
  input.addEventListener('input', perbarui);
  perbarui();
}
function pasangCekEmail(input, field) {
  const pola = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
  const cek = () => {
    const ok = pola.test(input.value.trim());
    field.classList.toggle('valid', ok);
    if (ok) field.classList.remove('has-error');
  };
  input.addEventListener('input', cek);
  input.addEventListener('blur', () => { if (input.value.trim()) field.classList.toggle('has-error', !pola.test(input.value.trim())); });
}

function kerangkaTabel(kolom, baris = 5) {
  const lebar = [62, 48, 70, 40, 55, 35, 28];
  return Array.from({ length: baris }, (_, r) => `<tr class="kerangka-baris" aria-hidden="true">${
    Array.from({ length: kolom }, (_, c) => `<td><span class="kerangka" style="width:${lebar[(c + r) % lebar.length]}%"></span></td>`).join('')
  }</tr>`).join('');
}
function kerangkaHalaman() {
  const garis = (lebar) => lebar.map(w => `<span class="kerangka" style="width:${w}%"></span>`).join('');
  return `<div class="kerangka-halaman" aria-busy="true" aria-label="Memuat">
    <div>${garis([18])}<div style="height:12px"></div><span class="kerangka" style="width:42%;height:1.8em"></span></div>
    <div class="dua">
      <div class="kartu">${garis([30, 90, 75, 85, 60, 80, 50])}</div>
      <div class="kartu">${garis([45, 70, 60, 80, 55])}</div>
    </div>
  </div>`;
}

function konfirmasi({ judul, pesan, ya = 'Lanjutkan', batal = 'Batal', bahaya = false }) {
  return new Promise(selesai => {
    const d = document.createElement('dialog');
    d.className = 'dialog' + (bahaya ? ' bahaya' : '');
    d.setAttribute('aria-labelledby', 'dialogJudul');
    d.innerHTML = `<div class="isi">
        <div class="ikon-dialog">${window.ikon ? ikon(bahaya ? 'peringatan' : 'centang', 20) : ''}</div>
        <h2 id="dialogJudul">${esc(judul)}</h2><p>${esc(pesan)}</p></div>
      <div class="aksi"><button type="button" class="btn btn-outline" value="batal">${esc(batal)}</button>
        <button type="button" class="btn btn-primary" value="ya">${esc(ya)}</button></div>`;
    document.body.appendChild(d);
    const tutup = (hasil) => { d.close(); d.remove(); selesai(hasil); };
    d.querySelector('[value="batal"]').addEventListener('click', () => tutup(false));
    d.querySelector('[value="ya"]').addEventListener('click', () => tutup(true));
    d.addEventListener('cancel', e => { e.preventDefault(); tutup(false); });
    d.addEventListener('click', e => { if (e.target === d) tutup(false); });
    d.showModal();
    // Tindakan berbahaya: fokus awal di "Batal" supaya Enter tidak langsung menghapus
    d.querySelector(bahaya ? '[value="batal"]' : '[value="ya"]').focus();
  });
}

function pratinjauPdf(url, judul) {
  const seluler = window.matchMedia('(max-width: 900px), (pointer: coarse)').matches;
  if (seluler) { window.open(url, '_blank', 'noopener'); return; }
  const d = document.createElement('dialog');
  d.className = 'dialog lebar';
  d.setAttribute('aria-label', 'Pratinjau ' + judul);
  d.innerHTML = `<div class="kepala"><b>${esc(judul)}</b><div class="tindakan">
      <a class="btn btn-outline btn-sm" href="${esc(url)}" target="_blank" rel="noopener">Buka di tab baru</a>
      <button type="button" class="btn btn-outline btn-ikon" aria-label="Tutup pratinjau">${window.ikon ? ikon('tutup', 16) : '×'}</button></div></div>
    <iframe src="${esc(url)}" title="${esc(judul)}"></iframe>`;
  document.body.appendChild(d);
  const tutup = () => { d.close(); d.remove(); };
  d.querySelector('.btn-ikon').addEventListener('click', tutup);
  d.addEventListener('close', () => d.remove());
  d.addEventListener('click', e => { if (e.target === d) tutup(); });
  d.showModal();
}
document.addEventListener('click', e => {
  const a = e.target.closest('a[data-pratinjau]');
  if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
  e.preventDefault();
  pratinjauPdf(a.getAttribute('href'), a.dataset.pratinjau || a.textContent.trim());
});

document.querySelectorAll('[data-kerangka]').forEach(el => { el.outerHTML = kerangkaHalaman(); });
document.querySelectorAll('[data-kerangka-tabel]').forEach(tb => {
  tb.innerHTML = kerangkaTabel(+tb.dataset.kerangkaTabel);
  tb.setAttribute('aria-busy', 'true');
});
