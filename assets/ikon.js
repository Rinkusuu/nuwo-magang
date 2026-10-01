/**
 * Set ikon bersama untuk seluruh halaman.
 *
 * Sebelumnya antarmuka memakai emoji sebagai ikon. Emoji punya tiga masalah:
 * bentuknya digambar sendiri oleh tiap sistem operasi sehingga tampilan tidak bisa
 * dipastikan, ukurannya mengikuti font emoji dan tidak menyatu dengan tipografi di
 * sekitarnya, dan warnanya tidak bisa diatur CSS sehingga keadaan hover/disabled
 * tidak pernah terlihat pada ikonnya.
 *
 * Semua ikon di bawah memakai stroke="currentColor" sehingga mewarisi warna
 * elemen induknya, dan memakai satu grid 24x24 dengan ketebalan garis yang sama
 * supaya terbaca sebagai satu keluarga.
 *
 * Pakai: ikon('lokasi')            -> ukuran bawaan 17px
 *        ikon('gedung', 22)        -> ukuran lain
 */
(function () {
  const JALUR = {
    /* identitas & orang */
    id:          '<rect x="2.8" y="5.2" width="18.4" height="13.6" rx="2.4"/><circle cx="8.6" cy="11.2" r="2.1"/><path d="M5.4 16.4c.5-1.5 1.7-2.3 3.2-2.3s2.7.8 3.2 2.3"/><path d="M14.8 10.2h4M14.8 13.6h4"/>',
    orang:       '<circle cx="12" cy="8.2" r="3.4"/><path d="M5.4 19.6c.7-3.3 3.4-5.1 6.6-5.1s5.9 1.8 6.6 5.1"/>',
    kelompok:    '<circle cx="9.2" cy="8.6" r="3"/><path d="M3.4 19.2c.6-2.9 3-4.5 5.8-4.5s5.2 1.6 5.8 4.5"/><path d="M16.4 6.1a3 3 0 0 1 0 5.9"/><path d="M17.6 14.9c2 .5 3.4 1.9 3.9 4.3"/>',
    umur:        '<circle cx="12" cy="12" r="8.6"/><path d="M12 7.2V12l3.2 1.9"/>',

    /* usaha & tempat */
    gedung:      '<path d="M4.4 20.4V5.6a1.4 1.4 0 0 1 1.4-1.4h8a1.4 1.4 0 0 1 1.4 1.4v14.8"/><path d="M15.2 9.6h3.4a1.4 1.4 0 0 1 1.4 1.4v9.4"/><path d="M2.8 20.4h18.4"/><path d="M7.6 8h4M7.6 11.6h4M7.6 15.2h4"/>',
    pabrik:      '<path d="M3.2 20.4V10.6l5.6 3.4V10.6l5.6 3.4V6.4h6.4v14H3.2Z"/><path d="M17.2 16.4h1.6"/>',
    rumah:       '<path d="M3.6 10.4 12 3.8l8.4 6.6v9a1.4 1.4 0 0 1-1.4 1.4H5a1.4 1.4 0 0 1-1.4-1.4v-9Z"/><path d="M9.4 20.8v-6.4h5.2v6.4"/>',
    lokasi:      '<path d="M19.2 10.6c0 5.2-7.2 10.6-7.2 10.6s-7.2-5.4-7.2-10.6a7.2 7.2 0 0 1 14.4 0Z"/><circle cx="12" cy="10.4" r="2.6"/>',

    /* dokumen & data */
    dokumen:     '<path d="M13.6 2.9H6.8a1.8 1.8 0 0 0-1.8 1.8v14.6a1.8 1.8 0 0 0 1.8 1.8h10.4a1.8 1.8 0 0 0 1.8-1.8V8.3l-5.4-5.4Z"/><path d="M13.6 2.9v5.4h5.4"/><path d="M8.6 13.4h6.8M8.6 16.8h6.8"/>',
    catatan:     '<path d="M17 3.4a2.3 2.3 0 0 1 3.3 3.3L10.6 16.4l-4.2 1.2 1.2-4.2L17 3.4Z"/><path d="M4.4 20.8h15.2"/>',
    kalender:    '<rect x="3.6" y="5.2" width="16.8" height="15.2" rx="2.2"/><path d="M3.6 10h16.8"/><path d="M8.2 3.2v3.6M15.8 3.2v3.6"/>',
    daftar:      '<rect x="5.2" y="4.4" width="13.6" height="16" rx="2"/><path d="M9.2 4.4V3.2h5.6v1.2"/><path d="M9 10.2h6M9 13.8h6M9 17.4h3.4"/>',
    sertifikat:  '<circle cx="12" cy="9.6" r="5.4"/><path d="M8.4 14.2 7.2 21l4.8-2.4L16.8 21l-1.2-6.8"/>',
    komoditas:   '<path d="M12 21.2V9.4"/><path d="M12 12.6c-3 0-4.6-1.9-4.6-4.9C10.4 7.7 12 9.6 12 12.6Z"/><path d="M12 12.6c3 0 4.6-1.9 4.6-4.9-3 0-4.6 1.9-4.6 4.9Z"/><path d="M12 8c0-2.3.9-4 2.2-4.9C15.1 4.4 14.4 6.6 12 8Z"/>',
    uang:        '<rect x="2.8" y="6.4" width="18.4" height="11.2" rx="2"/><circle cx="12" cy="12" r="2.8"/><path d="M6.4 10v4M17.6 10v4"/>',

    /* kontak */
    telepon:     '<path d="M21 16.9v2.6a1.8 1.8 0 0 1-2 1.8 17.6 17.6 0 0 1-7.7-2.7 17.3 17.3 0 0 1-5.3-5.3A17.6 17.6 0 0 1 3.3 5.5a1.8 1.8 0 0 1 1.8-2h2.6a1.8 1.8 0 0 1 1.8 1.5c.1.9.3 1.7.6 2.5a1.8 1.8 0 0 1-.4 1.9l-1.1 1.1a14 14 0 0 0 5.3 5.3l1.1-1.1a1.8 1.8 0 0 1 1.9-.4c.8.3 1.6.5 2.5.6a1.8 1.8 0 0 1 1.6 1.9Z"/>',
    obrolan:     '<path d="M20.6 11.6a7.6 7.6 0 0 1-8.2 7.6 8.6 8.6 0 0 1-2.8-.5L4 20.6l1.9-5.4a7.7 7.7 0 0 1-.5-2.8 7.6 7.6 0 0 1 7.6-8.2 7.6 7.6 0 0 1 7.6 7.4Z"/>',
    email:       '<rect x="2.8" y="5.2" width="18.4" height="13.6" rx="2.2"/><path d="m3.4 6.6 8.6 6 8.6-6"/>',
    instagram:   '<rect x="3.4" y="3.4" width="17.2" height="17.2" rx="4.6"/><circle cx="12" cy="12" r="3.9"/><path d="M17.1 6.9h.01"/>',
    web:         '<circle cx="12" cy="12" r="8.8"/><path d="M3.2 12h17.6"/><path d="M12 3.2a13.4 13.4 0 0 1 0 17.6 13.4 13.4 0 0 1 0-17.6Z"/>',
    youtube:     '<path d="M21.6 8.2a2.5 2.5 0 0 0-1.8-1.8C18.2 6 12 6 12 6s-6.2 0-7.8.4A2.5 2.5 0 0 0 2.4 8.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 3.8 2.5 2.5 0 0 0 1.8 1.8C5.8 18 12 18 12 18s6.2 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-3.8Z"/><path d="m10.2 15.1 5.2-3.1-5.2-3.1v6.2Z"/>',
    facebook:    '<path d="M15.4 3.6h-2.3a4.1 4.1 0 0 0-4.1 4.1v2.6H6.6v3.4H9v7.1h3.4v-7.1h2.6l.6-3.4h-3.2V7.7a1 1 0 0 1 1-1h2v-3.1Z"/>',
    faks:        '<path d="M6.6 9.4V3.8h10.8v5.6"/><rect x="3.4" y="9.4" width="17.2" height="7.2" rx="1.8"/><path d="M6.6 14.6h10.8v5.6H6.6v-5.6Z"/>',
    tautan:      '<path d="M10.2 13.8a3.8 3.8 0 0 0 5.7.4l2.8-2.8a3.8 3.8 0 0 0-5.4-5.4l-1.6 1.6"/><path d="M13.8 10.2a3.8 3.8 0 0 0-5.7-.4l-2.8 2.8a3.8 3.8 0 0 0 5.4 5.4l1.6-1.6"/>',

    /* manfaat portal */
    pelatihan:   '<path d="m2.6 8.4 9.4-4.6 9.4 4.6-9.4 4.6L2.6 8.4Z"/><path d="M6.6 10.4v4.8c0 1.7 2.4 3 5.4 3s5.4-1.3 5.4-3v-4.8"/><path d="M21.4 8.4v5.4"/>',
    pembiayaan:  '<rect x="2.8" y="5.4" width="18.4" height="13.2" rx="2.2"/><path d="M2.8 9.8h18.4"/><path d="M6.4 14.6h3.4"/>',
    promosi:     '<path d="M5.6 8h12.8l1.2 11.2a1.8 1.8 0 0 1-1.8 2H6.2a1.8 1.8 0 0 1-1.8-2L5.6 8Z"/><path d="M8.6 10.4V7a3.4 3.4 0 0 1 6.8 0v3.4"/>',
    pasar:       '<circle cx="12" cy="12" r="8.8"/><path d="M12 3.2c2.4 2.4 3.6 5.4 3.6 8.8s-1.2 6.4-3.6 8.8c-2.4-2.4-3.6-5.4-3.6-8.8S9.6 5.6 12 3.2Z"/><path d="M3.6 9.2h16.8M3.6 14.8h16.8"/>',
    digital:     '<rect x="3.2" y="4.6" width="17.6" height="11.6" rx="1.8"/><path d="M2 19.4h20"/>',

    /* keamanan */
    gembok:      '<rect x="4.6" y="10.4" width="14.8" height="10" rx="2.2"/><path d="M8 10.4V7.6a4 4 0 0 1 8 0v2.8"/>',
    akun:        '<circle cx="7.6" cy="12" r="3.9"/><path d="M11.5 12h9.1"/><path d="M17.6 12v3.2"/><path d="M20.6 12v2.4"/>',

    /* tindakan */
    lihat:       '<path d="M2.6 12S6.4 5.8 12 5.8 21.4 12 21.4 12 17.6 18.2 12 18.2 2.6 12 2.6 12Z"/><circle cx="12" cy="12" r="2.9"/>',
    ubah:        '<path d="M4 20.2h4.1L18.6 9.7a2.9 2.9 0 0 0-4.1-4.1L4 16.1v4.1Z"/><path d="M13.4 6.8l4.1 4.1"/>',
    hapus:       '<path d="M4.2 6.9h15.6"/><path d="M9.7 3.9h4.6"/><path d="M6.3 6.9l.8 12.3a2 2 0 0 0 2 1.9h5.8a2 2 0 0 0 2-1.9l.8-12.3"/><path d="M10.3 10.7v6.4M13.7 10.7v6.4"/>',
    tutup:       '<path d="M6.2 6.2 17.8 17.8M17.8 6.2 6.2 17.8"/>',
    centang:     '<path d="m4.8 12.6 4.8 4.8 9.6-10.8"/>',
    unduh:       '<path d="M12 3.6v12"/><path d="m7.2 10.8 4.8 4.8 4.8-4.8"/><path d="M4.2 20.4h15.6"/>',
    muatUlang:   '<path d="M20.4 12a8.4 8.4 0 1 1-2.5-6"/><path d="M20.6 4v5h-5"/>',
    cari:        '<circle cx="10.8" cy="10.8" r="6.8"/><path d="m15.8 15.8 4.6 4.6"/>',
    panahKiri:   '<path d="M19.4 12H4.6"/><path d="m10.8 5.8-6.2 6.2 6.2 6.2"/>',
    gambar:      '<rect x="3.4" y="4.6" width="17.2" height="14.8" rx="2.2"/><circle cx="8.8" cy="9.8" r="1.8"/><path d="m4.4 17.6 5-5 8.2 6.8"/>',

    /* status */
    menunggu:    '<circle cx="12" cy="12" r="8.6"/><path d="M12 7.2V12l3.2 1.9"/>',
    ditolak:     '<circle cx="12" cy="12" r="8.6"/><path d="M9.2 9.2l5.6 5.6M14.8 9.2l-5.6 5.6"/>',
    peringatan:  '<path d="M12 3.6 21.4 19.8H2.6L12 3.6Z"/><path d="M12 9.8v4.4"/><path d="M12 17.4h.01"/>',

    /* navigasi portal magang */
    grafik:      '<path d="M3.6 20.4h16.8"/><path d="M5.4 16.2 9.6 11l3.6 3 5.4-7.2"/><path d="M15 6.8h3.6v3.6"/>',
    menu:        '<path d="M4 6.6h16M4 12h16M4 17.4h16"/>',
    kembali:     '<path d="M19.2 12H4.8"/><path d="m10.8 6-6 6 6 6"/>',
    lanjut:      '<path d="M4.8 12h14.4"/><path d="m13.2 6 6 6-6 6"/>',
    keluar:      '<path d="M9.6 20.4H6a1.8 1.8 0 0 1-1.8-1.8V5.4A1.8 1.8 0 0 1 6 3.6h3.6"/><path d="M15.6 16.8 20.4 12l-4.8-4.8"/><path d="M20.4 12H9.6"/>',
    unggah:      '<path d="M12 15.6v-12"/><path d="m7.2 8.4 4.8-4.8 4.8 4.8"/><path d="M4.2 20.4h15.6"/>'
  };

  /*
    Untuk markup statis, tulis <span data-ikon="lokasi"></span> di HTML dan isinya
    diisi otomatis saat halaman dimuat. Untuk markup yang dibangun JavaScript,
    panggil ikon('lokasi') langsung di dalam template.
  */
  function pasangIkonStatis(akar) {
    (akar || document).querySelectorAll('[data-ikon]').forEach(function (el) {
      if (el.dataset.ikonTerpasang) return;
      const ukuran = el.dataset.ikonUkuran ? parseInt(el.dataset.ikonUkuran, 10) : 17;
      const svg = window.ikon(el.dataset.ikon, ukuran);
      if (svg) { el.innerHTML = svg; el.dataset.ikonTerpasang = '1'; }
    });
  }

  window.pasangIkonStatis = pasangIkonStatis;

  document.addEventListener('DOMContentLoaded', function () {
    pasangIkonStatis();

    /*
      Sebagian markup (isi modal, baris tabel, baris form berulang) disisipkan
      belakangan oleh JavaScript. Tanpa pengamat ini, span data-ikon di dalamnya
      akan tetap kosong karena pemasangan hanya berjalan sekali saat halaman dimuat.
      Pengamat hanya menyentuh elemen ber-atribut data-ikon, dan penanda
      data-ikon-terpasang mencegahnya berjalan berulang pada elemen yang sama.
    */
    new MutationObserver(function (daftar) {
      for (const rekaman of daftar) {
        for (const simpul of rekaman.addedNodes) {
          if (simpul.nodeType !== 1) continue;
          if (simpul.hasAttribute('data-ikon')) pasangIkonStatis(simpul.parentNode || document);
          else if (simpul.querySelector && simpul.querySelector('[data-ikon]')) pasangIkonStatis(simpul);
        }
      }
    }).observe(document.body, { childList: true, subtree: true });
  });

  window.ikon = function (nama, ukuran = 17) {
    const jalur = JALUR[nama];
    if (!jalur) return '';
    return `<svg width="${ukuran}" height="${ukuran}" viewBox="0 0 24 24" fill="none" `
      + `stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" `
      + `aria-hidden="true" focusable="false">${jalur}</svg>`;
  };
})();
