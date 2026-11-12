@extends('layouts.admin')

@section('title', 'Transaksi')

@section('content')
    @php
        $tabs = [
            ''                      => ['Semua', $counts['semua']],
            'diajukan'              => ['Pengajuan pinjam', $counts['diajukan']],
            'dipinjam'              => ['Dipinjam', $counts['dipinjam']],
            'pengembalian_diajukan' => ['Pengajuan kembali', $counts['pengembalian_diajukan']],
            'terlambat'             => ['Terlambat', $counts['terlambat']],
            'dikembalikan'          => ['Dikembalikan', $counts['dikembalikan']],
            'ditolak'               => ['Ditolak', $counts['ditolak']],
        ];
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');
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

    <div class="mb-3">
        <h4 class="fw-bold mb-0">Transaksi Peminjaman</h4>
        <div class="text-muted small">Proses pengajuan pinjam dan pengembalian dari anggota.</div>
    </div>

    <div class="mb-3 overflow-auto pb-1">
        <ul class="nav nav-pills loan-tabs flex-nowrap gap-2">
            @foreach ($tabs as $key => [$label, $total])
                <li class="nav-item">
                    <a class="nav-link text-nowrap {{ (string) $status === (string) $key ? 'active' : '' }}"
                       href="{{ route('admin.loans.index', array_filter(['status' => $key, 'search' => request('search')])) }}">
                        {{ $label }}
                        <span class="badge rounded-pill text-bg-light ms-1">{{ $total }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="card-soft p-3 mb-3">
        <form method="GET" class="row g-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="col-sm-9 col-md-10">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                       placeholder="Cari nama anggota, kode anggota, atau judul buku">
            </div>
            <div class="col-sm-3 col-md-2 d-grid">
                <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button>
            </div>
        </form>
    </div>

    <div class="card-soft overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-stack mb-0">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Denda</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($loans as $loan)
                        @php
                            $late = $loan->daysLate();
                            $canReturn = in_array($loan->status, ['dipinjam', 'pengembalian_diajukan']);
                        @endphp
                        <tr>
                            <td data-label="Anggota">
                                <div class="d-flex align-items-center gap-2">
                                    @include('admin.partials.member-avatar', ['user' => $loan->user, 'size' => 36])
                                    <div class="text-start">
                                        <div class="fw-medium">{{ $loan->user->name }}</div>
                                        <div class="text-muted small">{{ $loan->user->member_code ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>

                            <td data-label="Buku" class="fw-medium">{{ $loan->book->title }}</td>

                            <td data-label="Status">
                                <div class="d-flex flex-column align-items-md-start align-items-end gap-1">
                                    @include('admin.partials.loan-status', ['status' => $loan->status])
                                    @if ($late > 0)
                                        <span class="badge rounded-pill text-bg-danger">Terlambat {{ $late }} hari</span>
                                    @endif
                                </div>
                            </td>

                            <td data-label="Tanggal">
                                <div>
                                    <div>{{ $loan->requested_at?->format('d M Y') }}</div>
                                    @if ($loan->due_date)
                                        <div class="small {{ $late > 0 ? 'text-danger' : 'text-muted' }}">
                                            Jatuh tempo: {{ $loan->due_date->format('d M Y') }}
                                        </div>
                                    @endif
                                    @if ($loan->returned_at)
                                        <div class="small text-muted">Kembali: {{ $loan->returned_at->format('d M Y') }}</div>
                                    @endif
                                </div>
                            </td>

                            <td data-label="Denda">
                                @if ($loan->status === 'dikembalikan' && $loan->fine_amount > 0)
                                    <span class="fw-medium text-danger">{{ $rupiah($loan->fine_amount) }}</span>
                                @elseif ($late > 0)
                                    <span class="text-danger">{{ $rupiah($loan->currentFine()) }}
                                        <span class="small">(estimasi)</span></span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            <td data-label="Aksi" class="text-end">
                                @if ($loan->status === 'diajukan')
                                    <div class="d-inline-flex gap-1">
                                        <form method="POST" action="{{ route('admin.loans.approve', $loan) }}"
                                              onsubmit="return confirm('Setujui peminjaman buku ini? Stok berkurang 1 dan jatuh tempo {{ config('library.loan_days') }} hari dari hari ini.')">
                                            @csrf
                                            <button class="btn btn-sm btn-success">
                                                <i class="bi bi-check-lg me-1"></i>Setujui
                                            </button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                data-bs-toggle="modal" data-bs-target="#rejectLoan{{ $loan->id }}">
                                            <i class="bi bi-x-lg me-1"></i>Tolak
                                        </button>
                                    </div>
                                @elseif ($canReturn)
                                    <button type="button" class="btn btn-sm btn-primary"
                                            data-bs-toggle="modal" data-bs-target="#returnLoan{{ $loan->id }}">
                                        <i class="bi bi-box-arrow-in-down me-1"></i>Terima kembali
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada transaksi pada tampilan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $loans->links('pagination::bootstrap-5') }}
    </div>

    {{-- Modal tolak & terima kembali --}}
    @foreach ($loans as $loan)
        @if ($loan->status === 'diajukan')
            <div class="modal fade" id="rejectLoan{{ $loan->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('admin.loans.reject', $loan) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Tolak pengajuan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">
                                <strong>{{ $loan->user->name }}</strong> mengajukan
                                <strong>{{ $loan->book->title }}</strong>.
                            </p>
                            <label class="form-label fw-medium">Alasan penolakan (opsional)</label>
                            <textarea name="admin_note" rows="3" maxlength="500" class="form-control"
                                      placeholder="Contoh: Buku sedang dalam perbaikan"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-danger">Tolak pengajuan</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @if (in_array($loan->status, ['dipinjam', 'pengembalian_diajukan']))
            <div class="modal fade" id="returnLoan{{ $loan->id }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('admin.loans.return', $loan) }}" class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Terima pengembalian</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-3">
                                <strong>{{ $loan->user->name }}</strong> mengembalikan
                                <strong>{{ $loan->book->title }}</strong>.
                            </p>

                            <div class="rounded-3 p-3 mb-3" style="background: var(--brand-soft);">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Jatuh tempo</span>
                                    <span>{{ $loan->due_date?->format('d M Y') ?? '-' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Keterlambatan</span>
                                    <span>{{ $loan->daysLate() }} hari</span>
                                </div>
                                <div class="d-flex justify-content-between fw-semibold">
                                    <span>Denda</span>
                                    <span class="{{ $loan->currentFine() > 0 ? 'text-danger' : '' }}">
                                        {{ $rupiah($loan->currentFine()) }}
                                    </span>
                                </div>
                            </div>

                            <label class="form-label fw-medium">Catatan (opsional)</label>
                            <textarea name="admin_note" rows="2" maxlength="500" class="form-control"
                                      placeholder="Contoh: Sampul sedikit rusak"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary">Konfirmasi pengembalian</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endsection