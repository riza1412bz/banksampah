<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function formMasuk()
    {
        return view('auth.masuk');
    }

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [], [
            'email' => 'email',
            'password' => 'kata sandi',
        ]);

        if (! Auth::attempt($data, $request->boolean('ingat'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak cocok.',
            ]);
        }

        if (! $request->user()->aktif) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Akun ini sedang tidak aktif. Hubungi pengurus bank sampah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended($this->tujuanSetelahMasuk($request->user()));
    }

    public function formDaftar()
    {
        return view('auth.daftar');
    }

    public function daftar(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'jenis_nasabah' => ['sometimes', 'in:perorangan,corporate'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'name' => 'nama',
            'email' => 'email',
            'telepon' => 'nomor telepon',
            'alamat' => 'alamat',
            'jenis_nasabah' => 'jenis nasabah',
            'password' => 'kata sandi',
        ]);

        $jenis = $data['jenis_nasabah'] ?? User::JENIS_PERORANGAN;
        unset($data['jenis_nasabah']);

        $nasabah = User::create([
            ...$data,
            'role' => User::ROLE_NASABAH,
            'jenis_nasabah' => $jenis,
            'kode_nasabah' => $this->kodeNasabahBerikutnya($jenis),
            'aktif' => true,
        ]);

        Auth::login($nasabah);
        $request->session()->regenerate();

        return redirect()
            ->route('nasabah.beranda')
            ->with('sukses', "Selamat datang, {$nasabah->name}. Kode nasabahmu {$nasabah->kode_nasabah}.");
    }

    public function keluar(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('masuk')->with('sukses', 'Kamu sudah keluar.');
    }

    private function tujuanSetelahMasuk(User $user): string
    {
        return $user->isAdmin()
            ? route('admin.dashboard')
            : route('nasabah.beranda');
    }

    private function kodeNasabahBerikutnya(string $jenis = User::JENIS_PERORANGAN): string
    {
        $prefix = User::prefixUntukJenis($jenis);
        $like = $prefix.'-%';
        try {
            $terakhir = User::where('kode_nasabah', 'like', $like)
                ->lockForUpdate()
                ->orderByDesc('kode_nasabah')
                ->value('kode_nasabah');
        } catch (\Throwable) {
            $terakhir = User::where('kode_nasabah', 'like', $like)
                ->orderByDesc('kode_nasabah')
                ->value('kode_nasabah');
        }

        $urut = $terakhir ? ((int) substr($terakhir, -4)) + 1 : 1;

        return $prefix.'-'.str_pad((string) $urut, 4, '0', STR_PAD_LEFT);
    }
}
