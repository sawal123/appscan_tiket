<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

class TicketImportService
{
    private const INSERT_CHUNK_SIZE = 1000;

    public function templateXlsx(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ticket-template').'.xlsx';
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Template Excel tidak dapat dibuat.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Import QR" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->templateWorksheetXml());
        $zip->close();

        $contents = (string) file_get_contents($path);
        @unlink($path);

        return $contents;
    }

    /**
     * @return array{success: int, failed: int, errors: array<int, string>}
     */
    public function import(int $eventId, UploadedFile|string $file, ?User $registeredBy = null): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! $path || ! is_readable($path)) {
            throw new RuntimeException('File import tidak dapat dibaca.');
        }

        $result = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        $rows = $this->readRows($path, $this->extensionFor($file));
        $header = array_shift($rows);

        if ($this->invalidHeader($header)) {
            return [
                'success' => 0,
                'failed' => 1,
                'errors' => ['Header file harus: qr_code,ticket_category'],
            ];
        }

        $categories = TicketCategory::query()
            ->where('event_id', $eventId)
            ->get()
            ->keyBy(fn (TicketCategory $category): string => $this->normalizeCategory($category->name));

        $seenQrCodes = [];
        $candidates = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isBlankRow($row)) {
                continue;
            }

            $qrCode = Ticket::normalizeQrCode((string) ($row[0] ?? ''));
            $categoryName = trim((string) ($row[1] ?? ''));
            $category = $categories->get($this->normalizeCategory($categoryName));

            if ($qrCode === '') {
                $this->fail($result, $rowNumber, 'QR kosong.');

                continue;
            }

            if (isset($seenQrCodes[$qrCode])) {
                $this->fail($result, $rowNumber, "QR {$qrCode} duplicate di file.");

                continue;
            }

            $seenQrCodes[$qrCode] = true;

            if (! $category) {
                $this->fail($result, $rowNumber, "Kategori {$categoryName} tidak valid untuk event ini.");

                continue;
            }

            $candidates[] = [
                'row' => $rowNumber,
                'qr_code' => $qrCode,
                'ticket_category_id' => $category->id,
            ];
        }

        $existingQrCodes = $this->existingQrCodes(array_column($candidates, 'qr_code'));
        $now = now();
        $inserts = [];

        foreach ($candidates as $candidate) {
            if (isset($existingQrCodes[$candidate['qr_code']])) {
                $this->fail($result, $candidate['row'], "QR {$candidate['qr_code']} sudah terdaftar.");

                continue;
            }

            $inserts[] = [
                'event_id' => $eventId,
                'ticket_category_id' => $candidate['ticket_category_id'],
                'code' => $candidate['qr_code'],
                'qr_code' => $candidate['qr_code'],
                'status' => Ticket::STATUS_REGISTERED,
                'registered_at' => $now,
                'registered_by' => $registeredBy?->id,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $result['success'] = $this->insertTickets($inserts);

        return $result;
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readRows(string $path, string $extension): array
    {
        return match ($extension) {
            'csv', 'txt' => $this->readCsvRows($path),
            'xlsx' => $this->readXlsxRows($path),
            default => throw new RuntimeException('Format file import tidak didukung.'),
        };
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibuka.');
        }

        $rows = [];

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function readXlsxRows(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel tidak dapat dibuka.');
        }

        try {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

            if ($sheetXml === false) {
                throw new RuntimeException('Sheet pertama Excel tidak ditemukan.');
            }

            $sharedStrings = $this->readSharedStrings($zip);
        } finally {
            $zip->close();
        }

        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false) {
            throw new RuntimeException('Sheet Excel tidak valid.');
        }

        $rows = [];

        foreach ($sheet->sheetData->row as $sheetRow) {
            $row = [];

            foreach ($sheetRow->c as $cell) {
                $attributes = $cell->attributes();
                $columnIndex = $this->columnIndex((string) ($attributes['r'] ?? ''));
                $row[$columnIndex] = $this->cellValue($cell, (string) ($attributes['t'] ?? ''), $sharedStrings);
            }

            ksort($row);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $sharedStrings = simplexml_load_string($xml);

        if ($sharedStrings === false) {
            return [];
        }

        $values = [];

        foreach ($sharedStrings->si as $item) {
            $text = '';

            if (isset($item->t)) {
                $text = (string) $item->t;
            } else {
                foreach ($item->r as $run) {
                    $text .= (string) $run->t;
                }
            }

            $values[] = $text;
        }

        return $values;
    }

    private function templateWorksheetXml(): string
    {
        $rows = [
            ['qr_code', 'ticket_category'],
            ['FEST-REG-0001', 'Regular'],
            ['FEST-VIP-0001', 'VIP'],
        ];
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $xml .= '<row r="'.$rowNumber.'">';

            foreach ($row as $columnIndex => $value) {
                $cell = chr(65 + $columnIndex).$rowNumber;
                $xml .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_XML1).'</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private function cellValue(\SimpleXMLElement $cell, string $type, array $sharedStrings): ?string
    {
        if ($type === 's') {
            return $sharedStrings[(int) $cell->v] ?? '';
        }

        if ($type === 'inlineStr') {
            return isset($cell->is->t) ? (string) $cell->is->t : '';
        }

        return isset($cell->v) ? (string) $cell->v : null;
    }

    private function columnIndex(string $cellReference): int
    {
        preg_match('/^[A-Z]+/i', $cellReference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }

    /**
     * @param  array<int, string|null>|false|null  $header
     */
    private function invalidHeader(array|false|null $header): bool
    {
        if (! is_array($header) || count($header) < 2) {
            return true;
        }

        return trim(strtolower((string) $header[0])) !== 'qr_code'
            || trim(strtolower((string) $header[1])) !== 'ticket_category';
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return trim(implode('', array_map(fn ($value): string => (string) $value, $row))) === '';
    }

    private function normalizeCategory(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    /**
     * @param  array<int, string>  $qrCodes
     * @return array<string, true>
     */
    private function existingQrCodes(array $qrCodes): array
    {
        if ($qrCodes === []) {
            return [];
        }

        $existing = [];

        foreach (array_chunk(array_values(array_unique($qrCodes)), self::INSERT_CHUNK_SIZE) as $chunk) {
            Ticket::query()
                ->whereIn('qr_code', $chunk)
                ->orWhereIn('code', $chunk)
                ->get(['qr_code', 'code'])
                ->each(function (Ticket $ticket) use (&$existing): void {
                    $existing[$ticket->qr_code] = true;
                    $existing[$ticket->code] = true;
                });
        }

        return $existing;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tickets
     */
    private function insertTickets(array $tickets): int
    {
        $success = 0;

        foreach (array_chunk($tickets, self::INSERT_CHUNK_SIZE) as $chunk) {
            try {
                DB::table('tickets')->insert($chunk);
                $success += count($chunk);
            } catch (QueryException) {
                foreach ($chunk as $ticket) {
                    try {
                        DB::table('tickets')->insert($ticket);
                        $success++;
                    } catch (QueryException) {
                        //
                    }
                }
            }
        }

        return $success;
    }

    private function extensionFor(UploadedFile|string $file): string
    {
        $extension = $file instanceof UploadedFile
            ? ($file->getClientOriginalExtension() ?: $file->extension())
            : pathinfo($file, PATHINFO_EXTENSION);

        return mb_strtolower($extension);
    }

    /**
     * @param  array{success: int, failed: int, errors: array<int, string>}  $result
     */
    private function fail(array &$result, int $rowNumber, string $message): void
    {
        $result['failed']++;
        $result['errors'][] = "Baris {$rowNumber}: {$message}";
    }
}
