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

async function api(url, options = {}) {
  const opts = { credentials: 'same-origin', ...options };
  if (opts.json !== undefined) {
    opts.method = opts.method || 'POST';
    opts.headers = { 'Content-Type': 'application/json', ...(opts.headers || {}) };
    opts.body = JSON.stringify(opts.json);
    delete opts.json;
  }
  let res;
  try {
    res = await fetch(url, opts);
  } catch (e) {
    return { success: false, message: 'Tidak dapat terhubung ke server. Pastikan Apache dan MySQL di XAMPP berjalan.', status: 0 };
  }
  let data;
  try { data = await res.json(); }
  catch (e) { data = { success: false, message: 'Respons server tidak valid.' }; }
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
  await api('backend/api/auth/logout.php');
  window.location.href = target;
}

// Tanggal hari ini (zona waktu lokal) dalam format YYYY-MM-DD
function todayISO() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}
