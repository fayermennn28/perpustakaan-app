<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoanController extends Controller
{
    private const FILTERS = [
        'diajukan', 'dipinjam', 'pengembalian_diajukan',
        'dikembalikan', 'ditolak', 'terlambat',
    ];

    public function index(Request $request): View
    {
        $status = $request->query('status');
        if (! in_array($status, self::FILTERS, true)) {
            $status = null;
        }

        $loans = Loan::with(['user', 'book'])
            ->when($status === 'terlambat', function ($query) {
                $query->whereIn('status', [Loan::STATUS_DIPINJAM, Loan::STATUS_PENGEMBALIAN_DIAJUKAN])
                      ->whereDate('due_date', '<', today());
            })
            ->when($status && $status !== 'terlambat', fn ($query) => $query->where('status', $status))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                          ->orWhere('member_code', 'like', "%{$search}%");
                    })->orWhereHas('book', function ($b) use ($search) {
                        $b->where('title', 'like', "%{$search}%");
                    });
                });
            })
            ->latest('requested_at')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $byStatus = Loan::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'semua'                 => $byStatus->sum(),
            'diajukan'              => $byStatus->get('diajukan', 0),
            'dipinjam'              => $byStatus->get('dipinjam', 0),
            'pengembalian_diajukan' => $byStatus->get('pengembalian_diajukan', 0),
            'dikembalikan'          => $byStatus->get('dikembalikan', 0),
            'ditolak'               => $byStatus->get('ditolak', 0),
            'terlambat'             => Loan::whereIn('status', [Loan::STATUS_DIPINJAM, Loan::STATUS_PENGEMBALIAN_DIAJUKAN])
                ->whereDate('due_date', '<', today())
                ->count(),
        ];

        return view('admin.loans.index', [
            'loans'  => $loans,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    // Setujui pengajuan pinjam: stok -1, jatuh tempo = hari ini + lama pinjam
    public function approve(Loan $loan): RedirectResponse
    {
        $outcome = DB::transaction(function () use ($loan) {
            $locked = Loan::with('user')->lockForUpdate()->find($loan->id);

            if ($locked->status !== Loan::STATUS_DIAJUKAN) {
                return ['error' => 'Pengajuan ini sudah diproses sebelumnya.'];
            }

            if (! $locked->user->is_active) {
                return ['error' => 'Akun anggota nonaktif, pengajuan tidak bisa disetujui.'];
            }

            $book = Book::lockForUpdate()->find($locked->book_id);

            if ($book->stock < 1) {
                return ['error' => 'Stok buku "' . $book->title . '" sedang habis.'];
            }

            $book->decrement('stock');

            $due = today()->addDays((int) config('library.loan_days'));

            $locked->update([
                'status'      => Loan::STATUS_DIPINJAM,
                'approved_at' => now(),
                'due_date'    => $due,
            ]);

            return ['due' => $due];
        });

        if (isset($outcome['error'])) {
            return back()->with('error', $outcome['error']);
        }

        return back()->with('success', 'Pengajuan disetujui. Jatuh tempo: ' . $outcome['due']->format('d M Y') . '.');
    }

    // Tolak pengajuan pinjam (catatan opsional)
    public function reject(Request $request, Loan $loan): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $outcome = DB::transaction(function () use ($loan, $data) {
            $locked = Loan::lockForUpdate()->find($loan->id);

            if ($locked->status !== Loan::STATUS_DIAJUKAN) {
                return 'Pengajuan ini sudah diproses sebelumnya.';
            }

            $locked->update([
                'status'     => Loan::STATUS_DITOLAK,
                'admin_note' => $data['admin_note'] ?? null,
            ]);

            return null;
        });

        if ($outcome) {
            return back()->with('error', $outcome);
        }

        return back()->with('success', 'Pengajuan ditolak.');
    }

    // Terima pengembalian: stok +1, hitung denda, simpan
    public function receiveReturn(Request $request, Loan $loan): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $outcome = DB::transaction(function () use ($loan, $data) {
            $locked = Loan::lockForUpdate()->find($loan->id);

            if (! in_array($locked->status, [Loan::STATUS_DIPINJAM, Loan::STATUS_PENGEMBALIAN_DIAJUKAN])) {
                return ['error' => 'Transaksi ini tidak sedang dalam status dipinjam.'];
            }

            // Hitung denda SEBELUM status berubah (daysLate hanya berlaku untuk buku yang belum kembali)
            $fine = $locked->currentFine();

            Book::whereKey($locked->book_id)->increment('stock');

            $locked->update([
                'status'      => Loan::STATUS_DIKEMBALIKAN,
                'returned_at' => now(),
                'fine_amount' => $fine,
                'admin_note'  => $data['admin_note'] ?? $locked->admin_note,
            ]);

            return ['fine' => $fine];
        });

        if (isset($outcome['error'])) {
            return back()->with('error', $outcome['error']);
        }

        $message = 'Pengembalian diterima, stok buku bertambah.';
        if ($outcome['fine'] > 0) {
            $message .= ' Denda keterlambatan: Rp ' . number_format($outcome['fine'], 0, ',', '.') . '.';
        }

        return back()->with('success', $message);
    }
}