<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Penulis XLSX sederhana tanpa dependensi eksternal.
 * Dipakai untuk ekspor absensi dan laporan kehadiran guru.
 */
class XlsxExport
{
    /**
     * Bangun respon unduhan file .xlsx.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>  $options
     */
    public static function download(string $filename, array $headers, array $rows, array $options = [])
    {
        $binary = self::build($headers, $rows, $options);

        return response($binary, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<string, mixed>  $options
     */
    public static function build(array $headers, array $rows, array $options = []): string
    {
        $title = (string) ($options['title'] ?? '');
        $subtitle = (string) ($options['subtitle'] ?? '');
        $sheetName = self::sheetName((string) ($options['sheet'] ?? 'Data'));
        $total = $options['total'] ?? null;
        $widths = is_array($options['widths'] ?? null) ? $options['widths'] : [];

        $headers = array_values(array_map(static fn ($header) => (string) $header, $headers));
        if ($headers === []) {
            throw new RuntimeException('Header ekspor tidak boleh kosong.');
        }

        $rows = array_values(array_map(
            static fn ($row) => array_values(is_array($row) ? $row : [$row]),
            $rows
        ));

        $columnCount = count($headers);
        $lastColumn = self::columnName($columnCount);
        $headerRowNumber = ($title !== '' ? 1 : 0) + ($subtitle !== '' ? 1 : 0) + 1;
        $firstDataRow = $headerRowNumber + 1;
        $lastDataRow = $headerRowNumber + count($rows);
        $totalRowNumber = $total !== null ? $lastDataRow + 1 : null;
        $lastRowNumber = $totalRowNumber ?? max($lastDataRow, $headerRowNumber);

        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="A1:'.$lastColumn.$lastRowNumber.'"/>'
            .'<sheetViews><sheetView workbookViewId="0">'
            .'<pane ySplit="'.$headerRowNumber.'" topLeftCell="A'.$firstDataRow.'" activePane="bottomLeft" state="frozen"/>'
            .'<selection pane="bottomLeft" activeCell="A'.$firstDataRow.'" sqref="A'.$firstDataRow.'"/>'
            .'</sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="18"/>';

        $sheet .= self::columnXml($headers, $rows, $widths, $columnCount);

        $merges = [];
        $sheet .= '<sheetData>';

        $currentRow = 1;
        if ($title !== '') {
            $sheet .= '<row r="'.$currentRow.'" ht="26" customHeight="1">'
                .self::cell('A'.$currentRow, $title, 2)
                .'</row>';
            $merges[] = 'A'.$currentRow.':'.$lastColumn.$currentRow;
            $currentRow++;
        }
        if ($subtitle !== '') {
            $sheet .= '<row r="'.$currentRow.'" ht="18" customHeight="1">'
                .self::cell('A'.$currentRow, $subtitle, 3)
                .'</row>';
            $merges[] = 'A'.$currentRow.':'.$lastColumn.$currentRow;
            $currentRow++;
        }

        $sheet .= '<row r="'.$currentRow.'" ht="24" customHeight="1">';
        foreach ($headers as $index => $header) {
            $sheet .= self::cell(self::columnName($index + 1).$currentRow, $header, 1);
        }
        $sheet .= '</row>';
        $currentRow++;

        foreach ($rows as $rowIndex => $row) {
            $style = $rowIndex % 2 === 1 ? 5 : 4;
            $sheet .= '<row r="'.$currentRow.'" ht="19" customHeight="1">';
            foreach ($headers as $index => $_) {
                $sheet .= self::cell(
                    self::columnName($index + 1).$currentRow,
                    $row[$index] ?? '',
                    $style
                );
            }
            $sheet .= '</row>';
            $currentRow++;
        }

        if (is_array($total)) {
            $sheet .= '<row r="'.$currentRow.'" ht="22" customHeight="1">';
            foreach ($headers as $index => $_) {
                $sheet .= self::cell(
                    self::columnName($index + 1).$currentRow,
                    $total[$index] ?? '',
                    6
                );
            }
            $sheet .= '</row>';
        }

        $sheet .= '</sheetData>';

        if ($merges !== []) {
            $sheet .= '<mergeCells count="'.count($merges).'">';
            foreach ($merges as $merge) {
                $sheet .= '<mergeCell ref="'.$merge.'"/>';
            }
            $sheet .= '</mergeCells>';
        }

        $filterLastRow = $lastDataRow >= $firstDataRow ? $lastDataRow : $headerRowNumber;
        $sheet .= '<autoFilter ref="A'.$headerRowNumber.':'.$lastColumn.$filterLastRow.'"/>';
        $sheet .= '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>';
        $sheet .= '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>';
        $sheet .= '</worksheet>';

        return self::archive($sheetName, $title, $sheet);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  array<int, mixed>  $widths
     */
    private static function columnXml(array $headers, array $rows, array $widths, int $columnCount): string
    {
        $xml = '<cols>';
        for ($index = 0; $index < $columnCount; $index++) {
            if (isset($widths[$index])) {
                $width = (float) $widths[$index];
            } else {
                $max = mb_strlen((string) ($headers[$index] ?? ''));
                foreach ($rows as $row) {
                    $length = mb_strlen((string) ($row[$index] ?? ''));
                    if ($length > $max) {
                        $max = $length;
                    }
                    if ($max >= 48) {
                        break;
                    }
                }
                $width = max(12, min(48, $max + 2));
            }

            $xml .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.round($width, 2).'" customWidth="1"/>';
        }

        return $xml.'</cols>';
    }

    private static function cell(string $reference, mixed $value, int $style): string
    {
        if (is_int($value) || is_float($value)) {
            return '<c r="'.$reference.'" s="'.$style.'"><v>'.$value.'</v></c>';
        }

        $text = self::escape((string) ($value ?? ''));

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>';
    }

    private static function archive(string $sheetName, string $title, string $sheetXml): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sigap-xlsx-');
        if ($path === false) {
            throw new RuntimeException('Tidak dapat membuat berkas ekspor sementara.');
        }

        $archive = new ZipArchive;
        if ($archive->open($path, ZipArchive::OVERWRITE) !== true) {
            @unlink($path);
            throw new RuntimeException('Tidak dapat membuat berkas XLSX.');
        }

        $now = gmdate('Y-m-d\TH:i:s\Z');
        $title = $title !== '' ? $title : $sheetName;

        try {
            $archive->addFromString('[Content_Types].xml', self::contentTypes());
            $archive->addFromString('_rels/.rels', self::rootRelationships());
            $archive->addFromString('docProps/core.xml', self::coreProperties($title, $now));
            $archive->addFromString('docProps/app.xml', self::appProperties());
            $archive->addFromString('xl/workbook.xml', self::workbook($sheetName));
            $archive->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationships());
            $archive->addFromString('xl/styles.xml', self::styles());
            $archive->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        } finally {
            $archive->close();
        }

        $binary = file_get_contents($path);
        @unlink($path);

        if ($binary === false) {
            throw new RuntimeException('Berkas XLSX tidak dapat dibaca.');
        }

        return $binary;
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'</Types>';
    }

    private static function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    private static function coreProperties(string $title, string $now): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" '
            .'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.self::escape($title).'</dc:title>'
            .'<dc:creator>SIAKAD SMA Pencawan</dc:creator>'
            .'<cp:lastModifiedBy>SIAKAD SMA Pencawan</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private static function appProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" '
            .'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Microsoft Excel</Application>'
            .'<DocSecurity>0</DocSecurity>'
            .'<ScaleCrop>false</ScaleCrop>'
            .'<Company>SMA Pencawan</Company>'
            .'</Properties>';
    }

    private static function workbook(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.self::escape($sheetName).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="5">'
            .'<font><sz val="11"/><color rgb="FF0F172A"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="14"/><color rgb="FF1E3A8A"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><i/><sz val="10"/><color rgb="FF64748B"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FF1E3A8A"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="6">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1E3A8A"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFEFF6FF"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFDBEAFE"/><bgColor indexed="64"/></patternFill></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            .'<border>'
            .'<left style="thin"><color rgb="FFCBD5E1"/></left>'
            .'<right style="thin"><color rgb="FFCBD5E1"/></right>'
            .'<top style="thin"><color rgb="FFCBD5E1"/></top>'
            .'<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="7">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="0" fillId="5" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            .'<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="left" vertical="center" wrapText="1"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'</styleSheet>';
    }

    private static function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name === '' ? 'A' : $name;
    }

    private static function sheetName(string $name): string
    {
        $name = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name) ?? 'Data';
        $name = trim($name);
        $name = $name === '' ? 'Data' : $name;

        return mb_substr($name, 0, 31);
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
