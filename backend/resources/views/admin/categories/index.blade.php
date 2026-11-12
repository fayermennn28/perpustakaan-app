@extends('layouts.admin')

@section('title', 'Kategori')

@section('content')
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
        <h4 class="fw-bold mb-0">Kategori Buku</h4>
        <div class="text-muted small">{{ $categories->count() }} kategori terdaftar</div>
    </div>

    <div class="card-soft p-3 mb-3">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="row g-2">
            @csrf
            <div class="col-sm-8 col-md-9">
                <input type="text" name="name" value="{{ old('name') }}" class="form-control"
                       placeholder="Nama kategori baru, misalnya Sejarah" maxlength="100" required>
            </div>
            <div class="col-sm-4 col-md-3 d-grid">
                <button class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
            </div>
        </form>
    </div>

    <div class="card-soft overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover table-stack mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Nama</th>
                        <th>Jumlah Buku</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td data-label="#">{{ $loop->iteration }}</td>
                            <td data-label="Nama" class="fw-medium">{{ $category->name }}</td>
                            <td data-label="Jumlah Buku">
                                <span class="badge rounded-pill text-bg-secondary">{{ $category->books_count }} buku</span>
                            </td>
                            <td data-label="Aksi" class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editCategory{{ $category->id }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                          onsubmit="return confirm('Hapus kategori {{ $category->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="bi bi-tags fs-1 d-block mb-2"></i>
                                Belum ada kategori.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($categories as $category)
        <div class="modal fade" id="editCategory{{ $category->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="modal-content">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Ubah Kategori</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label fw-medium">Nama kategori</label>
                        <input type="text" name="name" value="{{ $category->name }}"
                               class="form-control" maxlength="100" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection