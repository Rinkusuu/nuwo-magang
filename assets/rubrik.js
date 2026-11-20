// Bobot harus sama dengan BOBOT_ASPEK di backend/config.php.
const SKALA = { 5: 'Sangat baik', 4: 'Baik', 3: 'Cukup', 2: 'Kurang', 1: 'Sangat kurang' };

const ASPEK = [
  {
    nama: 'Kesesuaian Akademik', singkat: 'Akademik', bobot: 15,
    ket: 'Relevansi program studi, mata kuliah, dan capaian akademik dengan bidang yang dilamar.',
    ketRubrik: 'Relevansi prodi, mata kuliah, dan capaian akademik dengan bidang.',
    tanya: 'Mata kuliah apa yang paling relevan dengan bidang pilihan Anda, dan bagaimana Anda akan menerapkannya selama PKL?',
    d: {
      5: 'Sangat relevan dengan capaian akademik sangat baik; mampu mengaitkan ilmu dengan tugas bidang secara konkret.',
      4: 'Relevan dengan capaian akademik baik; mampu memberi contoh penerapan.',
      3: 'Cukup relevan; keterkaitan dijelaskan secara umum.',
      2: 'Kurang relevan; sulit mengaitkan ilmu dengan tugas di bidang.',
      1: 'Tidak relevan dan tidak mampu menjelaskan keterkaitan ilmu dengan bidang.',
    },
  },
  {
    nama: 'Motivasi dan Target PKL', singkat: 'Motivasi', bobot: 15,
    ket: 'Kejelasan maksud, tujuan, dan target; konsisten dengan motivation letter.',
    ketRubrik: 'Maksud, tujuan, dan target; konsisten dengan motivation letter.',
    tanya: 'Apa yang ingin Anda capai selama PKL, dan bagaimana Anda mengukur keberhasilannya?',
    d: {
      5: 'Tujuan dan target spesifik, terukur, dan konsisten dengan motivation letter.',
      4: 'Tujuan jelas dan konsisten; target cukup spesifik.',
      3: 'Tujuan bersifat umum; target belum terukur.',
      2: 'Motivasi lemah atau tidak konsisten dengan motivation letter.',
      1: 'Tidak dapat menjelaskan alasan dan tujuan PKL.',
    },
  },
  {
    nama: 'Pengetahuan tentang BI dan Bidang yang Diminati', singkat: 'Pengetahuan BI', bobot: 15,
    ket: 'Pemahaman tugas Bank Indonesia, peran kantor perwakilan, dan lingkup bidang pilihan.',
    ketRubrik: 'Tugas BI, peran kantor perwakilan, dan lingkup bidang pilihan.',
    tanya: 'Jelaskan tugas utama Bank Indonesia dan peran bidang yang Anda pilih di kantor perwakilan.',
    d: {
      5: 'Memahami tugas BI dan peran bidang pilihan secara tepat, termasuk isu terkini.',
      4: 'Memahami tugas BI dan gambaran umum bidang pilihan.',
      3: 'Memahami tugas BI secara umum; pengetahuan tentang bidang terbatas.',
      2: 'Pemahaman keliru pada beberapa hal pokok.',
      1: 'Tidak memiliki pengetahuan dasar tentang BI maupun bidang pilihan.',
    },
  },
  {
    nama: 'Kemampuan Komunikasi', singkat: 'Komunikasi', bobot: 15,
    ket: 'Kejelasan, keruntutan, dan ketepatan jawaban; dinilai sepanjang wawancara.',
    ketRubrik: 'Kejelasan, keruntutan, dan ketepatan jawaban.',
    tanya: 'Ceritakan secara singkat satu kegiatan atau proyek yang pernah Anda kerjakan dan hasilnya.',
    d: {
      5: 'Runtut, lugas, dan meyakinkan; menyimak dengan baik dan menjawab tepat sasaran.',
      4: 'Jelas dan runtut, sesekali kurang fokus.',
      3: 'Cukup jelas, namun memerlukan pertanyaan lanjutan.',
      2: 'Kurang runtut dan sulit dipahami.',
      1: 'Tidak mampu menyampaikan gagasan dengan jelas.',
    },
  },
  {
    nama: 'Analisis dan Pemecahan Masalah', singkat: 'Analisis', bobot: 15,
    ket: 'Kemampuan mengurai masalah, menggunakan data, dan menawarkan solusi.',
    ketRubrik: 'Mengurai masalah, menggunakan data, dan menawarkan solusi.',
    tanya: 'Studi kasus: harga cabai merah di Lampung naik tajam menjelang hari besar keagamaan. Faktor apa yang perlu dianalisis dan apa usulan Anda?',
    d: {
      5: 'Menguraikan masalah secara sistematis dan berbasis data; solusi realistis dan mempertimbangkan dampak.',
      4: 'Menguraikan masalah secara sistematis dengan solusi yang masuk akal.',
      3: 'Mengenali pokok masalah; solusi masih umum.',
      2: 'Analisis dangkal; solusi tidak berkaitan dengan masalah.',
      1: 'Tidak mampu menganalisis masalah.',
    },
  },
  {
    nama: 'Pengalaman dan Keterampilan Pendukung', singkat: 'Pengalaman', bobot: 10,
    ket: 'Pengalaman organisasi, kepanitiaan, atau proyek, serta keterampilan yang relevan (olah data, penulisan, desain, dan sebagainya).',
    ketRubrik: 'Organisasi, kepanitiaan, proyek, dan keterampilan relevan.',
    tanya: 'Apa peran Anda dalam organisasi atau proyek terakhir, dan keterampilan apa yang dapat Anda sumbangkan di bidang ini?',
    d: {
      5: 'Pengalaman relevan dengan peran dan hasil nyata; keterampilan dapat langsung dimanfaatkan di bidang.',
      4: 'Memiliki pengalaman relevan dengan peran yang jelas.',
      3: 'Memiliki pengalaman, namun relevansinya terbatas.',
      2: 'Pengalaman dan keterampilan minim.',
      1: 'Tidak memiliki pengalaman maupun keterampilan pendukung.',
    },
  },
  {
    nama: 'Sikap, Integritas, dan Profesionalisme', singkat: 'Sikap dan integritas', bobot: 15,
    ket: 'Kesantunan, ketepatan waktu, kejujuran, serta komitmen terhadap aturan dan kerahasiaan.',
    ketRubrik: 'Kesantunan, ketepatan waktu, kejujuran, serta komitmen terhadap aturan dan kerahasiaan.',
    tanya: 'Bagaimana Anda menyikapi data atau informasi yang bersifat rahasia di tempat PKL? (Lengkapi dengan pengamatan sikap selama wawancara.)',
    d: {
      5: 'Santun, tepat waktu, jujur, dan konsisten; menunjukkan komitmen kuat terhadap aturan dan kerahasiaan.',
      4: 'Santun dan profesional dengan komitmen yang baik.',
      3: 'Cukup baik; beberapa hal perlu dibina.',
      2: 'Kurang menunjukkan sikap profesional.',
      1: 'Menunjukkan indikasi ketidakjujuran atau ketidakdisiplinan (otomatis tidak direkomendasikan).',
    },
  },
];

const KETENTUAN_NILAI = '≥ 4,20 Sangat direkomendasikan · 3,00–4,19 Direkomendasikan · < 3,00 Tidak direkomendasikan.';
