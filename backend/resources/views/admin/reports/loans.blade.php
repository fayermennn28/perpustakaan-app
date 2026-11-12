@extends('layouts.admin')

@section('title', 'Laporan')

@section('content')
    @php
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $statusLabels = [
            'diajukan'              => 'Pengajuan pinjam',
            'dipinjam'              => 'Dipinjam',
            'pengembalian_diajukan' => 'Pengajuan kembali',
            'terlambat'             => 'Terlambat',
            'dikembalikan'          => 'Dikembalikan',
            'ditolak'               => 'Ditolak',
        ];
        $exportParams = array_filter([
            'from'   => $from->toDateString(),
            'to'     => $to->toDateString(),
            'status' => $status,
        ]);
    @endphp

    @if ($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Laporan Transaksi</h4>
            <div class="text-muted small">
                Periode {{ $from->format('d M Y') }} - {{ $to->format('d M Y') }}
                @if ($status) &middot; {{ $statusLabels[$status] }} @endif
            </div>
        </div>

        <div class="d-flex gap-2 no-print">
            <a href="{{ route('admin.reports.loans.export', $exportParams) }}" class="btn btn-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export CSV
            </a>
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Cetak
            </button>
        </div>
    </div>

    <div class="card-soft p-3 mb-3 no-print">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-6 col-md-3">
                <label class="form-label small fw-medium mb-1">Dari tanggal</label>
                <input type="date" name="from" value="{{ $from->toDateString() }}" class="form-control">
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label small fw-medium mb-1">Sampai tanggal</label>
                <input type="date" name="to" value="{{ $to->toDateString() }}" class="form-control">
            </div>
            <div class="col-sm-8 col-md-4">
                <label class="form-label small fw-medium mb-1">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-4 col-md-2 d-grid">
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Terapkan</button>
            </div>
        </form>
    </div>

    <div class="stat-grid mb-3">
        <div class="card-soft stat-card">
            <div class="stat-icon primary"><i class="bi bi-receipt"></i></div>
            <div>
                <div class="text-muted small">Total transaksi</div>
                <div class="stat-value">{{ $summary['total'] }}</div>
            </div>
        </div>
        <div class="card-soft stat-card">
            <div class="stat-icon success"><i class="bi bi-check2-circle"></i></div>
            <div>
                <div class="text-muted small">Dikembalikan</div>
                <div class="stat-value">{{ $summary['dikembalikan'] }}</div>
            </div>
        </div>
        <div class="card-soft stat-card">
            <div class="stat-icon warning"><i class="bi bi-x-circle"></i></div>
            <div>
                <div class="text-muted small">Ditolak</div>
                <div class="stat-value">{{ $summary['ditolak'] }}</div>
            </div>
        </div>
        <div class="card-soft stat-card">
            <div class="stat-icon danger"><i class="bi bi-cash-coin"></i></div>
            <div>
                <div class="text-muted small">Total denda</div>
                <div class="stat-value fs-4">{{ $rupiah($summary['denda']) }}</div>
            </div>
        </div>
    </div>

    <div class="card-soft overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-stack mb-0">
                <thead>
                    <tr>
                        <th>Diajukan</th>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Status</th>
                        <th>Jatuh tempo</th>
                        <th>Kembali</th>
                        <th>Terlambat</th>
                        <th>Denda</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        @php $late = $loan->lateDays(); @endphp
                        <tr>
                            <td data-label="Diajukan" class="text-nowrap">{{ $loan->requested_at?->format('d M Y') }}</td>
                            <td data-label="Anggota">
                                <div class="text-md-start">
                                    <div class="fw-medium">{{ $loan->user->name }}</div>
                                    <div class="text-muted small">{{ $loan->user->member_code ?? '-' }}</div>
                                </div>
                            </td>
                            <td data-label="Buku">{{ $loan->book->title }}</td>
                            <td data-label="Status">@include('admin.partials.loan-status', ['status' => $loan->status])</td>
                            <td data-label="Jatuh tempo" class="text-nowrap">{{ $loan->due_date?->format('d M Y') ?? '-' }}</td>
                            <td data-label="Kembali" class="text-nowrap">{{ $loan->returned_at?->format('d M Y') ?? '-' }}</td>
                            <td data-label="Terlambat">
                                @if ($late > 0)
                                    <span class="text-danger fw-medium">{{ $late }} hari</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td data-label="Denda" class="text-nowrap">
                                @if ($loan->status === 'dikembalikan' && $loan->fine_amount > 0)
                                    <span class="text-danger fw-medium">{{ $rupiah($loan->fine_amount) }}</span>
                                @elseif ($loan->currentFine() > 0)
                                    <span class="text-danger">{{ $rupiah($loan->currentFine()) }} <span class="small">(estimasi)</span></span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada transaksi pada periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 no-print">
        {{ $loans->links('pagination::bootstrap-5') }}
    </div>

    <div class="text-muted small mt-2 no-print">
        Tombol Cetak mencetak halaman yang sedang tampil. Untuk seluruh data pada periode ini, gunakan Export CSV.
    </div>
@endsection