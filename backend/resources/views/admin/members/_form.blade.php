<div class="row g-4">
    <div class="col-lg-8">
        <div class="row g-3">
            @if ($member)
                <div class="col-md-6">
                    <label class="form-label fw-medium">Kode anggota</label>
                    <input type="text" class="form-control" value="{{ $member->member_code }}" disabled>
                </div>
                <div class="col-md-6 d-none d-md-block"></div>
            @endif

            <div class="col-md-6">
                <label class="form-label fw-medium">Nama lengkap <span class="text-danger">*</span></label>
                <input type="text" name="name" value="{{ old('name', $member?->name) }}"
                       class="form-control @error('name') is-invalid @enderror" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" value="{{ old('email', $member?->email) }}"
                       class="form-control @error('email') is-invalid @enderror" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">Telepon</label>
                <input type="text" name="phone" value="{{ old('phone', $member?->phone) }}"
                       class="form-control @error('phone') is-invalid @enderror">
                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
                <label class="form-label fw-medium">
                    {{ $member ? 'Password baru' : 'Password' }}
                    @unless ($member) <span class="text-danger">*</span> @endunless
                </label>
                <div class="input-group">
                    <input type="password" name="password" id="passwordInput"
                           class="form-control @error('password') is-invalid @enderror"
                           minlength="8" autocomplete="new-password" {{ $member ? '' : 'required' }}>
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword" aria-label="Tampilkan password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                <div class="form-text">
                    {{ $member ? 'Kosongkan jika tidak ingin mengubah password. Isi untuk mereset password.' : 'Minimal 8 karakter.' }}
                </div>
            </div>

            <div class="col-12">
                <label class="form-label fw-medium">Alamat</label>
                <textarea name="address" rows="3"
                          class="form-control @error('address') is-invalid @enderror">{{ old('address', $member?->address) }}</textarea>
                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <label class="form-label fw-medium">Foto anggota</label>
        <div class="card-soft p-3 text-center">
            <div class="d-flex justify-content-center mb-3">
                <img id="photoPreview" alt="Foto anggota"
                     class="rounded-circle {{ $member?->photo ? '' : 'd-none' }}"
                     style="width: 140px; height: 140px; object-fit: cover;"
                     @if ($member?->photo) src="{{ $member->photo_url }}" @endif>

                <div id="photoPlaceholder" class="avatar {{ $member?->photo ? 'd-none' : '' }}"
                     style="width: 140px; height: 140px; font-size: 3.2rem;">
                    <i class="bi bi-person"></i>
                </div>
            </div>

            <input type="file" name="photo" id="photoInput" accept="image/*"
                   class="form-control @error('photo') is-invalid @enderror">
            @error('photo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">JPG, PNG, atau WEBP. Maksimal 2 MB.</div>

            @if ($member?->photo)
                <div class="form-check text-start mt-3">
                    <input class="form-check-input" type="checkbox" name="remove_photo" id="removePhoto" value="1">
                    <label class="form-check-label" for="removePhoto">Hapus foto saat ini</label>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>Simpan
    </button>
    <a href="{{ route('admin.members.index') }}" class="btn btn-light">Batal</a>
</div>

<script>
    // Tampilkan / sembunyikan password
    document.getElementById('togglePassword').addEventListener('click', function () {
        var input = document.getElementById('passwordInput');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });

    // Preview foto sebelum disimpan
    document.getElementById('photoInput').addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        var reader = new FileReader();
        reader.onload = function (ev) {
            var img = document.getElementById('photoPreview');
            img.src = ev.target.result;
            img.classList.remove('d-none');
            document.getElementById('photoPlaceholder').classList.add('d-none');
        };
        reader.readAsDataURL(file);
    });
</script>