@extends('layouts.admin')

@section('title', 'Anggota')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Data Anggota</h4>
            <div class="text-muted small">{{ $members->total() }} anggota ditemukan</div>
        </div>
        <a href="{{ route('admin.members.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i>Tambah Anggota
        </a>
    </div>

    <div class="card-soft p-3 mb-3">
        <form method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                       placeholder="Cari nama, email, atau kode anggota">
            </div>
            <div class="col-sm-8 col-md-4">
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                </select>
            </div>
            <div class="col-sm-4 col-md-2 d-grid">
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
                        <th>Kode</th>
                        <th>Telepon</th>
                        <th>Pinjaman Aktif</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($members as $member)
                        <tr>
                            <td data-label="Anggota">
                                <div class="d-flex align-items-center gap-3">
                                    @include('admin.partials.member-avatar', ['user' => $member, 'size' => 40])
                                    <div class="text-start">
                                        <div class="fw-medium">{{ $member->name }}</div>
                                        <div class="text-muted small">{{ $member->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Kode" class="fw-medium">{{ $member->member_code ?? '-' }}</td>
                            <td data-label="Telepon" class="text-muted">{{ $member->phone ?? '-' }}</td>
                            <td data-label="Pinjaman Aktif">
                                <span class="badge rounded-pill text-bg-secondary">{{ $member->active_loans_count }}</span>
                            </td>
                            <td data-label="Status">
                                @if ($member->is_active)
                                    <span class="badge rounded-pill text-bg-success">Aktif</span>
                                @else
                                    <span class="badge rounded-pill text-bg-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td data-label="Aksi" class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.members.show', $member) }}"
                                       class="btn btn-sm btn-outline-secondary" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.members.edit', $member) }}"
                                       class="btn btn-sm btn-outline-primary" title="Ubah">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.members.toggle', $member) }}"
                                          onsubmit="return confirm('{{ $member->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ addslashes($member->name) }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm {{ $member->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                                title="{{ $member->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i class="bi bi-power"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.members.destroy', $member) }}"
                                          onsubmit="return confirm('Hapus anggota {{ addslashes($member->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-people fs-1 d-block mb-2"></i>
                                Tidak ada anggota yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $members->links('pagination::bootstrap-5') }}
    </div>
@endsection