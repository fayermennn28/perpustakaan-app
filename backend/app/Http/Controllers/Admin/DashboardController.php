<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $notReturned = [Loan::STATUS_DIPINJAM, Loan::STATUS_PENGEMBALIAN_DIAJUKAN];

        // Grafik aktivitas 30 hari (tampilan 7/14/30 hari dipotong di sisi browser)
        $days = 30;
        $start = today()->subDays($days - 1);

        $requested = Loan::where('requested_at', '>=', $start)
            ->selectRaw('DATE(requested_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $returned = Loan::where('returned_at', '>=', $start)
            ->selectRaw('DATE(returned_at) as d, COUNT(*) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        $chart = ['labels' => [], 'requested' => [], 'returned' => []];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();

            $chart['labels'][]    = $day->locale('id')->translatedFormat('d M');
            $chart['requested'][] = (int) ($requested[$key] ?? 0);
            $chart['returned'][]  = (int) ($returned[$key] ?? 0);
        }

        // Donut status transaksi
        $byStatus = Loan::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $statusChart = [
            ['label' => 'Dipinjam',          'value' => (int) $byStatus->get('dipinjam', 0),              'color' => '#0ea5e9'],
            ['label' => 'Pengajuan pinjam',  'value' => (int) $byStatus->get('diajukan', 0),              'color' => '#f59e0b'],
            ['label' => 'Pengajuan kembali', 'value' => (int) $byStatus->get('pengembalian_diajukan', 0), 'color' => '#8b5cf6'],
            ['label' => 'Dikembalikan',      'value' => (int) $byStatus->get('dikembalikan', 0),          'color' => '#10b981'],
            ['label' => 'Ditolak',           'value' => (int) $byStatus->get('ditolak', 0),               'color' => '#ef4444'],
        ];

        // Metrik tambahan
        $returnedTotal = Loan::where('status', Loan::STATUS_DIKEMBALIKAN)->count();
        $onTime = Loan::where('status', Loan::STATUS_DIKEMBALIKAN)->where('fine_amount', 0)->count();

        // Buku terpopuler (hanya transaksi yang benar-benar disetujui)
        $popularBooks = Book::withCount([
                'loans' => fn ($q) => $q->whereIn('status', [
                    Loan::STATUS_DIPINJAM,
                    Loan::STATUS_PENGEMBALIAN_DIAJUKAN,
                    Loan::STATUS_DIKEMBALIKAN,
                ]),
            ])
            ->orderByDesc('loans_count')
            ->limit(5)
            ->get()
            ->where('loans_count', '>', 0)
            ->values();

        return view('admin.dashboard', [
            'totalBooks'      => Book::count(),
            'availableBooks'  => Book::where('stock', '>', 0)->count(),
            'totalMembers'    => User::where('role', 'anggota')->count(),
            'activeMembers'   => User::where('role', 'anggota')->where('is_active', true)->count(),
            'activeLoans'     => Loan::where('status', Loan::STATUS_DIPINJAM)->count(),
            'pendingLoans'    => Loan::where('status', Loan::STATUS_DIAJUKAN)->count(),
            'pendingReturns'  => Loan::where('status', Loan::STATUS_PENGEMBALIAN_DIAJUKAN)->count(),
            'overdueCount'    => Loan::whereIn('status', $notReturned)->whereDate('due_date', '<', today())->count(),
            'finesThisMonth'  => (int) Loan::where('status', Loan::STATUS_DIKEMBALIKAN)
                ->where('returned_at', '>=', today()->startOfMonth())
                ->sum('fine_amount'),
            'onTimeRate'      => $returnedTotal > 0 ? (int) round($onTime / $returnedTotal * 100) : null,

            'chart'           => $chart,
            'statusChart'     => $statusChart,
            'popularBooks'    => $popularBooks,

            'lowStockBooks'   => Book::where('stock', '<=', (int) config('library.low_stock', 2))
                ->orderBy('stock')->orderBy('title')->limit(5)->get(),

            'overdueLoans'    => Loan::with(['user', 'book'])
                ->whereIn('status', $notReturned)
                ->whereDate('due_date', '<', today())
                ->orderBy('due_date')->limit(5)->get(),

            'recentLoans'     => Loan::with(['user', 'book'])
                ->whereIn('status', [Loan::STATUS_DIAJUKAN, Loan::STATUS_PENGEMBALIAN_DIAJUKAN])
                ->latest('requested_at')->limit(5)->get(),
        ]);
    }
}