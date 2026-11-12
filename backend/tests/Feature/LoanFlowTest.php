<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LoanFlowTest extends TestCase
{
    use RefreshDatabase;

    private function book(int $stock = 2): Book
    {
        $category = Category::firstOrCreate(['name' => 'Teknologi']);

        return Book::create([
            'category_id' => $category->id,
            'title'       => 'Buku Uji ' . uniqid(),
            'author'      => 'Penulis Uji',
            'stock'       => $stock,
        ]);
    }

    private function member(): User
    {
        return User::factory()->create(['role' => 'anggota']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_hanya_anggota_aktif_yang_bisa_login_lewat_api(): void
    {
        $member = $this->member();
        $admin = $this->admin();

        $this->postJson('/api/login', ['email' => $member->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'photo_url']]);

        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertForbidden();

        $member->update(['is_active' => false]);

        $this->postJson('/api/login', ['email' => $member->email, 'password' => 'password'])
            ->assertForbidden();
    }

    public function test_pengajuan_ditolak_jika_stok_habis_atau_melebihi_batas(): void
    {
        Sanctum::actingAs($this->member());

        $this->postJson('/api/loans', ['book_id' => $this->book(0)->id])->assertStatus(422);

        foreach (range(1, 3) as $ignored) {
            $this->postJson('/api/loans', ['book_id' => $this->book()->id])->assertCreated();
        }

        $this->postJson('/api/loans', ['book_id' => $this->book()->id])->assertStatus(422);
    }

    public function test_anggota_bisa_mengajukan_pengembalian_buku_yang_dipinjam(): void
    {
        $member = $this->member();
        $loan = Loan::create([
            'user_id'      => $member->id,
            'book_id'      => $this->book()->id,
            'status'       => Loan::STATUS_DIPINJAM,
            'requested_at' => now(),
            'due_date'     => today()->addDays(7),
        ]);

        Sanctum::actingAs($member);

        $this->postJson("/api/loans/{$loan->id}/return")->assertOk();

        $this->assertSame(Loan::STATUS_PENGEMBALIAN_DIAJUKAN, $loan->fresh()->status);
    }

    public function test_anggota_tidak_bisa_mengajukan_pengembalian_milik_orang_lain(): void
    {
        $owner = $this->member();
        $loan = Loan::create([
            'user_id'      => $owner->id,
            'book_id'      => $this->book()->id,
            'status'       => Loan::STATUS_DIPINJAM,
            'requested_at' => now(),
            'due_date'     => today()->addDays(7),
        ]);

        Sanctum::actingAs($this->member());

        $this->postJson("/api/loans/{$loan->id}/return")->assertForbidden();
    }

    public function test_admin_menyetujui_pengajuan_mengurangi_stok_dan_mengisi_jatuh_tempo(): void
    {
        $book = $this->book(2);
        $loan = Loan::create([
            'user_id'      => $this->member()->id,
            'book_id'      => $book->id,
            'status'       => Loan::STATUS_DIAJUKAN,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.loans.approve', $loan))
            ->assertRedirect();

        $loan->refresh();

        $this->assertSame(Loan::STATUS_DIPINJAM, $loan->status);
        $this->assertSame(1, $book->fresh()->stock);
        $this->assertTrue(
            $loan->due_date->isSameDay(today()->addDays((int) config('library.loan_days')))
        );
    }

    public function test_persetujuan_gagal_jika_stok_habis(): void
    {
        $book = $this->book(0);
        $loan = Loan::create([
            'user_id'      => $this->member()->id,
            'book_id'      => $book->id,
            'status'       => Loan::STATUS_DIAJUKAN,
            'requested_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.loans.approve', $loan))
            ->assertSessionHas('error');

        $this->assertSame(Loan::STATUS_DIAJUKAN, $loan->fresh()->status);
        $this->assertSame(0, $book->fresh()->stock);
    }

    public function test_pengembalian_terlambat_dikenai_denda_dan_stok_bertambah(): void
    {
        $book = $this->book(1);
        $loan = Loan::create([
            'user_id'      => $this->member()->id,
            'book_id'      => $book->id,
            'status'       => Loan::STATUS_DIPINJAM,
            'requested_at' => now()->subDays(10),
            'approved_at'  => now()->subDays(10),
            'due_date'     => today()->subDays(3),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.loans.return', $loan))
            ->assertRedirect();

        $loan->refresh();

        $this->assertSame(Loan::STATUS_DIKEMBALIKAN, $loan->status);
        $this->assertSame(3 * (int) config('library.fine_per_day'), $loan->fine_amount);
        $this->assertSame(2, $book->fresh()->stock);
    }

    public function test_menonaktifkan_anggota_mencabut_semua_token(): void
    {
        $member = $this->member();
        $member->createToken('mobile');

        $this->assertSame(1, $member->tokens()->count());

        $this->actingAs($this->admin())
            ->patch(route('admin.members.toggle', $member))
            ->assertRedirect();

        $this->assertFalse($member->fresh()->is_active);
        $this->assertSame(0, $member->tokens()->count());
    }
}