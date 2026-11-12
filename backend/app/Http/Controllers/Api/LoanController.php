<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoanResource;
use App\Models\Book;
use App\Models\Loan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanController extends Controller
{
    // Riwayat transaksi milik anggota yang login
    public function index(Request $request)
    {
        $loans = $request->user()->loans()
            ->with('book.category')
            ->when($request->query('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest('requested_at')
            ->latest('id')
            ->paginate(10);

        return LoanResource::collection($loans);
    }

    // Ajukan peminjaman
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
        ]);

        $user = $request->user();
        $book = Book::findOrFail($data['book_id']);

        if ($book->stock < 1) {
            return response()->json(['message' => 'Stok buku sedang habis.'], 422);
        }

        $activeLoans = $user->loans()->whereIn('status', Loan::ACTIVE_STATUSES);

        if ((clone $activeLoans)->where('book_id', $book->id)->exists()) {
            return response()->json([
                'message' => 'Kamu masih punya transaksi aktif untuk buku ini.',
            ], 422);
        }

        $max = config('library.max_loans');
        if ((clone $activeLoans)->count() >= $max) {
            return response()->json([
                'message' => "Batas peminjaman aktif adalah {$max} buku.",
            ], 422);
        }

        $loan = $user->loans()->create([
            'book_id'      => $book->id,
            'status'       => Loan::STATUS_DIAJUKAN,
            'requested_at' => now(),
        ]);

        return (new LoanResource($loan->load('book.category')))
            ->response()
            ->setStatusCode(201);
    }

    // Ajukan pengembalian
    public function requestReturn(Request $request, Loan $loan): JsonResponse|LoanResource
    {
        if ($loan->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Transaksi tidak ditemukan.'], 403);
        }

        if ($loan->status !== Loan::STATUS_DIPINJAM) {
            return response()->json([
                'message' => 'Pengembalian hanya bisa diajukan untuk buku yang sedang dipinjam.',
            ], 422);
        }

        $loan->update([
            'status'              => Loan::STATUS_PENGEMBALIAN_DIAJUKAN,
            'return_requested_at' => now(),
        ]);

        return new LoanResource($loan->load('book.category'));
    }
}