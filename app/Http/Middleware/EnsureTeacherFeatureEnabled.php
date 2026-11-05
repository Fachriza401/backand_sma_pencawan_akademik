<?php

namespace App\Http\Middleware;

use App\Support\Sekolah;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherFeatureEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Sekolah::pengaturan()['fiturUnggulanGuruAktif'] ?? true, 403, 'Fitur presensi guru sedang dinonaktifkan administrator.');

        return $next($request);
    }
}
