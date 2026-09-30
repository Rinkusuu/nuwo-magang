<?php
// Pengiriman email notifikasi.
//
// Email dikirim lewat SMTP yang diatur di config.local.php. Selama SMTP_HOST
// kosong (mis. di laptop pengembang), email tidak dikirim melainkan dicatat di
// backend/logs/email.log supaya isinya tetap bisa diperiksa.
//
// Kegagalan kirim tidak pernah menggagalkan tindakan pengguna: pendaftaran tetap
// terkirim dan keputusan admin tetap tersimpan; kegagalannya dicatat di log server.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

require_once __DIR__ . '/vendor/autoload.php';

function e_html(?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/**
 * @param string     $judul     judul besar di badan email
 * @param string[]   $paragraf  paragraf HTML (nilai dinamis wajib sudah di-escape dengan e_html)
 * @param array|null $rincian   pasangan [label, nilai] yang ditampilkan sebagai tabel ringkas (teks biasa)
 * @param array|null $tombol    [label, url]
 * @param string[]   $penutup   paragraf HTML setelah tabel rincian
 */
function kirim_email(string $ke, string $nama, string $subjek, string $judul, array $paragraf,
                     ?array $rincian = null, ?array $tombol = null, array $penutup = []): bool {
    if (!filter_var($ke, FILTER_VALIDATE_EMAIL)) return false;
    [$html, $teks] = susun_email($nama, $judul, $paragraf, $rincian, $tombol, $penutup);
    $smtp = $GLOBALS['SMTP'];

    if ($smtp['host'] === '') {
        $dir = __DIR__ . '/logs';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        file_put_contents($dir . '/email.log',
            sprintf("==== %s\nKe: %s <%s>\nSubjek: %s\n\n%s\n\n", date('c'), $nama, $ke, $subjek, $teks),
            FILE_APPEND | LOCK_EX);
        return false;
    }

    $m = new PHPMailer(true);
    try {
        $m->isSMTP();
        $m->Host = $smtp['host'];
        $m->Port = $smtp['port'];
        $m->SMTPAuth = $smtp['user'] !== '';
        $m->Username = $smtp['user'];
        $m->Password = $smtp['pass'];
        if ($smtp['secure'] === 'none') {
            $m->SMTPSecure = '';
            $m->SMTPAutoTLS = false;
        } else {
            $m->SMTPSecure = $smtp['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        }
        $m->Timeout = 10;
        $m->CharSet = PHPMailer::CHARSET_UTF8;
        $m->setFrom($smtp['from'] ?: $smtp['user'], $smtp['nama']);
        $m->addAddress($ke, $nama);
        $m->Subject = $subjek;
        $m->isHTML(true);
        $m->Body = $html;
        $m->AltBody = $teks;
        $m->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('[magang] Email ke ' . $ke . ' gagal: ' . $m->ErrorInfo);
        return false;
    }
}

function susun_email(string $nama, string $judul, array $paragraf, ?array $rincian, ?array $tombol, array $penutup = []): array {
    $logo = APP_URL ? '<img src="' . e_html(APP_URL . '/assets/bi-logo.png') . '" width="180" height="33" alt="Bank Indonesia" style="display:block;border:0;">' : '<b style="font-size:16px;color:#0A2A4A;">Bank Indonesia</b>';
    $paragraf = array_values(array_filter($paragraf, fn($p) => $p !== ''));
    $isi = '';
    foreach ($paragraf as $p) $isi .= '<p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#132238;">' . $p . '</p>';
    if ($rincian) {
        $isi .= '<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:6px 0 18px;background:#F1F6FB;border:1px solid #E3EEF8;border-radius:10px;">';
        foreach ($rincian as [$label, $nilai]) {
            $isi .= '<tr><td style="padding:10px 16px;font-size:13px;color:#46566B;width:38%;vertical-align:top;">' . e_html($label)
                . '</td><td style="padding:10px 16px;font-size:14px;color:#132238;font-weight:bold;">' . e_html($nilai) . '</td></tr>';
        }
        $isi .= '</table>';
    }
    foreach ($penutup as $p) $isi .= '<p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#132238;">' . $p . '</p>';
    if ($tombol) {
        $isi .= '<p style="margin:8px 0 18px;"><a href="' . e_html($tombol[1]) . '" style="display:inline-block;background:#0057A3;color:#ffffff;text-decoration:none;font-weight:bold;font-size:14px;padding:12px 22px;border-radius:8px;">'
            . e_html($tombol[0]) . '</a></p>';
    }

    $html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width"></head>'
        . '<body style="margin:0;padding:0;background:#F5F8FC;font-family:Arial,Helvetica,sans-serif;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F5F8FC;padding:24px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #DEE7F1;border-radius:14px;">'
        . '<tr><td style="padding:22px 28px;border-bottom:1px solid #DEE7F1;">' . $logo . '</td></tr>'
        . '<tr><td style="padding:28px 28px 12px;">'
        . '<p style="margin:0 0 6px;font-size:13px;color:#0057A3;font-weight:bold;">Program Magang Mahasiswa</p>'
        . '<h1 style="margin:0 0 18px;font-family:Georgia,serif;font-size:24px;line-height:1.25;color:#132238;font-weight:normal;">' . e_html($judul) . '</h1>'
        . '<p style="margin:0 0 14px;font-size:15px;color:#132238;">Yth. ' . e_html($nama) . ',</p>'
        . $isi
        . '</td></tr>'
        . '<tr><td style="padding:18px 28px 24px;border-top:1px solid #DEE7F1;font-size:12px;line-height:1.5;color:#6F7F93;">'
        . 'Email ini dikirim otomatis oleh Portal Magang Mahasiswa, Kantor Perwakilan Bank Indonesia Provinsi Lampung. Mohon tidak membalas email ini.'
        . '</td></tr></table></td></tr></table></body></html>';

    $teks = "Yth. $nama,\n\n" . $judul . "\n\n";
    foreach ($paragraf as $p) $teks .= trim(html_entity_decode(strip_tags(str_replace('<br>', "\n", $p)), ENT_QUOTES, 'UTF-8')) . "\n\n";
    if ($rincian) foreach ($rincian as [$label, $nilai]) $teks .= "$label: $nilai\n";
    foreach ($penutup as $p) $teks .= "\n" . trim(html_entity_decode(strip_tags($p), ENT_QUOTES, 'UTF-8')) . "\n";
    if ($tombol) $teks .= "\n{$tombol[0]}: {$tombol[1]}\n";
    $teks .= "\n--\nPortal Magang Mahasiswa\nKantor Perwakilan Bank Indonesia Provinsi Lampung\n(email otomatis, mohon tidak dibalas)";
    return [$html, $teks];
}

// Tautan ke halaman portal; tanpa APP_URL tautan dihilangkan dari email
function tautan_portal(string $halaman): ?string {
    return APP_URL ? APP_URL . '/' . ltrim($halaman, '/') : null;
}

function tombol_portal(string $label, string $halaman): ?array {
    $url = tautan_portal($halaman);
    return $url ? [$label, $url] : null;
}

function tanggal_id(?string $ymd): string {
    if (!$ymd) return '-';
    $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    [$y, $m, $d] = array_map('intval', explode('-', substr($ymd, 0, 10)));
    return "$d {$bulan[$m - 1]} $y";
}

// ---------- Notifikasi seleksi ----------

function email_pendaftaran_terkirim(array $app, bool $perbaikan): void {
    kirim_email($app['email'], $app['nama'],
        $perbaikan ? 'Perbaikan berkas diterima · ' . $app['no_pendaftaran'] : 'Pendaftaran magang diterima · ' . $app['no_pendaftaran'],
        $perbaikan ? 'Perbaikan berkas Anda sudah kami terima' : 'Pendaftaran Anda sudah kami terima',
        [$perbaikan
            ? 'Berkas perbaikan Anda sudah masuk dan akan diverifikasi ulang oleh tim seleksi.'
            : 'Terima kasih telah mendaftar Program Magang Mahasiswa. Pendaftaran Anda akan diverifikasi oleh tim seleksi.'],
        [['No. pendaftaran', $app['no_pendaftaran']], ['Nama', $app['nama']], ['Perguruan tinggi', (string) $app['universitas']]],
        tombol_portal('Lihat status pendaftaran', 'status_pengajuan.html'),
        ['Perkembangan seleksi dapat dipantau kapan saja di halaman Status Pendaftaran.']);
}

function email_hasil_verifikasi(array $app, string $hasil, string $catatan, ?string $batas): void {
    if ($hasil === 'memenuhi') {
        kirim_email($app['email'], $app['nama'], 'Lolos verifikasi administrasi · ' . $app['no_pendaftaran'],
            'Anda lolos verifikasi administrasi',
            ['Seluruh syarat dan berkas administrasi Anda dinyatakan memenuhi. Tahap berikutnya adalah wawancara.',
             'Jadwal wawancara akan dikirim melalui email dan ditampilkan di halaman Status Pendaftaran.'],
            [['No. pendaftaran', $app['no_pendaftaran']]],
            tombol_portal('Lihat status pendaftaran', 'status_pengajuan.html'));
    } elseif ($hasil === 'perbaikan') {
        kirim_email($app['email'], $app['nama'], 'Berkas perlu diperbaiki · ' . $app['no_pendaftaran'],
            'Sebagian berkas Anda perlu diperbaiki',
            ['Tim seleksi meminta Anda memperbaiki sebagian berkas. Catatan dari tim:',
             '<i>' . nl2br(e_html($catatan)) . '</i>',
             'Unggah ulang berkas yang ditandai di formulir pendaftaran, lalu kirim kembali sebelum batas waktu.'],
            [['No. pendaftaran', $app['no_pendaftaran']], ['Batas perbaikan', tanggal_id($batas)]],
            tombol_portal('Perbaiki berkas', 'formulir.html#s4'));
    } else {
        kirim_email($app['email'], $app['nama'], 'Hasil verifikasi administrasi · ' . $app['no_pendaftaran'],
            'Hasil verifikasi administrasi',
            ['Mohon maaf, pendaftaran Anda belum memenuhi syarat administrasi Program Magang Mahasiswa periode ini.',
             $catatan !== '' ? 'Catatan dari tim seleksi: <i>' . nl2br(e_html($catatan)) . '</i>' : '',
             'Terima kasih atas minat Anda terhadap Program Magang Mahasiswa KPwBI Provinsi Lampung.'],
            [['No. pendaftaran', $app['no_pendaftaran']]], null);
    }
}

function email_jadwal_wawancara(array $app, string $tanggal, string $jam, string $lokasi, bool $ubah): void {
    kirim_email($app['email'], $app['nama'],
        ($ubah ? 'Perubahan jadwal wawancara · ' : 'Jadwal wawancara · ') . $app['no_pendaftaran'],
        $ubah ? 'Jadwal wawancara Anda berubah' : 'Undangan wawancara',
        [$ubah ? 'Jadwal wawancara Anda telah diperbarui. Berikut jadwal terbaru:'
               : 'Anda diundang mengikuti wawancara seleksi Program Magang Mahasiswa pada:'],
        [['Tanggal', tanggal_id($tanggal)], ['Waktu', substr($jam, 0, 5) . ' WIB'], ['Lokasi', $lokasi], ['No. pendaftaran', $app['no_pendaftaran']]],
        tombol_portal('Lihat status pendaftaran', 'status_pengajuan.html'),
        ['Mohon hadir 15 menit sebelum jadwal dan membawa kartu identitas.']);
}

function email_hasil_akhir(array $app, string $hasil, ?string $penempatan): void {
    $isi = [
        'diterima' => ['Selamat, Anda diterima', ['Selamat, Anda dinyatakan <b>diterima</b> sebagai peserta Program Magang Mahasiswa KPwBI Provinsi Lampung.',
                        'Informasi administrasi lanjutan akan disampaikan oleh tim kami.']],
        'cadangan' => ['Anda masuk daftar cadangan', ['Anda masuk <b>daftar cadangan</b> Program Magang Mahasiswa. Kami akan menghubungi Anda apabila kuota tersedia.']],
        'tidak_diterima' => ['Hasil seleksi Program Magang Mahasiswa', ['Terima kasih telah mengikuti seluruh rangkaian seleksi. Mohon maaf, Anda belum dapat kami terima pada periode ini.',
                        'Kami menghargai waktu dan usaha Anda, dan berharap dapat bertemu kembali pada kesempatan berikutnya.']],
    ][$hasil] ?? null;
    if (!$isi) return;
    $rincian = [['No. pendaftaran', $app['no_pendaftaran']]];
    if ($hasil === 'diterima' && $penempatan) $rincian[] = ['Bidang penempatan', $penempatan];
    kirim_email($app['email'], $app['nama'], 'Pengumuman hasil seleksi · ' . $app['no_pendaftaran'], $isi[0], $isi[1], $rincian,
        tombol_portal('Lihat status pendaftaran', 'status_pengajuan.html'));
}

function email_atur_ulang_sandi(string $email, string $nama, string $url): void {
    kirim_email($email, $nama, 'Atur ulang kata sandi Portal Magang', 'Atur ulang kata sandi',
        ['Kami menerima permintaan untuk mengatur ulang kata sandi akun Portal Magang Mahasiswa Anda.',
         'Tautan di bawah berlaku selama 60 menit dan hanya dapat dipakai sekali.',
         'Jika Anda tidak meminta ini, abaikan email ini; kata sandi Anda tidak berubah.'],
        null, ['Atur kata sandi baru', $url]);
}
