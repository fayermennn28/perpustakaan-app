<?php

return [
    'max_loans'    => 3,     // maksimal peminjaman aktif per anggota
    'loan_days'    => 7,     // lama pinjam (hari)
    'fine_per_day' => 1000,  // denda per hari keterlambatan (Rp)
    'low_stock' => 2,   // buku dengan stok <= angka ini dianggap menipis
];