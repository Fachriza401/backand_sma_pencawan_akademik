<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class AttendanceAutomation
{
    public function markAlpaForToday(): int
    {
        $sekarang = now();
        if ($sekarang->dayOfWeekIso > 6 || $sekarang->lt($this->cutoffForToday())) {
            return 0;
        }

        $semua = Sekolah::absensiGuru();
        $diperbarui = 0;
        $maxId = (int) collect($semua)->max(fn (array $record) => (int) ($record['id'] ?? 0));
        $guruAktif = array_filter(
            Sekolah::guru(),
            fn (array $guru) => ($guru['status'] ?? '') === 'Aktif'
        );

        foreach ($guruAktif as $guru) {
            $sudahAda = false;
            foreach ($semua as $index => $record) {
                if (($record['guruId'] ?? null) !== ($guru['id'] ?? null)
                    || ($record['tgl'] ?? null) !== $sekarang->toDateString()) {
                    continue;
                }

                $sudahAda = true;
                if (($record['status'] ?? null) === 'Belum Hadir') {
                    $semua[$index]['status'] = 'Alpa';
                    $diperbarui++;
                }
                break;
            }

            if ($sudahAda) {
                continue;
            }

            $maxId++;
            $semua[] = [
                'id' => $maxId,
                'tgl' => $sekarang->toDateString(),
                'guruId' => $guru['id'],
                'jam' => null,
                'status' => 'Alpa',
                'jarak' => null,
                'verifikasi' => '-',
                'lat' => null,
                'lng' => null,
            ];
            $diperbarui++;
        }

        if ($diperbarui > 0) {
            Sekolah::put('absensi_guru', $semua);
        }

        return $diperbarui;
    }

    public function cutoffForToday(): Carbon
    {
        $pengaturan = Sekolah::pengaturan();
        $jamMasuk = (string) ($pengaturan['jamMasuk'] ?? '07:00');
        if (! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $jamMasuk)) {
            $jamMasuk = '07:00';
        }
        $toleransi = max(0, (int) ($pengaturan['toleransiMenit'] ?? 15));

        return now()->copy()->setTimeFromTimeString($jamMasuk)->addMinutes($toleransi);
    }
}
