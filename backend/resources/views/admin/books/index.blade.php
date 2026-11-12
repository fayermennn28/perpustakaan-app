@extends('layouts.admin')

@section('title', 'Buku')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-0">Data Buku</h4>
            <div class="text-muted small">{{ $books->total() }} buku ditemukan</div>
        </div>
        <a href="{{ route('admin.books.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Tambah Buku
        </a>
    </div>

    <div class="card-soft p-3 mb-3">
        <form method="GET" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                       placeholder="Cari judul, penulis, atau ISBN">
            </div>
            <div class="col-sm-8 col-md-4">
                <select name="category_id" class="form-select">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
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
                        <th>Buku</th>
                        <th>Kategori</th>
                        <th>ISBN</th>
                        <th>Stok</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($books as $book)
                        <tr>
                            <td data-label="Buku">
                                <div class="d-flex align-items-center gap-3">
                                    @if ($book->cover)
                                        <img src="{{ asset('storage/' . $book->cover) }}" alt="" class="cover-thumb">
                                    @else
                                        <div class="cover-thumb"><i class="bi bi-book"></i></div>
                                    @endif
                                    <div class="text-start">
                                        <div class="fw-medium">{{ $book->title }}</div>
                                        <div class="text-muted small">{{ $book->author }}</div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Kategori">
                                <span class="badge rounded-pill text-bg-secondary">{{ $book->category->name }}</span>
                            </td>
                            <td data-label="ISBN" class="text-muted">{{ $book->isbn ?? '-' }}</td>
                            <td data-label="Stok">
                                @if ($book->stock > 0)
                                    <span class="badge rounded-pill text-bg-success">{{ $book->stock }} tersedia</span>
                                @else
                                    <span class="badge rounded-pill text-bg-danger">Habis</span>
                                @endif
                            </td>
                            <td data-label="Aksi" class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.books.edit', $book) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.books.destroy', $book) }}"
                                          onsubmit="return confirm('Hapus buku {{ addslashes($book->title) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                                Tidak ada buku yang cocok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $books->links('pagination::bootstrap-5') }}
    </div>
@endsection