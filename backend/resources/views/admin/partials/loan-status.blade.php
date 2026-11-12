@php
    [$color, $label] = match ($status) {
        'diajukan'              => ['warning', 'Diajukan'],
        'ditolak'               => ['danger', 'Ditolak'],
        'dipinjam'              => ['primary', 'Dipinjam'],
        'pengembalian_diajukan' => ['info', 'Pengajuan kembali'],
        'dikembalikan'          => ['success', 'Dikembalikan'],
        default                 => ['secondary', $status],
    };
@endphp
<span class="badge rounded-pill text-bg-{{ $color }}">{{ $label }}</span>