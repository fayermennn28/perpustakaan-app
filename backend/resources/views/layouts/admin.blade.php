<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') - Perpustakaan</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

    {{-- Pasang tema sebelum halaman tampil supaya tidak berkedip --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme');
                if (!t) {
                    t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="sidebar">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            <span class="brand-icon"><i class="bi bi-book-half"></i></span>
            <span class="brand-text">
                <span class="brand-name">Perpustakaan</span>
                <span class="brand-sub">Panel Admin</span>
            </span>
        </a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-sidebar-toggle aria-label="Tutup"></button>
    </div>

    <div class="menu-scroll">
        <div class="menu-label">Utama</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
               href="{{ route('admin.dashboard') }}">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <a class="nav-link {{ request()->routeIs('admin.loans.*') ? 'active' : '' }}"
               href="{{ route('admin.loans.index') }}">
                <i class="bi bi-arrow-left-right"></i> Transaksi
                @if (($pendingCount ?? 0) > 0)
                    <span class="badge rounded-pill bg-warning text-dark ms-auto">{{ $pendingCount }}</span>
                @endif
            </a>
        </nav>

        <div class="menu-label">Data master</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link {{ request()->routeIs('admin.books.*') ? 'active' : '' }}"
               href="{{ route('admin.books.index') }}">
                <i class="bi bi-journal-bookmark"></i> Buku
            </a>
            <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
               href="{{ route('admin.categories.index') }}">
                <i class="bi bi-tags"></i> Kategori
            </a>
            <a class="nav-link {{ request()->routeIs('admin.members.*') ? 'active' : '' }}"
               href="{{ route('admin.members.index') }}">
                <i class="bi bi-people"></i> Anggota
            </a>
        </nav>

        <div class="menu-label">Laporan</div>
        <nav class="nav flex-column gap-1">
            <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
               href="{{ route('admin.reports.loans') }}">
                <i class="bi bi-file-earmark-bar-graph"></i> Laporan transaksi
            </a>
        </nav>
    </div>

    <div class="pt-3">
        <div class="sidebar-user">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            <div class="overflow-hidden">
                <div class="text-white fw-semibold small text-truncate">{{ auth()->user()->name }}</div>
                <div class="text-truncate" style="font-size: .72rem; color: rgba(255,255,255,.6);">Administrator</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-sm w-100 logout-btn py-2">
                <i class="bi bi-box-arrow-right me-1"></i> Keluar
            </button>
        </form>
    </div>
</aside>

<div class="content">
    <header class="topbar">
        <button type="button" class="icon-btn d-lg-none" data-sidebar-toggle aria-label="Buka menu">
            <i class="bi bi-list"></i>
        </button>

        <div class="topbar-title d-md-none text-truncate">
            @yield('title', 'Admin')
        </div>

        <!-- Nama Sistem -->
        <div class="system-badge d-none d-md-flex align-items-center">
            <i class="bi bi-book-half"></i>
            <span>SISTEM PERPUSTAKAAN</span>
        </div>

        <!-- Menu Kanan -->
        <div class="ms-auto d-flex align-items-center gap-2">
            <a href="{{ route('admin.loans.index') }}" class="icon-btn" title="Pengajuan menunggu">
                <i class="bi bi-bell"></i>
                @if (($pendingCount ?? 0) > 0)
                    <span class="notif-dot"></span>
                @endif
            </a>

            <button type="button" class="icon-btn" id="themeToggle" aria-label="Ganti tema"></button>

            <div class="user-chip d-none d-sm-flex">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div>
                    <div class="name">{{ auth()->user()->name }}</div>
                    <div class="role">Administrator</div>
                </div>
            </div>
        </div>
    </header>


    <main class="page">
        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
                <i class="bi bi-check-circle me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
                <i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
<script>
    // Sidebar (HP/tablet)
    (function () {
        var sidebar = document.getElementById('sidebar');
        var backdrop = document.getElementById('sidebarBackdrop');

        function setOpen(open) {
            sidebar.classList.toggle('open', open);
            backdrop.classList.toggle('show', open);
            document.body.classList.toggle('overflow-hidden', open);
        }

        document.querySelectorAll('[data-sidebar-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setOpen(!sidebar.classList.contains('open'));
            });
        });
        backdrop.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
        window.addEventListener('resize', function () { if (window.innerWidth >= 992) setOpen(false); });
    })();

    // Tombol tema terang/gelap
    (function () {
        var btn = document.getElementById('themeToggle');
        var root = document.documentElement;

        function paint() {
            var dark = root.getAttribute('data-bs-theme') === 'dark';
            btn.innerHTML = dark ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
        }

        btn.addEventListener('click', function () {
            var next = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('theme', next); } catch (e) {}
            paint();
            document.dispatchEvent(new Event('themechange'));
        });

        paint();
    })();
    // Pindahkan modal ke <body> agar tidak terjebak di dalam panel kaca
        document.addEventListener('show.bs.modal', function (event) {
            if (event.target.parentElement !== document.body) {
            document.body.appendChild(event.target);
            }
        });
</script>
</body>
</html>