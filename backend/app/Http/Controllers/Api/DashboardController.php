<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookResource;
use App\Http\Resources\LoanResource;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        // Transaksi yang masih "hidup" milik anggota ini
        $active = $user->loans()
            ->whereIn('status', Loan::ACTIVE_STATUSES)
            ->with('book.category')
            ->get();

        // Buku yang benar-benar sedang di tangan anggota
        $borrowed = $active->whereIn('status', [
            Loan::STATUS_DIPINJAM,
            Loan::STATUS_PENGEMBALIAN_DIAJUKAN,
        ]);

        $overdue = $borrowed->filter(fn (Loan $loan) => $loan->daysLate() > 0);

        $dueSoon = $borrowed->sortBy('due_date')->take(3)->values();

        $recent = $user->loans()
            ->with('book.category')
            ->latest('requested_at')
            ->latest('id')
            ->limit(3)
            ->get();

        $newBooks = Book::with('category')->latest()->limit(8)->get();

        $popular = Book::with('category')
            ->withCount([
                'loans' => fn ($q) => $q->whereIn('status', [
                    Loan::STATUS_DIPINJAM,
                    Loan::STATUS_PENGEMBALIAN_DIAJUKAN,
                    Loan::STATUS_DIKEMBALIKAN,
                ]),
            ])
            ->orderByDesc('loans_count')
            ->limit(6)
            ->get()
            ->where('loans_count', '>', 0)
            ->values();

        return response()->json([
            'summary' => [
                'active_loans'     => $borrowed->count(),
                'pending_requests' => $active->where('status', Loan::STATUS_DIAJUKAN)->count(),
                'pending_returns'  => $active->where('status', Loan::STATUS_PENGEMBALIAN_DIAJUKAN)->count(),
                'overdue'          => $overdue->count(),
                'estimated_fine'   => (int) $overdue->sum(fn (Loan $loan) => $loan->currentFine()),
                'loan_limit'       => (int) config('library.max_loans'),
                'slots_used'       => $active->count(),
            ],
            'due_soon'      => LoanResource::collection($dueSoon)->resolve(),
            'recent_loans'  => LoanResource::collection($recent)->resolve(),
            'new_books'     => BookResource::collection($newBooks)->resolve(),
            'popular_books' => BookResource::collection($popular)->resolve(),
        ]);
    }
}