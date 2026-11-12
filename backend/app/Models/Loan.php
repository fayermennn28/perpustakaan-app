<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'book_id', 'status', 'requested_at', 'approved_at',
    'due_date', 'return_requested_at', 'returned_at', 'fine_amount', 'admin_note',
])]
class Loan extends Model
{
    public const STATUS_DIAJUKAN = 'diajukan';
    public const STATUS_DITOLAK = 'ditolak';
    public const STATUS_DIPINJAM = 'dipinjam';
    public const STATUS_PENGEMBALIAN_DIAJUKAN = 'pengembalian_diajukan';
    public const STATUS_DIKEMBALIKAN = 'dikembalikan';

    public const ACTIVE_STATUSES = [
    self::STATUS_DIAJUKAN,
    self::STATUS_DIPINJAM,
    self::STATUS_PENGEMBALIAN_DIAJUKAN,
];
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'return_requested_at' => 'datetime',
            'returned_at' => 'datetime',
            'due_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    // Jumlah hari terlambat (hanya untuk buku yang belum kembali)
    public function daysLate(): int
    {
        if (
            ! $this->due_date
            || ! in_array($this->status, [self::STATUS_DIPINJAM, self::STATUS_PENGEMBALIAN_DIAJUKAN])
        ) {
        return 0;
        }

        return $this->due_date->lt(today()) ? (int) $this->due_date->diffInDays(today()) : 0;
    }

    // Denda berjalan (estimasi) berdasarkan keterlambatan hari ini
    public function currentFine(): int
    {
        return $this->daysLate() * (int) config('library.fine_per_day');
    }

    // Hari terlambat untuk laporan: untuk buku yang sudah kembali dihitung sampai tanggal kembali
    public function lateDays(): int
    {
        if (! $this->due_date) {
            return 0;
        }

        if ($this->status === self::STATUS_DIKEMBALIKAN && $this->returned_at) {
            $returned = $this->returned_at->copy()->startOfDay();

        return $returned->gt($this->due_date) ? (int) $this->due_date->diffInDays($returned) : 0;
        }

        return $this->daysLate();
    }
}