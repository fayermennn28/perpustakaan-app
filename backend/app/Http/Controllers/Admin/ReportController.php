<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const STATUSES = [
        'diajukan', 'dipinjam', 'pengembalian_diajukan',
        'dikembalikan', 'ditolak', 'terlambat',
    ];

    public function loans(Request $request): View
    {
        [$from, $to, $status] = $this->filters($request);

        $base = $this->baseQuery($from, $to, $status);

        $summary = [
            'total'        => (clone $base)->count(),
            'dikembalikan' => (clone $base)->where('status', Loan::STATUS_DIKEMBALIKAN)->count(),
            'ditolak'      => (clone $base)->where('status', Loan::STATUS_DITOLAK)->count(),
            'denda'        => (clone $base)->where('status', Loan::STATUS_DIKEMBALIKAN)->sum('fine_amount'),
        ];

        $loans = (clone $base)
            ->with(['user', 'book'])
            ->latest('requested_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.loans', compact('loans', 'summary', 'from', 'to', 'status'));
    }

    public function export(Request $request): StreamedResponse
    {
        [$from, $to, $status] = $this->filters($request);

        $loans = $this->baseQuery($from, $to, $status)
            ->with(['user', 'book'])
            ->latest('requested_at')
            ->latest('id')
            ->get();

        $filename = 'laporan-transaksi-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($loans) {
            $out = fopen('php://output', 'w');

            // BOM supaya Excel membaca UTF-8 dengan benar
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Kode Anggota', 'Nama Anggota', 'Judul Buku', 'Status',
                'Tanggal Diajukan', 'Tanggal Disetujui', 'Jatuh Tempo', 'Tanggal Kembali',
                'Terlambat (hari)', 'Denda (Rp)', 'Catatan',
            ]);

            foreach ($loans as $loan) {
                fputcsv($out, [
                    $loan->user->member_code,
                    $loan->user->name,
                    $loan->book->title,
                    $loan->status,
                    $loan->requested_at?->format('Y-m-d H:i'),
                    $loan->approved_at?->format('Y-m-d H:i'),
                    $loan->due_date?->format('Y-m-d'),
                    $loan->returned_at?->format('Y-m-d H:i'),
                    $loan->lateDays(),
                    $loan->status === Loan::STATUS_DIKEMBALIKAN ? $loan->fine_amount : $loan->currentFine(),
                    $loan->admin_note,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $request->validate([
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ]);

        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : today()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : today()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to, $request->query('status') ?: null];
    }

    private function baseQuery(Carbon $from, Carbon $to, ?string $status)
    {
        return Loan::query()
            ->whereBetween('requested_at', [$from, $to])
            ->when($status === 'terlambat', function ($query) {
                $query->whereIn('status', [Loan::STATUS_DIPINJAM, Loan::STATUS_PENGEMBALIAN_DIAJUKAN])
                      ->whereDate('due_date', '<', today());
            })
            ->when($status && $status !== 'terlambat', fn ($query) => $query->where('status', $status));
    }
}