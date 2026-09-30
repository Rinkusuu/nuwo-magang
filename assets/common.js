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
  { jenis: 'pendukung', verif: 'Dokumen Pendukung', judul: 'Dokumen Pendukung', singkat: 'KTP, NPWP, Buku Tabungan',
    ket: 'KTP, NPWP, dan Buku Tabungan.',
    periksa: 'KTP · NPWP · buku tabungan; nama sesuai dan terbaca jelas.' },
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
  el.textContent = message;
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
