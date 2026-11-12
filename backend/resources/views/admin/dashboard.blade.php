@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    @php
        $hour = now()->hour;
        $greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
        $firstName = explode(' ', auth()->user()->name)[0];
        $totalPending = $pendingLoans + $pendingReturns;
        $statusTotal = collect($statusChart)->sum('value');
        $rupiah = fn ($n) => 'Rp ' . number_format($n, 0, ',', '.');

        $metrics = [
            ['Total Buku',        $totalBooks,   'bi-journal-bookmark', 'primary', $availableBooks . ' buku tersedia',         route('admin.books.index')],
            ['Total Anggota',     $totalMembers, 'bi-people',           'success', $activeMembers . ' akun aktif',             route('admin.members.index')],
            ['Sedang Dipinjam',   $activeLoans,  'bi-arrow-left-right', 'info',    'Belum dikembalikan',                      route('admin.loans.index', ['status' => 'dipinjam'])],
            ['Pengajuan Pinjam',  $pendingLoans, 'bi-hourglass-split',  'warning', 'Menunggu persetujuan',                    route('admin.loans.index', ['status' => 'diajukan'])],
            ['Pengajuan Kembali', $pendingReturns, 'bi-box-arrow-in-down', 'primary', 'Menunggu verifikasi',                  route('admin.loans.index', ['status' => 'pengembalian_diajukan'])],
            ['Terlambat',         $overdueCount, 'bi-alarm',            'danger',  'Perlu ditindaklanjuti',                   route('admin.loans.index', ['status' => 'terlambat'])],
            ['Denda Bulan Ini',   $rupiah($finesThisMonth), 'bi-cash-coin', 'warning', 'Dari buku yang dikembalikan',        route('admin.reports.loans')],
            ['Tepat Waktu',       $onTimeRate === null ? '-' : $onTimeRate . '%', 'bi-patch-check', 'success', 'Pengembalian tanpa denda', route('admin.reports.loans')],
        ];
    @endphp

    {{-- Sapaan + aksi --}}
    <div class="d-flex flex-wrap gap-3 justify-content-between align-items-start mb-4">
        <div>
            <h3 class="fw-bold mb-1">{{ $greeting }}, {{ $firstName }}</h3>
            <div class="text-muted">
                {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                &middot;
                @if ($totalPending > 0)
                    {{ $totalPending }} pengajuan menunggu tindakan
                @else
                    semua pengajuan sudah ditangani
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.books.create') }}" class="btn btn-glass">
                <i class="bi bi-plus-lg me-1"></i>Tambah buku
            </a>
            <a href="{{ route('admin.members.create') }}" class="btn btn-glass">
                <i class="bi bi-person-plus me-1"></i>Tambah anggota
            </a>
            <a href="{{ route('admin.loans.index', ['status' => 'diajukan']) }}" class="btn btn-primary">
                <i class="bi bi-hourglass-split me-1"></i>Proses pengajuan
            </a>
        </div>
    </div>

    {{-- Grafik + donut --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card-soft h-100 p-3 p-md-4">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <div>
                        <div class="fw-semibold">Aktivitas peminjaman</div>
                        <div class="text-muted small">Pengajuan dan pengembalian per hari</div>
                    </div>
                    <div class="segmented">
                        <button type="button" data-range="7" class="active">7 hari</button>
                        <button type="button" data-range="14">14 hari</button>
                        <button type="button" data-range="30">30 hari</button>
                    </div>
                </div>
                <div style="height: 260px;"><canvas id="loanChart"></canvas></div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card-soft h-100 p-3 p-md-4">
                <div class="fw-semibold">Status transaksi</div>
                <div class="text-muted small mb-3">Seluruh transaksi yang tercatat</div>

                <div class="donut-wrap mb-3">
                    <canvas id="statusChart"></canvas>
                    <div class="donut-center">
                        <div class="num">{{ $statusTotal }}</div>
                        <div class="cap">transaksi</div>
                    </div>
                </div>

                @foreach ($statusChart as $row)
                    <div class="legend-row">
                        <span class="legend-dot" style="background: {{ $row['color'] }};"></span>
                        <span class="flex-grow-1">{{ $row['label'] }}</span>
                        <span class="fw-semibold">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 8 kartu metrik --}}
    <div class="metric-grid mb-3">
        @foreach ($metrics as [$label, $value, $icon, $color, $note, $url])
            <a href="{{ $url }}" class="card-soft metric">
                <div class="metric-icon {{ $color }}"><i class="bi {{ $icon }}"></i></div>
                <div class="metric-value">{{ $value }}</div>
                <div class="metric-label">{{ $label }}</div>
                <div class="metric-note">{{ $note }}</div>
            </a>
        @endforeach
    </div>

    {{-- Buku terpopuler, stok menipis, terlambat --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card-soft h-100">
                <div class="px-4 py-3 border-bottom fw-semibold" style="border-color: var(--border) !important;">Buku terpopuler</div>
                <div class="p-3">
                    @forelse ($popularBooks as $i => $book)
                        <div class="d-flex align-items-center gap-3 py-2">
                            <div class="rank-no">{{ $i + 1 }}</div>
                            @if ($book->cover)
                                <img src="{{ asset('storage/' . $book->cover) }}" alt="" class="cover-thumb">
                            @else
                                <div class="cover-thumb"><i class="bi bi-book"></i></div>
                            @endif
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-medium text-truncate">{{ $book->title }}</div>
                                <div class="small text-muted text-truncate">{{ $book->author }}</div>
                            </div>
                            <span class="badge rounded-pill text-bg-secondary">{{ $book->loans_count }}x</span>
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-bar-chart fs-1 d-block mb-2"></i>
                            Belum ada data peminjaman.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-soft h-100">
                <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--border) !important;">
                    <div class="fw-semibold">Stok menipis</div>
                    <a href="{{ route('admin.books.index') }}" class="small text-decoration-none">Kelola</a>
                </div>
                <div class="p-3">
                    @forelse ($lowStockBooks as $book)
                        <div class="d-flex align-items-center justify-content-between gap-3 py-2">
                            <div class="overflow-hidden">
                                <div class="fw-medium text-truncate">{{ $book->title }}</div>
                                <div class="small text-muted text-truncate">{{ $book->author }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                @if ($book->stock === 0)
                                    <span class="badge rounded-pill text-bg-danger">Habis</span>
                                @else
                                    <span class="badge rounded-pill text-bg-warning">Sisa {{ $book->stock }}</span>
                                @endif
                                <a href="{{ route('admin.books.edit', $book) }}" class="btn btn-sm btn-outline-primary" title="Ubah stok">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-check-circle fs-1 d-block mb-2"></i>
                            Semua stok buku aman.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-soft h-100">
                <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--border) !important;">
                    <div class="fw-semibold">Peminjaman terlambat</div>
                    <a href="{{ route('admin.loans.index', ['status' => 'terlambat']) }}" class="small text-decoration-none">Lihat semua</a>
                </div>
                <div class="p-3">
                    @forelse ($overdueLoans as $loan)
                        <div class="d-flex align-items-center justify-content-between gap-3 py-2">
                            <div class="overflow-hidden">
                                <div class="fw-medium text-truncate">{{ $loan->user->name }}</div>
                                <div class="small text-muted text-truncate">{{ $loan->book->title }}</div>
                            </div>
                            <div class="text-end flex-shrink-0">
                                <span class="badge rounded-pill text-bg-danger">{{ $loan->daysLate() }} hari</span>
                                <div class="small text-muted mt-1">{{ $loan->due_date->format('d M Y') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-emoji-smile fs-1 d-block mb-2"></i>
                            Tidak ada yang terlambat.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Menunggu tindakan --}}
    <div class="card-soft overflow-hidden">
        <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center" style="border-color: var(--border) !important;">
            <div class="fw-semibold">Menunggu tindakan</div>
            <a href="{{ route('admin.loans.index') }}" class="btn btn-sm btn-outline-primary">
                Proses transaksi <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-stack mb-0">
                <thead>
                    <tr>
                        <th>Anggota</th>
                        <th>Buku</th>
                        <th>Jenis</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentLoans as $loan)
                        @php $isReturn = $loan->status === 'pengembalian_diajukan'; @endphp
                        <tr>
                            <td data-label="Anggota">
                                <div class="text-md-start">
                                    <div class="fw-medium">{{ $loan->user->name }}</div>
                                    <div class="text-muted small">{{ $loan->user->member_code ?? '-' }}</div>
                                </div>
                            </td>
                            <td data-label="Buku">{{ $loan->book->title }}</td>
                            <td data-label="Jenis">
                                <span class="badge rounded-pill {{ $isReturn ? 'text-bg-info' : 'text-bg-warning' }}">
                                    {{ $isReturn ? 'Pengajuan kembali' : 'Pengajuan pinjam' }}
                                </span>
                            </td>
                            <td data-label="Tanggal" class="text-muted">
                                {{ ($isReturn ? $loan->return_requested_at : $loan->requested_at)?->format('d M Y, H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada pengajuan yang perlu diproses.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            var labels = @json($chart['labels']);
            var requested = @json($chart['requested']);
            var returned = @json($chart['returned']);
            var range = 7;

            function css(name) {
                return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
            }

            // ----- Grafik batang -----
            var bar = new Chart(document.getElementById('loanChart'), {
                type: 'bar',
                data: {
                    labels: labels.slice(-range),
                    datasets: [
                        {
                            label: 'Diajukan',
                            data: requested.slice(-range),
                            backgroundColor: '#38bdf8',
                            borderRadius: 8,
                            maxBarThickness: 24
                        },
                        {
                            label: 'Dikembalikan',
                            data: returned.slice(-range),
                            backgroundColor: '#34d399',
                            borderRadius: 8,
                            maxBarThickness: 24
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: css('--muted'), usePointStyle: true, boxWidth: 8 } }
                    },
                    scales: {
                        x: { ticks: { color: css('--muted'), maxRotation: 0, autoSkip: true }, grid: { display: false } },
                        y: { beginAtZero: true, ticks: { color: css('--muted'), precision: 0 }, grid: { color: css('--border') } }
                    }
                }
            });

            document.querySelectorAll('[data-range]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var n = parseInt(btn.dataset.range, 10);

                    document.querySelectorAll('[data-range]').forEach(function (b) {
                        b.classList.toggle('active', b === btn);
                    });

                    bar.data.labels = labels.slice(-n);
                    bar.data.datasets[0].data = requested.slice(-n);
                    bar.data.datasets[1].data = returned.slice(-n);
                    bar.update();
                });
            });

            // ----- Donut status -----
            var values = @json(collect($statusChart)->pluck('value'));
            var colors = @json(collect($statusChart)->pluck('color'));
            var names  = @json(collect($statusChart)->pluck('label'));
            var empty = values.every(function (v) { return v === 0; });

            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: empty ? ['Belum ada data'] : names,
                    datasets: [{
                        data: empty ? [1] : values,
                        backgroundColor: empty ? ['rgba(148, 163, 184, .35)'] : colors,
                        borderWidth: 0,
                        spacing: empty ? 0 : 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: { legend: { display: false }, tooltip: { enabled: !empty } }
                }
            });

            // Perbarui warna teks grafik saat tema diganti
            document.addEventListener('themechange', function () {
                bar.options.plugins.legend.labels.color = css('--muted');
                bar.options.scales.x.ticks.color = css('--muted');
                bar.options.scales.y.ticks.color = css('--muted');
                bar.options.scales.y.grid.color = css('--border');
                bar.update();
            });
        })();
    </script>
@endpush