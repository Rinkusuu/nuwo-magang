// Sidebar backoffice + cek sesi admin. Halaman menandai menu aktif lewat <body data-menu="...">
const MENU_ADMIN = [
  ['SELEKSI', [
    ['dasbor', 'Dasbor', null],
    ['periode', 'Periode dan Kuota', 'admin_periode.html'],
    ['pelamar', 'Daftar Pelamar', 'dashboard_admin.html'],
    ['verifikasi', 'Verifikasi Administrasi', 'dashboard_admin.html?status=menunggu'],
    ['wawancara', 'Wawancara dan Penilaian', 'admin_wawancara.html'],
    ['rubrik', 'Rubrik Penilaian', 'admin_rubrik.html'],
    ['hasil', 'Hasil dan Pengumuman', 'admin_hasil.html'],
  ]],
  ['PELAKSANAAN', [
    ['penempatan', 'Penempatan dan Pembimbing', null],
    ['logbook', 'Logbook dan Evaluasi', null],
    ['sertifikat', 'Sertifikat', null],
  ]],
  ['SISTEM', [
    ['pengguna', 'Pengguna dan Hak Akses', null],
    ['log', 'Log Aktivitas', null],
  ]],
];

function renderSidebar() {
  const active = document.body.dataset.menu;
  const side = document.getElementById('sidebar');
  side.innerHTML = `
    <div class="brand"><b>Backoffice Magang</b><span>KPwBI Provinsi Lampung</span></div>
    ${MENU_ADMIN.map(([group, items]) => `
      <div class="group">${group}</div>
      ${items.map(([key, label, href]) => href
        ? `<a href="${href}" class="${key === active ? 'active' : ''}"${key === active ? ' aria-current="page"' : ''}>${label}</a>`
        : `<span class="soon" title="Belum tersedia">${label}<small>Segera</small></span>`
      ).join('')}
    `).join('')}
    <div class="me"><b id="adminNama">…</b><span id="adminEmail"></span><br><button type="button" id="logoutBtn">Keluar</button></div>
  `;
  document.getElementById('logoutBtn').addEventListener('click', () => logout('login_admin.html'));

  const menuBtn = document.getElementById('menuBtn');
  if (menuBtn) {
    menuBtn.addEventListener('click', e => { e.stopPropagation(); side.classList.toggle('open'); });
    document.addEventListener('click', e => { if (!side.contains(e.target)) side.classList.remove('open'); });
  }
}

async function requireAdmin() {
  renderSidebar();
  const data = await api('backend/api/auth/admin_me.php');
  if (!data.success) { window.location.href = 'login_admin.html'; return null; }
  document.getElementById('adminNama').textContent = data.admin.nama;
  document.getElementById('adminEmail').textContent = data.admin.email;
  return data.admin;
}

// Kategori nilai wawancara sesuai ketentuan rubrik
function kategoriNilai(nilai, skorSikap) {
  if (nilai === null || nilai === undefined) return null;
  if (skorSikap === 1) return 'tidak';
  if (nilai >= 4.2) return 'sangat';
  if (nilai >= 3.4) return 'direkomendasikan';
  if (nilai >= 2.6) return 'cadangan';
  return 'tidak';
}
