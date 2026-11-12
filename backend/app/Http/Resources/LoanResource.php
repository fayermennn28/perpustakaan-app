<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Hitung keterlambatan hanya untuk transaksi yang bukunya belum kembali
        $daysLate = 0;
        if (
            in_array($this->status, ['dipinjam', 'pengembalian_diajukan'])
            && $this->due_date
            && $this->due_date->lt(today())
        ) {
            $daysLate = (int) $this->due_date->diffInDays(today());
        }

        return [
            'id'                  => $this->id,
            'status'              => $this->status,
            'book'                => new BookResource($this->whenLoaded('book')),
            'requested_at'        => $this->requested_at?->toIso8601String(),
            'approved_at'         => $this->approved_at?->toIso8601String(),
            'due_date'            => $this->due_date?->toDateString(),
            'return_requested_at' => $this->return_requested_at?->toIso8601String(),
            'returned_at'         => $this->returned_at?->toIso8601String(),
            'is_overdue'          => $daysLate > 0,
            'days_late'           => $daysLate,
            // Setelah dikembalikan pakai denda final, sebelum itu tampilkan estimasi
            'fine_amount'         => $this->status === 'dikembalikan'
                ? $this->fine_amount
                : $daysLate * config('library.fine_per_day'),
            'admin_note'          => $this->admin_note,
        ];
    }
}