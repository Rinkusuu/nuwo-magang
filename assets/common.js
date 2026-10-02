// Helper bersama untuk seluruh halaman portal
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
  hasil: {
    diterima: ['Diterima', 'green'],
    cadangan: ['Cadangan', 'amber'],
    tidak_diterima: ['Tidak diterima', 'red'],
  },
  rekomendasi: {
    sangat: 'Sangat direkomendasikan',
    direkomendasikan: 'Direkomendasikan',
    cadangan: 'Dipertimbangkan (cadangan)',
    tidak: 'Tidak direkomendasikan',
  },
};

const BERKAS = [
  { jenis: 'cv', verif: 'Curriculum Vitae', judul: 'Data Diri Lengkap (Curriculum Vitae)', singkat: 'Curriculum Vitae',
    ket: 'Riwayat pendidikan, organisasi, pengalaman, dan keterampilan.',
    periksa: 'data diri, riwayat pendidikan, pengalaman organisasi, keterampilan.' },
  { jenis: 'motivation', verif: 'Motivation Letter', judul: 'Motivation Letter', singkat: 'Motivation Letter',
    ket: 'Maksud dan tujuan, bidang/sektor yang diminati, jangka waktu, serta harapan atau target yang akan dicapai.',
    periksa: 'maksud dan tujuan · bidang/sektor yang diminati · jangka waktu · harapan atau target.' },
  { jenis: 'pengantar', verif: 'Surat Pengantar Perguruan Tinggi', judul: 'Surat Pengantar dari Perguruan Tinggi', singkat: 'Surat Pengantar',
    ket: 'Mencakup Nama, NIM, Fakultas/Program Studi, dan Semester.',
    periksa: 'nama · NIM · fakultas/program studi · semester · tanda tangan dan cap.' },
  { jenis: 'transkrip', verif: 'Transkrip Nilai Terakhir', judul: 'Transkrip Nilai Terakhir', singkat: 'Transkrip Nilai',
    ket: 'Transkrip resmi sampai semester terakhir.',
    periksa: 'terbaru, identitas sesuai, terbaca jelas.' },
  { jenis: 'ktm', verif: 'Kartu Tanda Mahasiswa (KTM)', judul: 'Kartu Tanda Mahasiswa (KTM)', singkat: 'KTM',
    ket: 'Pindaian KTM dalam satu berkas PDF.',
    periksa: 'nama dan NIM sesuai data pendaftaran; terbaca jelas.' },
];

// Token CSRF sesi ini. Diambil sekali sebelum permintaan pertama yang mengubah data,
// lalu diperbarui dari respons login/daftar (sesi baru = token baru).
let csrfToken = null;
async function ambilCsrf(paksa = false) {
  if (csrfToken && !paksa) return csrfToken;
  try {
    const r = await fetch('backend/api/auth/csrf.php', { credentials: 'same-origin' });
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
  // Token kedaluwarsa (mis. sesi berakhir): ambil token baru lalu ulangi sekali
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

function initials(name) {
  return String(name || '').trim().split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase() || '–';
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
  // Ikon membedakan berhasil dan gagal selain lewat warna
  el.innerHTML = (window.ikon ? ikon(isError ? 'peringatan' : 'centang', 16) : '') + `<span>${esc(message)}</span>`;
  el.classList.toggle('error', isError);
  el.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('show'), 3200);
}

async function logout(target) {
  await api('backend/api/auth/logout.php', { method: 'POST' });
  window.location.href = target;
}

// Tanggal hari ini (zona waktu lokal) dalam format YYYY-MM-DD
function todayISO() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

// Indikator kekuatan kata sandi. Skor 1–4 dari panjang dan keragaman karakter;
// hanya sebagai panduan, syarat minimal tetap 8 karakter (diperiksa server).
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
// Tanda centang saat format email sudah benar (tanpa memeriksa apakah email terdaftar)
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

// ---------- Kerangka saat memuat ----------
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

// ---------- Dialog konfirmasi ----------
// Pengganti confirm() bawaan peramban: tampil sesuai gaya portal, bisa diberi
// penanda bahaya, dan menunggu jawaban lewat Promise.
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

// ---------- Pratinjau PDF ----------
// Di layar lebar berkas dibuka dalam dialog tanpa meninggalkan halaman. Di HP
// (peramban seluler umumnya tidak menampilkan PDF di dalam iframe) dibuka di tab baru.
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
// Tautan berkas cukup diberi atribut data-pratinjau
document.addEventListener('click', e => {
  const a = e.target.closest('a[data-pratinjau]');
  if (!a || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
  e.preventDefault();
  pratinjauPdf(a.getAttribute('href'), a.dataset.pratinjau || a.textContent.trim());
});

// Placeholder "Memuat…" bertanda data-kerangka langsung diganti kerangka halaman
document.querySelectorAll('[data-kerangka]').forEach(el => { el.outerHTML = kerangkaHalaman(); });
document.querySelectorAll('[data-kerangka-tabel]').forEach(tb => {
  tb.innerHTML = kerangkaTabel(+tb.dataset.kerangkaTabel);
  tb.setAttribute('aria-busy', 'true');
});
