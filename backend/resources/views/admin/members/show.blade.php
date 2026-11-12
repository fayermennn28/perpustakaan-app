@extends('layouts.admin')

@section('title', 'Detail Anggota')

@section('content')
    <a href="{{ route('admin.members.index') }}" class="text-decoration-none small">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke daftar
    </a>

    <div class="row g-3 mt-1">
        <div class="col-lg-4">
            <div class="card-soft p-4 text-center">
                <div class="d-flex justify-content-center mb-3">
                    @include('admin.partials.member-avatar', ['user' => $member, 'size' => 96])
                </div>
                <h5 class="fw-bold mb-0">{{ $member->name }}</h5>
                <div class="text-muted mb-2">{{ $member->member_code ?? '-' }}</div>

                @if ($member->is_active)
                    <span class="badge rounded-pill text-bg-success">Aktif</span>
                @else
                    <span class="badge rounded-pill text-bg-danger">Nonaktif</span>
                @endif

                <hr style="border-color: var(--border);">

                <div class="text-start small">
                    <div class="mb-2"><i class="bi bi-envelope me-2 text-muted"></i>{{ $member->email }}</div>
                    <div class="mb-2"><i class="bi bi-telephone me-2 text-muted"></i>{{ $member->phone ?? '-' }}</div>
                    <div><i class="bi bi-geo-alt me-2 text-muted"></i>{{ $member->address ?? '-' }}</div>
                </div>

                <div class="d-grid mt-4">
                    <a href="{{ route('admin.members.edit', $member) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Ubah data
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card-soft overflow-hidden">
                <div class="px-4 py-3 border-bottom fw-semibold" style="border-color: var(--border) !important;">
                    Riwayat transaksi
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-stack mb-0">
                        <thead>
                            <tr>
                                <th>Buku</th>
                                <th>Status</th>
                                <th>Diajukan</th>
                                <th>Jatuh tempo</th>
                                <th>Denda</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($loans as $loan)
                                <tr>
                                    <td data-label="Buku" class="fw-medium">{{ $loan->book->title }}</td>
                                    <td data-label="Status">
                                        @include('admin.partials.loan-status', ['status' => $loan->status])
                                    </td>
                                    <td data-label="Diajukan" class="text-muted">{{ $loan->requested_at?->format('d M Y') }}</td>
                                    <td data-label="Jatuh tempo" class="text-muted">{{ $loan->due_date?->format('d M Y') ?? '-' }}</td>
                                    <td data-label="Denda">
                                        {{ $loan->fine_amount > 0 ? 'Rp ' . number_format($loan->fine_amount, 0, ',', '.') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Belum ada transaksi.
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
        </div>
    </div>
@endsection