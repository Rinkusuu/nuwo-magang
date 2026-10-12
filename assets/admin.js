// Sidebar backoffice + cek sesi admin. Halaman menandai menu aktif lewat <body data-menu="...">
// Item: [kunci, label, tautan, peran yang boleh melihat]. Tanpa peran = khusus admin.
const MENU_ADMIN = [
  ['Seleksi', [
    ['dasbor', 'Dasbor', null],
    ['periode', 'Batch dan Jadwal', 'admin_periode.html'],
    ['pelamar', 'Daftar Pelamar', 'dashboard_admin.html'],
    ['verifikasi', 'Verifikasi Administrasi', 'dashboard_admin.html?status=menunggu'],
    ['wawancara', 'Seleksi dan Wawancara', 'admin_wawancara.html', ['admin', 'pewawancara']],
    ['rubrik', 'Rubrik Penilaian', 'admin_rubrik.html', ['admin', 'pewawancara']],
    ['hasil', 'Hasil dan Pengumuman', 'admin_hasil.html'],
  ]],
  ['Pelaksanaan', [
    ['penempatan', 'Penempatan dan Pembimbing', null],
    ['peserta', 'Peserta PKL', 'admin_peserta.html'],
    ['sertifikat', 'Sertifikat', null],
  ]],
  ['Sistem', [
    ['pengguna', 'Pengguna dan Hak Akses', 'admin_pengguna.html'],
    ['log', 'Log Aktivitas', null],
  ]],
];
const NAMA_PERAN = { admin: 'Admin', pewawancara: 'Pewawancara' };

function renderSidebar(admin) {
  const active = document.body.dataset.menu;
  const side = document.getElementById('sidebar');
  const boleh = (peranItem) => (peranItem || ['admin']).includes(admin.peran);
  // Pewawancara tidak melihat menu yang belum tersedia maupun menu khusus admin
  const grup = MENU_ADMIN.map(([nama, items]) => [nama, items.filter(([, , href, peran]) => href ? boleh(peran) : admin.peran === 'admin')])
    .filter(([, items]) => items.length);
  side.innerHTML = `
    <div class="brand"><span class="logo-mark"><img src="assets/bi-logo-bulat.webp" alt="Bank Indonesia" width="32" height="32"></span><span><b>Backoffice Magang</b><span>KPwBI Provinsi Lampung</span></span></div>
    ${grup.map(([group, items]) => `
      <div class="group">${group}</div>
      ${items.map(([key, label, href]) => (label = key === 'wawancara' && admin.peran === 'pewawancara' ? 'Daftar Wawancara' : label, href)
        ? `<a href="${href}" class="${key === active ? 'active' : ''}"${key === active ? ' aria-current="page"' : ''}>${label}</a>`
        : `<span class="soon" title="Belum tersedia">${label}<small>Segera</small></span>`
      ).join('')}
    `).join('')}
    <div class="me"><b>${esc(admin.nama)}</b><span>${esc(admin.email)}</span><span class="peran">${NAMA_PERAN[admin.peran] || ''}</span>
      <div class="tombol-me"><button type="button" id="sandiBtn">${ikon('gembok', 14)}Kata sandi</button><button type="button" id="logoutBtn">${ikon('keluar', 14)}Keluar</button></div></div>
  `;
  document.getElementById('logoutBtn').addEventListener('click', () => logout('login_admin.html'));
  document.getElementById('sandiBtn').addEventListener('click', bukaGantiSandi);

  // Delegasi: tombol Menu (HP) tetap berfungsi walau isi halaman digambar ulang setelah sidebar dipasang
  document.addEventListener('click', e => {
    if (e.target.closest('#menuBtn')) { side.classList.toggle('open'); return; }
    if (!side.contains(e.target)) side.classList.remove('open');
  });
}

// Cek sesi dan peran. Halaman yang juga terbuka untuk pewawancara memanggil
// requireAdmin(['admin', 'pewawancara']); pewawancara yang membuka halaman khusus admin
// dialihkan ke halaman wawancara. Server tetap memeriksa peran di setiap permintaan.
async function requireAdmin(izin = ['admin']) {
  const data = await api('backend/api/auth/admin_me.php');
  if (!data.success) { window.location.href = 'login_admin.html'; return null; }
  if (!izin.includes(data.admin.peran)) { window.location.href = 'admin_wawancara.html'; return null; }
  renderSidebar(data.admin);
  return data.admin;
}

// Ganti kata sandi akun sendiri
function bukaGantiSandi() {
  const d = document.createElement('dialog');
  d.className = 'dialog';
  d.setAttribute('aria-labelledby', 'judulSandi');
  d.innerHTML = `<form class="isi" novalidate>
      <h2 id="judulSandi">Ganti kata sandi</h2>
      <p style="margin-bottom:14px;">Minimal 12 karakter.</p>
      <div class="field" style="margin-bottom:12px;"><label for="sandiLama">Kata sandi lama</label><input class="input" type="password" id="sandiLama" autocomplete="current-password"></div>
      <div class="field" style="margin-bottom:12px;"><label for="sandiBaru">Kata sandi baru</label><input class="input" type="password" id="sandiBaru" autocomplete="new-password"><div class="kekuatan" id="kekuatanSandi"></div></div>
      <div class="field"><label for="sandiUlang">Ulangi kata sandi baru</label><input class="input" type="password" id="sandiUlang" autocomplete="new-password"></div>
    </form>
    <div class="aksi"><button type="button" class="btn btn-outline" value="batal">Batal</button><button type="button" class="btn btn-primary" value="simpan">Simpan</button></div>`;
  document.body.appendChild(d);
  const tutup = () => { d.close(); d.remove(); };
  pasangKekuatanSandi(d.querySelector('#sandiBaru'), d.querySelector('#kekuatanSandi'));
  d.querySelector('[value="batal"]').addEventListener('click', tutup);
  d.addEventListener('cancel', e => { e.preventDefault(); tutup(); });
  d.querySelector('[value="simpan"]').addEventListener('click', async e => {
    const baru = d.querySelector('#sandiBaru').value;
    if (baru.length < 12) { toast('Kata sandi baru minimal 12 karakter.', true); return; }
    if (baru !== d.querySelector('#sandiUlang').value) { toast('Ulangan kata sandi tidak sama.', true); return; }
    e.target.disabled = true;
    const res = await api('backend/api/auth/admin_sandi.php', { json: { lama: d.querySelector('#sandiLama').value, baru } });
    e.target.disabled = false;
    toast(res.message || (res.success ? 'Tersimpan.' : 'Gagal menyimpan.'), !res.success);
    if (res.success) tutup();
  });
  d.showModal();
  d.querySelector('#sandiLama').focus();
}

// Kategori nilai wawancara sesuai ketentuan rubrik
function kategoriNilai(nilai, skorSikap) {
  if (nilai === null || nilai === undefined) return null;
  if (skorSikap === 1) return 'tidak';
  if (nilai >= 4.2) return 'sangat';
  if (nilai >= 3) return 'direkomendasikan';
  return 'tidak';
}
