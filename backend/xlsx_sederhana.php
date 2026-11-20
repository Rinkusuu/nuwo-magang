<?php

class XlsxSederhana {
    private string $namaLembar;
    private array $sel = [];
    private array $gabungan = [];
    private array $lebarKolom = [];
    private array $fonts = [];
    private array $fills = [];
    private array $borders = [];
    private array $xfs = [];
    private string $hurufBawaan;
    private float $ukuranBawaan;

    public function __construct(string $namaLembar = 'Sheet1', string $huruf = 'Times New Roman', float $ukuran = 11) {
        $this->namaLembar = mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', '', $namaLembar), 0, 31) ?: 'Sheet1';
        $this->hurufBawaan = $huruf;
        $this->ukuranBawaan = $ukuran;
        // Indeks 0 harus gaya polos; isian 0 dan 1 wajib "none" dan "gray125"
        $this->idFont(['huruf' => $huruf, 'ukuran' => $ukuran, 'tebal' => false]);
        $this->fills = ['<fill><patternFill patternType="none"/></fill>' => 0, '<fill><patternFill patternType="gray125"/></fill>' => 1];
        $this->idBorder([]);
        $this->idGaya([]);
    }

    public function lebar(array $lebar): void {
        foreach ($lebar as $kolom => $w) $this->lebarKolom[self::indeksKolom($kolom)] = (float) $w;
    }

    public function sel(string $ref, $nilai, array $gaya = []): void {
        [$k, $b] = self::urai($ref);
        $this->sel[$b][$k] = [$nilai, $this->idGaya($gaya)];
    }

    public function gabung(string $rentang): void {
        $this->gabungan[] = $rentang;
    }

    public function kotak(string $rentang, $nilai, array $gaya = [], array $garis = []): void {
        [$awal, $akhir] = array_pad(explode(':', $rentang), 2, null);
        $akhir ??= $awal;
        [$k1, $b1] = self::urai($awal);
        [$k2, $b2] = self::urai($akhir);
        for ($b = $b1; $b <= $b2; $b++) {
            for ($k = $k1; $k <= $k2; $k++) {
                $g = [];
                if ($k === $k1 && isset($garis['l'])) $g['l'] = $garis['l'];
                if ($k === $k2 && isset($garis['r'])) $g['r'] = $garis['r'];
                if ($b === $b1 && isset($garis['t'])) $g['t'] = $garis['t'];
                if ($b === $b2 && isset($garis['b'])) $g['b'] = $garis['b'];
                $this->sel[$b][$k] = [$k === $k1 && $b === $b1 ? $nilai : null, $this->idGaya(['garis' => $g] + $gaya)];
            }
        }
        if ($awal !== $akhir) $this->gabung("$awal:$akhir");
    }

    public function simpan(string $path): void {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Tidak dapat membuat berkas xlsx.');
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::esc($this->namaLembar) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', $this->xmlGaya());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xmlLembar());
        $zip->close();
    }

