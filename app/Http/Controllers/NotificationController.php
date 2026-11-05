<?php

namespace App\Http\Controllers;

use App\Support\PasswordRecoveryStore;
use App\Support\Sekolah;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(private readonly PasswordRecoveryStore $passwordRecoveryStore) {}

    public function admin(): JsonResponse
    {
        $items = array_values(array_filter(
            Sekolah::absensiGuru(),
            fn (array $attendance) => ($attendance['status'] ?? null) === 'Mencurigakan'
                || (
                    in_array(($attendance['status'] ?? null), ['Izin', 'Sakit'], true)
                    && ($attendance['verifikasi'] ?? null) === 'Menunggu'
                )
        ));
        usort($items, fn (array $a, array $b) => strcmp(
            ($b['tgl'] ?? '').($b['jam'] ?? ''),
            ($a['tgl'] ?? '').($a['jam'] ?? '')
        ));

        $attendanceNotifications = array_map(function (array $attendance) {
            $guru = Sekolah::cariGuru($attendance['guruId'] ?? null) ?? [];
            $status = $attendance['status'] ?? 'Presensi';

            return [
                'id' => (string) ($attendance['id'] ?? ''),
                'title' => $status === 'Mencurigakan' ? 'Presensi mencurigakan' : 'Pengajuan '.strtolower($status),
                'message' => ($guru['nama'] ?? 'Guru').' · '.($attendance['tgl'] ?? '-').' '.($attendance['jam'] ?? ''),
                'url' => route('admin.absensi', ['tgl' => $attendance['tgl'] ?? null]),
            ];
        }, array_slice($items, 0, 10));

        $pendingPasswordRequests = collect($this->passwordRecoveryStore->all())
            ->filter(fn (array $recovery) => ($recovery['status'] ?? null) === 'pending')
            ->sortByDesc('createdAt');
        $passwordRequests = $pendingPasswordRequests
            ->take(10)
            ->map(fn (array $recovery) => [
                'id' => (string) ($recovery['id'] ?? ''),
                'title' => 'Permintaan reset password',
                'message' => 'Permintaan baru menunggu verifikasi administrator.',
                'url' => route('admin.pemulihan-akun'),
            ])
            ->values()
            ->all();
        $notifications = array_slice(array_merge($passwordRequests, $attendanceNotifications), 0, 10);

        return response()->json([
            'count' => count($items) + $pendingPasswordRequests->count(),
            'items' => $notifications,
        ]);
    }

    public function guru(): JsonResponse
    {
        $announcements = array_values(array_filter(
            Sekolah::pengumuman(),
            fn (array $announcement) => ($announcement['published'] ?? false) === true
        ));
        usort($announcements, fn (array $a, array $b) => strcmp(
            (string) ($b['publishedAt'] ?? $b['updatedAt'] ?? ''),
            (string) ($a['publishedAt'] ?? $a['updatedAt'] ?? '')
        ));

        $items = array_map(fn (array $announcement) => [
            'id' => (string) ($announcement['id'] ?? ''),
            'title' => (string) ($announcement['judul'] ?? 'Informasi sekolah'),
            'message' => (string) ($announcement['isi'] ?? ''),
            'pengirim' => (string) ($announcement['pengirim'] ?? ''),
            'targetKelas' => (string) ($announcement['targetKelas'] ?? ''),
            'urgensi' => (string) ($announcement['urgensi'] ?? 'Normal'),
            'publishedAt' => (string) ($announcement['publishedAt'] ?? ''),
            'url' => route('guru.dashboard').'#informasi-terbaru',
        ], array_slice($announcements, 0, 10));

        return response()->json([
            'count' => count($announcements),
            'items' => $items,
        ]);
    }
}
