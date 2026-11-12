<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $members = User::where('role', 'anggota')
            ->withCount([
                'loans as active_loans_count' => fn ($q) => $q->whereIn('status', Loan::ACTIVE_STATUSES),
            ])
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('member_code', 'like', "%{$search}%");
                });
            })
            ->when($request->query('status') === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when($request->query('status') === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.members.index', ['members' => $members]);
    }

    public function create(): View
    {
        return view('admin.members.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        unset($data['photo']);
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('members', 'public');
        }

        $data['role'] = 'anggota';
        $data['member_code'] = User::nextMemberCode();
        $data['is_active'] = true;

        $member = User::create($data);

        return redirect()->route('admin.members.index')
            ->with('success', "Anggota {$member->name} berhasil ditambahkan dengan kode {$member->member_code}.");
    }

    public function show(User $member): View
    {
        return view('admin.members.show', [
            'member' => $member,
            'loans'  => $member->loans()->with('book')->latest('requested_at')->paginate(10),
        ]);
    }

    public function edit(User $member): View
    {
        return view('admin.members.edit', ['member' => $member]);
    }

    public function update(Request $request, User $member): RedirectResponse
    {
        $data = $this->validated($request, $member);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        unset($data['photo']);
        if ($request->hasFile('photo')) {
            // Ganti foto: hapus file lama, simpan yang baru
            if ($member->photo) {
                Storage::disk('public')->delete($member->photo);
            }
            $data['photo'] = $request->file('photo')->store('members', 'public');
        } elseif ($request->boolean('remove_photo') && $member->photo) {
            // Hapus foto tanpa menggantinya
            Storage::disk('public')->delete($member->photo);
            $data['photo'] = null;
        }

        $member->update($data);

        return redirect()->route('admin.members.index')
            ->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function toggle(User $member): RedirectResponse
    {
        $member->update(['is_active' => ! $member->is_active]);

        if (! $member->is_active) {
            // Cabut semua token supaya sesi mobile langsung berakhir
            $member->tokens()->delete();

            return back()->with('success', 'Akun dinonaktifkan dan sesi login mobile dihentikan.');
        }

        return back()->with('success', 'Akun berhasil diaktifkan.');
    }

    public function destroy(User $member): RedirectResponse
    {
        if ($member->loans()->exists()) {
            return back()->with('error',
                'Anggota punya riwayat transaksi sehingga tidak bisa dihapus. Nonaktifkan akunnya saja.');
        }

        if ($member->photo) {
            Storage::disk('public')->delete($member->photo);
        }

        $member->tokens()->delete();
        $member->delete();

        return redirect()->route('admin.members.index')
            ->with('success', 'Anggota berhasil dihapus.');
    }

    private function validated(Request $request, ?User $member = null): array
    {
        return $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($member?->id)],
            'phone'    => ['nullable', 'string', 'max:20'],
            'address'  => ['nullable', 'string', 'max:500'],
            'password' => [$member ? 'nullable' : 'required', 'string', 'min:8'],
            'photo'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }
}