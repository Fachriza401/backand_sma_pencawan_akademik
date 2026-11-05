<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_lengkap',
        'nis',
        'nisn',
        'kelas',
        'angkatan',
        'tanggal_masuk',
        'target_tanggal_lulus',
        'nama_wali',
        'hubungan_wali',
        'telepon_utama',
        'telepon_cadangan',
        'alamat',
        'status',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'target_tanggal_lulus' => 'date',
    ];

    protected $appends = [
        'link_wa_utama',
        'link_wa_cadangan',
    ];

    public function getLinkWaUtamaAttribute(): ?string
    {
        return $this->waLink($this->telepon_utama);
    }

    public function getLinkWaCadanganAttribute(): ?string
    {
        return $this->waLink($this->telepon_cadangan);
    }

    private function waLink(?string $telepon): ?string
    {
        $digit = preg_replace('/\D+/', '', (string) $telepon);
        if ($digit === '') {
            return null;
        }
        if (str_starts_with($digit, '0')) {
            $digit = '62'.substr($digit, 1);
        } elseif (str_starts_with($digit, '8')) {
            $digit = '62'.$digit;
        }

        return 'https://wa.me/'.$digit;
    }
}