    private function xmlLembar(): string {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0" zoomScale="90" zoomScaleNormal="90"/></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>';
        if ($this->lebarKolom) {
            ksort($this->lebarKolom);
            $x .= '<cols>';
            foreach ($this->lebarKolom as $k => $w) $x .= '<col min="' . $k . '" max="' . $k . '" width="' . $w . '" customWidth="1"/>';
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        ksort($this->sel);
        foreach ($this->sel as $b => $kolom) {
            ksort($kolom);
            $x .= '<row r="' . $b . '">';
            foreach ($kolom as $k => [$nilai, $s]) {
                $ref = self::hurufKolom($k) . $b;
                $attr = ' r="' . $ref . '"' . ($s ? ' s="' . $s . '"' : '');
                if ($nilai === null || $nilai === '') $x .= '<c' . $attr . '/>';
                elseif (is_int($nilai) || is_float($nilai)) $x .= '<c' . $attr . '><v>' . $nilai . '</v></c>';
                else $x .= '<c' . $attr . ' t="inlineStr"><is><t xml:space="preserve">' . self::esc((string) $nilai) . '</t></is></c>';
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ($this->gabungan) {
            $x .= '<mergeCells count="' . count($this->gabungan) . '">';
            foreach ($this->gabungan as $g) $x .= '<mergeCell ref="' . $g . '"/>';
            $x .= '</mergeCells>';
        }
        $x .= '<pageMargins left="0.5" right="0.5" top="0.6" bottom="0.6" header="0.3" footer="0.3"/>'
            . '<pageSetup paperSize="9" orientation="landscape"/>'
            . '</worksheet>';
        return $x;
    }

    private function xmlGaya(): string {
        $daftar = fn(array $peta) => implode('', array_keys($peta));
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="' . count($this->fonts) . '">' . $daftar($this->fonts) . '</fonts>'
            . '<fills count="' . count($this->fills) . '">' . $daftar($this->fills) . '</fills>'
            . '<borders count="' . count($this->borders) . '">' . $daftar($this->borders) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($this->xfs) . '">' . $daftar($this->xfs) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function idGaya(array $g): int {
        $font = $this->idFont(['huruf' => $g['huruf'] ?? $this->hurufBawaan, 'ukuran' => $g['ukuran'] ?? $this->ukuranBawaan, 'tebal' => !empty($g['tebal'])]);
        $fill = isset($g['isi']) ? $this->idFill($g['isi']) : 0;
        $border = $this->idBorder($g['garis'] ?? []);
        $rata = '';
        if (!empty($g['rata'])) $rata .= ' horizontal="' . $g['rata'] . '"';
        if (!empty($g['tegak'])) $rata .= ' vertical="' . $g['tegak'] . '"';
        if (!empty($g['bungkus'])) $rata .= ' wrapText="1"';
        $xf = '<xf numFmtId="0" fontId="' . $font . '" fillId="' . $fill . '" borderId="' . $border . '" xfId="0"'
            . ($font ? ' applyFont="1"' : '') . ($fill ? ' applyFill="1"' : '') . ($border ? ' applyBorder="1"' : '')
            . ($rata ? ' applyAlignment="1"><alignment' . $rata . '/></xf>' : '/>');
        return $this->xfs[$xf] ??= count($this->xfs);
    }

    private function idFont(array $f): int {
        $xml = '<font>' . ($f['tebal'] ? '<b/>' : '') . '<sz val="' . $f['ukuran'] . '"/><color rgb="FF000000"/><name val="' . self::esc($f['huruf']) . '"/></font>';
        return $this->fonts[$xml] ??= count($this->fonts);
    }

    private function idFill(string $warna): int {
        $rgb = 'FF' . strtoupper(ltrim($warna, '#'));
        $xml = '<fill><patternFill patternType="solid"><fgColor rgb="' . $rgb . '"/><bgColor rgb="' . $rgb . '"/></patternFill></fill>';
        return $this->fills[$xml] ??= count($this->fills);
    }

    private function idBorder(array $g): int {
        $xml = '<border>';
        foreach (['l' => 'left', 'r' => 'right', 't' => 'top', 'b' => 'bottom'] as $k => $tag) {
            $xml .= isset($g[$k]) ? '<' . $tag . ' style="' . $g[$k] . '"><color rgb="FF000000"/></' . $tag . '>' : '<' . $tag . '/>';
        }
        $xml .= '<diagonal/></border>';
        return $this->borders[$xml] ??= count($this->borders);
    }

    private static function urai(string $ref): array {
        if (!preg_match('/^([A-Z]+)(\d+)$/', $ref, $m)) throw new InvalidArgumentException("Alamat sel tidak valid: $ref");
        return [self::indeksKolom($m[1]), (int) $m[2]];
    }

    private static function indeksKolom(string $huruf): int {
        $n = 0;
        foreach (str_split($huruf) as $c) $n = $n * 26 + (ord($c) - 64);
        return $n;
    }

    private static function hurufKolom(int $n): string {
        $s = '';
        while ($n > 0) { $m = ($n - 1) % 26; $s = chr(65 + $m) . $s; $n = intdiv($n - 1, 26); }
        return $s;
    }

    private static function esc(string $s): string {
        // Buang karakter kontrol yang tidak sah di XML
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $s);
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
