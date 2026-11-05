<?php

namespace App\Http\Controllers;

use App\Support\PasswordRecoveryStore;
use App\Support\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordRecoveryController extends Controller
{
    public function __construct(private readonly PasswordRecoveryStore $store) {}

    public function create()
    {
        return view('auth.recovery', [
            'mode' => 'request',
            'sekolah' => Sekolah::pengaturan(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:120'],
            'verification' => ['required', 'string', 'max:120'],
        ], [], [
            'identifier' => 'Username atau email',
            'verification' => 'Informasi verifikasi',
        ]);
        $reference = Str::random(40);

        $this->store->mutate(function (array &$requests) use ($data, $reference) {
            $requests[] = [
                'id' => $reference,
                'identifier' => Crypt::encryptString(trim($data['identifier'])),
                'verification' => Crypt::encryptString(trim($data['verification'])),
                'status' => 'pending',
                'createdAt' => now()->toIso8601String(),
            ];
        });

        return redirect()->route('password.recovery.status', ['reference' => $reference]);
    }

    public function status(string $reference)
    {
        $this->expireApprovedCodes();
        $recovery = collect($this->store->all())->firstWhere('id', $reference);
        abort_if($recovery === null, 404);

        return view('auth.recovery', [
            'mode' => 'status',
            'recovery' => $recovery,
            'sekolah' => Sekolah::pengaturan(),
        ]);
    }

    public function resetForm(string $reference)
    {
        $recovery = collect($this->store->all())->firstWhere('id', $reference);
        abort_if($recovery === null, 404);

        return view('auth.recovery', [
            'mode' => 'reset',
            'reference' => $reference,
            'sekolah' => Sekolah::pengaturan(),
        ]);
    }

    public function reset(Request $request, string $reference)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'code' => 'Kode pemulihan',
            'password' => 'Password baru',
        ]);

        $result = $this->store->mutate(function (array &$requests) use ($reference, $data) {
            foreach ($requests as &$recovery) {
                if (($recovery['id'] ?? null) !== $reference) {
                    continue;
                }

                if (($recovery['status'] ?? null) !== 'approved') {
                    return 'unavailable';
                }

                if (strtotime((string) ($recovery['codeExpiresAt'] ?? '')) <= time()) {
                    $recovery['status'] = 'expired';
                    unset($recovery['codeHash'], $recovery['codeExpiresAt']);

                    return 'expired';
                }

                if (($recovery['attempts'] ?? 0) >= 5) {
                    $recovery['status'] = 'expired';
                    unset($recovery['codeHash'], $recovery['codeExpiresAt'], $recovery['attempts']);

                    return 'locked';
                }

                if (! Hash::check(strtoupper(trim($data['code'])), (string) ($recovery['codeHash'] ?? ''))) {
                    $recovery['attempts'] = ($recovery['attempts'] ?? 0) + 1;
                    if ($recovery['attempts'] >= 5) {
                        $recovery['status'] = 'expired';
                        unset($recovery['codeHash'], $recovery['codeExpiresAt']);
                    }

                    return 'invalid';
                }

                $identifier = Crypt::decryptString($recovery['identifier']);
                $accounts = Sekolah::akun();
                $accountIndex = null;
                foreach ($accounts as $index => $account) {
                    if (
                        strcasecmp((string) ($account['user'] ?? ''), $identifier) === 0
                        || strcasecmp((string) ($account['email'] ?? ''), $identifier) === 0
                    ) {
                        $accountIndex = $index;
                        break;
                    }
                }

                if ($accountIndex === null || ($accounts[$accountIndex]['aktif'] ?? true) !== true) {
                    $recovery['status'] = 'expired';
                    unset($recovery['codeHash'], $recovery['codeExpiresAt'], $recovery['attempts']);

                    return 'unavailable';
                }
                if (($accounts[$accountIndex]['role'] ?? null) === 'guru') {
                    $teacher = Sekolah::cariGuru($accounts[$accountIndex]['guruId'] ?? null);
                    if (! $teacher || ($teacher['status'] ?? null) !== 'Aktif') {
                        $recovery['status'] = 'expired';
                        unset($recovery['codeHash'], $recovery['codeExpiresAt'], $recovery['attempts']);

                        return 'unavailable';
                    }
                }

                $accounts[$accountIndex]['pass'] = Hash::make($data['password']);
                unset($accounts[$accountIndex]['rememberTokenHash'], $accounts[$accountIndex]['rememberExpiresAt']);
                Sekolah::put('akun', $accounts);

                $recovery['status'] = 'completed';
                $recovery['completedAt'] = now()->toIso8601String();
                unset($recovery['codeHash'], $recovery['codeExpiresAt'], $recovery['attempts']);

                return 'completed';
            }
            unset($recovery);

            return 'not_found';
        });

        if ($result !== 'completed') {
            $message = match ($result) {
                'invalid' => 'Kode pemulihan tidak sesuai. Periksa kembali kode dari administrator.',
                'expired', 'locked' => 'Kode pemulihan sudah kedaluwarsa. Ajukan permintaan baru kepada administrator.',
                default => 'Permintaan pemulihan tidak tersedia atau akun perlu diverifikasi kembali.',
            };

            return back()->withErrors(['code' => $message]);
        }

        return redirect()->route('login')->with('status', 'Password berhasil diperbarui. Silakan masuk dengan password baru.');
    }

    public function adminIndex()
    {
        $this->expireApprovedCodes();
        $requests = collect($this->store->all())
            ->sortByDesc('createdAt')
            ->take(100)
            ->map(function (array $recovery) {
                $recovery['identifierValue'] = Crypt::decryptString($recovery['identifier']);
                $recovery['verificationValue'] = Crypt::decryptString($recovery['verification']);
                $account = $this->findAccount($recovery['identifierValue']);
                $recovery['registeredEmail'] = $account['email'] ?? null;

                return $recovery;
            })
            ->values();

        return view('admin.pemulihan-akun', ['requests' => $requests]);
    }

    public function approve(string $reference)
    {
        $code = Str::upper(Str::random(12));
        $result = $this->store->mutate(function (array &$requests) use ($reference, $code) {
            foreach ($requests as &$recovery) {
                if (($recovery['id'] ?? null) !== $reference || ($recovery['status'] ?? null) !== 'pending') {
                    continue;
                }

                $identifier = Crypt::decryptString($recovery['identifier']);
                $account = $this->findAccount($identifier);
                if (! $account || ($account['aktif'] ?? true) !== true) {
                    return 'account_unavailable';
                }

                if (($account['role'] ?? null) === 'guru') {
                    $teacher = Sekolah::cariGuru($account['guruId'] ?? null);
                    if (! $teacher || ($teacher['status'] ?? null) !== 'Aktif') {
                        return 'account_unavailable';
                    }
                }

                $recovery['status'] = 'approved';
                $recovery['codeHash'] = Hash::make($code);
                $recovery['codeExpiresAt'] = now()->addMinutes(30)->toIso8601String();
                $recovery['attempts'] = 0;
                $recovery['reviewedAt'] = now()->toIso8601String();

                return 'approved';
            }
            unset($recovery);

            return 'not_pending';
        });

        if ($result !== 'approved') {
            $message = $result === 'account_unavailable'
                ? 'Akun tidak ditemukan atau tidak aktif. Permintaan tidak dapat disetujui.'
                : 'Permintaan tidak ditemukan atau sudah ditinjau.';

            return back()->withErrors(['recovery' => $message]);
        }

        return back()->with('recoveryCode', [
            'reference' => $reference,
            'code' => $code,
        ]);
    }

    public function reject(Request $request, string $reference)
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:300'],
        ]);

        $updated = $this->store->mutate(function (array &$requests) use ($reference, $request) {
            foreach ($requests as &$recovery) {
                if (($recovery['id'] ?? null) === $reference && ($recovery['status'] ?? null) === 'pending') {
                    $recovery['status'] = 'rejected';
                    $recovery['reviewReason'] = trim((string) $request->input('reason'));
                    $recovery['reviewedAt'] = now()->toIso8601String();

                    return true;
                }
            }
            unset($recovery);

            return false;
        });

        if (! $updated) {
            return back()->withErrors(['recovery' => 'Permintaan tidak ditemukan atau sudah ditinjau.']);
        }

        return back()->with('sukses', 'Permintaan pemulihan ditolak.');
    }

    private function findAccount(string $identifier): ?array
    {
        foreach (Sekolah::akun() as $account) {
            if (
                strcasecmp((string) ($account['user'] ?? ''), $identifier) === 0
                || strcasecmp((string) ($account['email'] ?? ''), $identifier) === 0
            ) {
                return $account;
            }
        }

        return null;
    }

    private function expireApprovedCodes(): void
    {
        $this->store->mutate(function (array &$requests) {
            foreach ($requests as &$recovery) {
                if (
                    ($recovery['status'] ?? null) === 'approved'
                    && strtotime((string) ($recovery['codeExpiresAt'] ?? '')) <= time()
                ) {
                    $recovery['status'] = 'expired';
                    unset($recovery['codeHash'], $recovery['codeExpiresAt'], $recovery['attempts']);
                }
            }
            unset($recovery);
        });
    }
}
