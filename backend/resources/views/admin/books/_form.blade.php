<div class="row g-4">
    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-medium">Judul <span class="text-danger">*</span></label>
                <input type="text" name="title" value="{{ old('title', $book?->title) }}"
                       class="form-control @error('title') is-invalid @enderror" required>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Penulis <span class="text-danger">*</span></label>
                <input type="text" name="author" value="{{ old('author', $book?->author) }}"
                       class="form-control @error('author') is-invalid @enderror" required>
                @error('author') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Kategori <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected(old('category_id', $book?->category_id) == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Penerbit</label>
                <input type="text" name="publisher" value="{{ old('publisher', $book?->publisher) }}"
                       class="form-control @error('publisher') is-invalid @enderror">
                @error('publisher') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">ISBN</label>
                <input type="text" name="isbn" value="{{ old('isbn', $book?->isbn) }}"
                       class="form-control @error('isbn') is-invalid @enderror">
                @error('isbn') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Tahun terbit</label>
                <input type="number" name="year" value="{{ old('year', $book?->year) }}"
                       class="form-control @error('year') is-invalid @enderror" min="1000" max="{{ date('Y') }}">
                @error('year') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Stok <span class="text-danger">*</span></label>
                <input type="number" name="stock" value="{{ old('stock', $book?->stock ?? 1) }}"
                       class="form-control @error('stock') is-invalid @enderror" min="0" required>
                @error('stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-medium">Deskripsi</label>
                <textarea name="description" rows="4"
                          class="form-control @error('description') is-invalid @enderror">{{ old('description', $book?->description) }}</textarea>
                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <label class="form-label fw-medium">Cover</label>
        <div class="card-soft p-3 text-center">
            <img id="coverPreview"
                 src="{{ $book?->cover ? asset('storage/' . $book->cover) : '' }}"
                 alt="Cover"
                 class="img-fluid rounded mb-3 {{ $book?->cover ? '' : 'd-none' }}"
                 style="max-height: 280px; object-fit: cover;">
            <div id="coverPlaceholder" class="text-muted py-5 {{ $book?->cover ? 'd-none' : '' }}">
                <i class="bi bi-image fs-1 d-block mb-2"></i>
                Belum ada cover
            </div>

            <input type="file" name="cover" id="coverInput" accept="image/*"
                   class="form-control @error('cover') is-invalid @enderror">
            @error('cover') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>Simpan
    </button>
    <a href="{{ route('admin.books.index') }}" class="btn btn-light">Batal</a>
</div>

<script>
    document.getElementById('coverInput').addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function (ev) {
            var img = document.getElementById('coverPreview');
            img.src = ev.target.result;
            img.classList.remove('d-none');
            document.getElementById('coverPlaceholder').classList.add('d-none');
        };
        reader.readAsDataURL(file);
    });
</script>