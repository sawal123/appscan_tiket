<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TicketImportService
{
    /**
     * @return array{success: int, failed: int, errors: array<int, string>}
     */
    public function import(int $eventId, UploadedFile|string $csvFile, ?User $registeredBy = null): array
    {
        $path = $csvFile instanceof UploadedFile ? $csvFile->getRealPath() : $csvFile;

        if (! $path || ! is_readable($path)) {
            throw new RuntimeException('File CSV tidak dapat dibaca.');
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibuka.');
        }

        $result = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        try {
            $header = fgetcsv($handle);

            if ($this->invalidHeader($header)) {
                return [
                    'success' => 0,
                    'failed' => 1,
                    'errors' => ['Header CSV harus: qr_code,ticket_category'],
                ];
            }

            $categories = TicketCategory::query()
                ->where('event_id', $eventId)
                ->get()
                ->keyBy(fn (TicketCategory $category): string => $this->normalizeCategory($category->name));

            $seenQrCodes = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

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
                    $this->fail($result, $rowNumber, "QR {$qrCode} duplicate di CSV.");

                    continue;
                }

                $seenQrCodes[$qrCode] = true;

                if (! $category) {
                    $this->fail($result, $rowNumber, "Kategori {$categoryName} tidak valid untuk event ini.");

                    continue;
                }

                if ($this->qrCodeExists($qrCode)) {
                    $this->fail($result, $rowNumber, "QR {$qrCode} sudah terdaftar.");

                    continue;
                }

                DB::transaction(function () use ($eventId, $category, $qrCode, $registeredBy): void {
                    Ticket::create([
                        'event_id' => $eventId,
                        'ticket_category_id' => $category->id,
                        'code' => $qrCode,
                        'qr_code' => $qrCode,
                        'status' => Ticket::STATUS_REGISTERED,
                        'registered_at' => now(),
                        'registered_by' => $registeredBy?->id,
                    ]);
                });

                $result['success']++;
            }
        } finally {
            fclose($handle);
        }

        return $result;
    }

    /**
     * @param  array<int, string>|false|null  $header
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

    private function qrCodeExists(string $qrCode): bool
    {
        return Ticket::query()
            ->where('qr_code', $qrCode)
            ->orWhere('code', $qrCode)
            ->exists();
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
