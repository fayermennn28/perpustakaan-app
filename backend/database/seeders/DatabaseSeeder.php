<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---------- Akun ----------
        User::create([
            'name'     => 'Admin Perpustakaan',
            'email'    => 'admin@perpus.test',
            'password' => 'password123',
            'role'     => 'admin',
        ]);

        $members = collect([
            ['Anggota Contoh', 'anggota@perpus.test', '081234567801'],
            ['Budi Santoso',   'budi@perpus.test',    '081234567802'],
            ['Citra Lestari',  'citra@perpus.test',   '081234567803'],
        ])->values()->map(fn (array $m, int $i) => User::create([
            'name'        => $m[0],
            'email'       => $m[1],
            'phone'       => $m[2],
            'address'     => 'Jl. Contoh No. ' . ($i + 1) . ', Jawa Barat',
            'password'    => 'password123',
            'role'        => 'anggota',
            'member_code' => 'AGT-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
        ]));

        [$anggota, $budi, $citra] = $members->all();

        // ---------- Kategori dan buku ----------
        $categories = collect(['Teknologi', 'Fiksi', 'Sains', 'Sejarah', 'Bisnis'])
            ->mapWithKeys(fn (string $name) => [$name => Category::create(['name' => $name])]);

        $catalog = [
            ['Belajar Flutter dari Nol',     'Rina Pratama',      'Teknologi', 5, 2024],
            ['Dasar-Dasar Laravel',          'Dimas Saputra',     'Teknologi', 4, 2023],
            ['Pemrograman Web Modern',       'Salsa Anindya',     'Teknologi', 3, 2022],
            ['Basis Data untuk Pemula',      'Hendra Wijaya',     'Teknologi', 2, 2021],
            ['Senja di Ujung Dermaga',       'Maya Kusuma',       'Fiksi',     3, 2020],
            ['Rumah di Balik Kabut',         'Bayu Nugraha',      'Fiksi',     2, 2019],
            ['Mengenal Alam Semesta',        'Prof. Arif Rahman', 'Sains',     4, 2018],
            ['Dunia Mikroba',                'Dewi Safitri',      'Sains',     1, 2021],
            ['Jejak Kerajaan Nusantara',     'Rudi Hartono',      'Sejarah',   3, 2017],
            ['Perang dan Perdamaian Asia',   'Lina Marlina',      'Sejarah',   2, 2016],
            ['Memulai Bisnis Digital',       'Fajar Ramadhan',    'Bisnis',    5, 2023],
            ['Manajemen Keuangan Pribadi',   'Tania Permata',     'Bisnis',    1, 2022],
        ];

        $books = collect($catalog)->map(fn (array $b, int $i) => Book::create([
            'category_id' => $categories[$b[2]]->id,
            'title'       => $b[0],
            'author'      => $b[1],
            'publisher'   => 'Penerbit Contoh',
            'year'        => $b[4],
            'isbn'        => sprintf('978-602-%04d-%02d-%d', 1000 + $i, 10 + $i, $i % 10),
            'stock'       => $b[3],
            'description' => 'Buku contoh untuk demo sistem perpustakaan.',
        ]))->values();

        // ---------- Transaksi contoh ----------
        $loan = function (User $user, Book $book, string $status, array $extra = []): void {
            Loan::create(array_merge([
                'user_id'      => $user->id,
                'book_id'      => $book->id,
                'status'       => $status,
                'requested_at' => now()->subDays(8),
            ], $extra));

            // Buku yang sedang di tangan anggota mengurangi stok
            if (in_array($status, [Loan::STATUS_DIPINJAM, Loan::STATUS_PENGEMBALIAN_DIAJUKAN], true)) {
                $book->decrement('stock');
            }
        };

        // Anggota Contoh
        $loan($anggota, $books[0], Loan::STATUS_DIPINJAM, [
            'requested_at' => now()->subDays(2),
            'approved_at'  => now()->subDays(2),
            'due_date'     => today()->addDays(5),
        ]);
        $loan($anggota, $books[1], Loan::STATUS_DIAJUKAN, [
            'requested_at' => now()->subHours(3),
        ]);
        $loan($anggota, $books[2], Loan::STATUS_DIKEMBALIKAN, [
            'requested_at' => now()->subDays(20),
            'approved_at'  => now()->subDays(19),
            'due_date'     => today()->subDays(12),
            'returned_at'  => now()->subDays(14),
            'fine_amount'  => 0,
        ]);

        // Budi (satu terlambat 3 hari)
        $loan($budi, $books[3], Loan::STATUS_DIPINJAM, [
            'requested_at' => now()->subDays(10),
            'approved_at'  => now()->subDays(10),
            'due_date'     => today()->subDays(3),
        ]);
        $loan($budi, $books[4], Loan::STATUS_PENGEMBALIAN_DIAJUKAN, [
            'requested_at'        => now()->subDays(6),
            'approved_at'         => now()->subDays(6),
            'due_date'            => today()->addDays(1),
            'return_requested_at' => now()->subHours(5),
        ]);
        $loan($budi, $books[5], Loan::STATUS_DITOLAK, [
            'requested_at' => now()->subDays(4),
            'admin_note'   => 'Buku sedang dalam perbaikan.',
        ]);

        // Citra (dikembalikan terlambat 2 hari, kena denda)
        $loan($citra, $books[6], Loan::STATUS_DIKEMBALIKAN, [
            'requested_at' => now()->subDays(25),
            'approved_at'  => now()->subDays(24),
            'due_date'     => today()->subDays(17),
            'returned_at'  => now()->subDays(15),
            'fine_amount'  => 2000,
        ]);
        $loan($citra, $books[10], Loan::STATUS_DIPINJAM, [
            'requested_at' => now()->subDays(1),
            'approved_at'  => now()->subDays(1),
            'due_date'     => today()->addDays(6),
        ]);
    }
}