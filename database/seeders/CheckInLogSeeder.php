<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\CheckInLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class CheckInLogSeeder extends Seeder
{
    public function run(): void
    {
        $tickets = Ticket::query()
            ->whereNotNull('checked_in_at')
            ->orderBy('id')
            ->get();

        $scannerIds = User::query()
            ->where('role', UserRole::Scanner->value)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        foreach ($tickets as $ticket) {
            CheckInLog::query()->firstOrCreate(
                ['ticket_id' => $ticket->id, 'status' => CheckInLog::STATUS_SUCCESS],
                [
                    'scanner_id' => $ticket->checked_in_by,
                    'qr_code' => $ticket->qr_code,
                    'scanned_at' => $ticket->checked_in_at,
                ],
            );
        }

        // A few repeat scans so the history page shows the non-success states too.
        foreach ($tickets->take(6) as $ticket) {
            CheckInLog::query()->firstOrCreate(
                ['ticket_id' => $ticket->id, 'status' => CheckInLog::STATUS_ALREADY_CHECKED_IN],
                [
                    'scanner_id' => $ticket->checked_in_by,
                    'qr_code' => $ticket->qr_code,
                    'scanned_at' => $ticket->checked_in_at->copy()->addMinutes(3),
                ],
            );
        }

        foreach (range(1, 6) as $index) {
            CheckInLog::query()->firstOrCreate(
                ['qr_code' => sprintf('UNKNOWN-%04d', $index), 'status' => CheckInLog::STATUS_INVALID],
                [
                    'ticket_id' => null,
                    'scanner_id' => $scannerIds === [] ? null : (int) $scannerIds[$index % count($scannerIds)],
                    'scanned_at' => now()->subMinutes(5 * $index),
                ],
            );
        }
    }
}
