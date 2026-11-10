<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use ZipArchive;

class GuruImportReader
{
    private const HEADERS = ['nip', 'nama', 'mapel', 'status', 'username', 'email', 'password'];

    private const OPTIONAL_HEADERS = ['telepon'];

    private const MAX_ROWS = 100;

    public function read(UploadedFile $file): array
    {
        $rows = $this->readTable($file);

        if ($rows === []) {
            throw new InvalidArgumentException('File tidak berisi data guru.');
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), array_shift($rows));
        if (count($headers) !== count(array_unique($headers))) {
            throw new InvalidArgumentException('Nama kolom pada template tidak boleh duplikat.');
        }

        $missingHeaders = array_diff(self::HEADERS, $headers);
        if ($missingHeaders !== []) {
            throw new InvalidArgumentException('Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'.');
        }

        $result = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($headers as $index => $header) {
                if (in_array($header, [...self::HEADERS, ...self::OPTIONAL_HEADERS], true)) {
                    $values[$header] = trim((string) ($row[$index] ?? ''));
                }
            }

            if (count(array_filter($values, fn ($value) => $value !== '')) === 0) {
                continue;
            }

            $result[] = $values;
            if (count($result) > self::MAX_ROWS) {
                throw new InvalidArgumentException('Maksimal 100 data guru dalam satu file.');
            }
        }

        return $result;
    }

    public function readTable(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv' => $this->readCsv($file->getRealPath()),
            'xlsx' => $this->readXlsx($file->getRealPath()),
            default => throw new InvalidArgumentException('Format file harus .xlsx atau .csv.'),
        };
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new InvalidArgumentException('File CSV tidak dapat dibaca.');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return [];
            }

            $delimiter = ',';
            $maxColumns = 0;
            foreach ([',', ';', "\t"] as $candidate) {
                $columns = count(str_getcsv($firstLine, $candidate, '"', '\\'));
                if ($columns > $maxColumns) {
                    $delimiter = $candidate;
                    $maxColumns = $columns;
                }
            }
            rewind($handle);

            $rows = [];
            while (($row = fgetcsv($handle, null, $delimiter, '"', '\\')) !== false) {
                if ($rows === [] && isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
                }
                if ($rows !== [] && count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0) {
                    continue;
                }
                $rows[] = $row;
                if (count($rows) > self::MAX_ROWS + 1) {
                    throw new InvalidArgumentException('Maksimal 100 data guru dalam satu file.');
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function readXlsx(string $path): array
    {
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            throw new InvalidArgumentException('File Excel tidak valid atau tidak dapat dibuka.');
        }

        try {
            $workbook = $this->loadXml($this->readEntry($archive, 'xl/workbook.xml'));
            $relationships = $this->loadXml($this->readEntry($archive, 'xl/_rels/workbook.xml.rels'));
            $sheet = $workbook->getElementsByTagNameNS(
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                'sheet'
            )->item(0);

            if (! $sheet instanceof DOMElement) {
                throw new InvalidArgumentException('Workbook Excel tidak memiliki sheet.');
            }

            $relationshipId = $sheet->getAttributeNS(
                'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
                'id'
            );
            $target = null;
            foreach ($relationships->getElementsByTagNameNS(
                'http://schemas.openxmlformats.org/package/2006/relationships',
                'Relationship'
            ) as $relationship) {
                if ($relationship instanceof DOMElement && $relationship->getAttribute('Id') === $relationshipId) {
                    $target = $relationship->getAttribute('Target');
                    break;
                }
            }

            if ($target === null || str_contains($target, '..')) {
                throw new InvalidArgumentException('Sheet pertama pada file Excel tidak dapat dibaca.');
            }
            $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
            $worksheet = $this->loadXml($this->readEntry($archive, $sheetPath));
            $sharedStrings = [];
            if ($archive->locateName('xl/sharedStrings.xml') !== false) {
                $strings = $this->loadXml($this->readEntry($archive, 'xl/sharedStrings.xml'));
                foreach ($strings->getElementsByTagNameNS(
                    'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                    'si'
                ) as $item) {
                    $text = '';
                    foreach ($item->getElementsByTagNameNS(
                        'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                        't'
                    ) as $part) {
                        $text .= $part->textContent;
                    }
                    $sharedStrings[] = $text;
                }
            }

            $rows = [];
            foreach ($worksheet->getElementsByTagNameNS(
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                'row'
            ) as $rowElement) {
                $row = [];
                foreach ($rowElement->getElementsByTagNameNS(
                    'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                    'c'
                ) as $cell) {
                    if (! $cell instanceof DOMElement
                        || ! preg_match('/^([A-Z]+)/', $cell->getAttribute('r'), $columnMatch)) {
                        continue;
                    }

                    $column = 0;
                    foreach (str_split($columnMatch[1]) as $letter) {
                        $column = $column * 26 + ord($letter) - 64;
                    }
                    $column--;
                    $valueNode = $cell->getElementsByTagNameNS(
                        'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                        'v'
                    )->item(0);
                    $value = $valueNode?->textContent ?? '';

                    if ($cell->getAttribute('t') === 's') {
                        $value = $sharedStrings[(int) $value] ?? '';
                    } elseif ($cell->getAttribute('t') === 'inlineStr') {
                        $value = $cell->getElementsByTagNameNS(
                            'http://schemas.openxmlformats.org/spreadsheetml/2006/main',
                            't'
                        )->item(0)?->textContent ?? '';
                    }
                    $row[$column] = $value;
                }

                if ($row !== []) {
                    ksort($row);
                    $rows[] = array_replace(array_fill(0, max(array_keys($row)) + 1, ''), $row);
                    if (count($rows) > self::MAX_ROWS + 1) {
                        throw new InvalidArgumentException('Maksimal 100 data guru dalam satu file.');
                    }
                }
            }

            return $rows;
        } finally {
            $archive->close();
        }
    }

    private function readEntry(ZipArchive $archive, string $name): string
    {
        $index = $archive->locateName($name);
        $stat = $index === false ? false : $archive->statIndex($index);
        if ($stat === false || ($stat['size'] ?? 0) > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('Struktur file Excel tidak valid atau terlalu besar.');
        }

        $contents = $archive->getFromIndex($index);
        if ($contents === false) {
            throw new InvalidArgumentException('Sheet Excel tidak dapat dibaca.');
        }

        return $contents;
    }

    private function loadXml(string $contents): DOMDocument
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($contents, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new InvalidArgumentException('Struktur XML pada file Excel tidak valid.');
        }

        return $document;
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(str_replace(' ', '_', trim($header)));
    }
}
