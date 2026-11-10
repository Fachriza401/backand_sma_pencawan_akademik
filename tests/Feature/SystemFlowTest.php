<?php

namespace Tests\Feature;

use App\Http\Middleware\RestoreRememberedLogin;
use App\Support\PasswordRecoveryStore;
use App\Support\Sekolah;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ZipArchive;

class SystemFlowTest extends TestCase
{
    private string $testStoragePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStoragePath = base_path('storage/framework/testing/system-flow-'.uniqid('', true));
        app()->useStoragePath($this->testStoragePath);
        File::ensureDirectoryExists($this->testStoragePath.'/framework/views');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testStoragePath);

        parent::tearDown();
    }

    public function test_demo_accounts_login_and_redirect_to_their_dashboards(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Demo login:')
            ->assertSee('Maju Bersama')
            ->assertSee('Menuju Prestasi')
            ->assertSee('SIAKAD')
            ->assertSee('SMA Pencawan')
            ->assertSee('Sistem Informasi Akademik')
            ->assertSee('Hubungi Admin')
            ->assertSee('/lupa-password', false)
            ->assertSee('name="remember"', false)
            ->assertSee('mailto:admin@acsupply.com?', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('aria-label="Tampilkan password"', false);

        $this->from('/login')->post(route('login.submit'), [
            'user' => 'admin',
            'pass' => 'wrong-password',
        ])->assertSessionHasErrors('login');

        $this->post(route('login.submit'), [
            'user' => '',
            'pass' => '',
        ])->assertSessionHasErrors(['user', 'pass']);

        $this->post(route('login.submit'), [
            'user' => 'admin@acsupply.com',
            'pass' => 'admin123',
        ])->assertRedirect(route('admin.dashboard'));
        $this->assertTrue(Hash::check('admin123', Sekolah::cariAkun('admin')['pass']));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Selamat datang, Admin')
            ->assertSee('dashboard-top-grid', false)
            ->assertSee('Sistem beroperasi normal')
            ->assertSee('dashboard-live-date', false)
            ->assertSee('Versi 1.0.0 - Development')
            ->assertDontSee('Distribusi Kehadiran')
            ->assertDontSee('Guru Paling Disiplin')
            ->assertDontSee('Catatan Kehadiran Perlu Perhatian');

        $attendancePage = $this->get(route('admin.absensi'));
        $attendancePage->assertOk()
            ->assertSee('name="tgl"', false)
            ->assertDontSee('name="bulan"', false)
            ->assertDontSee('name="tahun"', false)
            ->assertDontSee('Tanggal kehadiran')
            ->assertDontSee('Bukti Foto')
            ->assertDontSee('Verifikasi')
            ->assertDontSee('Mencurigakan')
            ->assertDontSee('value="Mencurigakan"', false);
        $this->assertDoesNotMatchRegularExpression('/<a\b[^>]*>\s*<button\b/i', (string) $attendancePage->getContent());

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Bukti Foto')
            ->assertDontSee('Verifikasi')
            ->assertSee('SIAKAD')
            ->assertSee('assets/logo-pencawan.png')
            ->assertSee('class="dashboard-metric-icon"', false);

        $this->get(route('admin.laporan'))
            ->assertOk()
            ->assertSee('class="toolbar no-print report-toolbar"', false)
            ->assertSee('class="data report-table"', false)
            ->assertSee('class="report-total"', false)
            ->assertSee('<th>Total</th>', false);

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->post(route('login.submit'), [
            'user' => 'guru@acsupply.com',
            'pass' => 'guru123',
        ])->assertRedirect(route('guru.dashboard'));

        $this->get(route('guru.dashboard'))->assertOk();
    }

    public function test_remember_me_restores_a_login_and_logout_revokes_the_token(): void
    {
        $login = $this->post(route('login.submit'), [
            'user' => 'admin',
            'pass' => 'admin123',
            'remember' => '1',
        ])->assertRedirect(route('admin.dashboard'));

        $cookie = $login->getCookie(RestoreRememberedLogin::COOKIE);
        $this->assertNotNull($cookie);
        $this->assertNotEmpty(Sekolah::cariAkun('admin')['rememberTokenHash']);

        $this->flushSession();
        $this->withCookie(RestoreRememberedLogin::COOKIE, $cookie->getValue())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('<span class="nama">Admin</span>', false);

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertArrayNotHasKey('rememberTokenHash', Sekolah::cariAkun('admin'));
    }

    public function test_admin_can_manually_reset_active_teacher_password_and_recovery_page_has_tabs(): void
    {
        $accounts = Sekolah::akun();
        foreach ($accounts as &$account) {
            if (($account['guruId'] ?? null) === 'G01') {
                $account['rememberTokenHash'] = 'old-token-hash';
                $account['rememberExpiresAt'] = now()->addDay()->toIso8601String();
            }
        }
        unset($account);
        Sekolah::put('akun', $accounts);

        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Admin', 'user' => 'admin']])
            ->get(route('admin.pemulihan-akun'))
            ->assertOk()
            ->assertSee('Belum ada permintaan pemulihan akun saat ini')
            ->assertSee('recovery-empty-icon', false)
            ->assertSee('Reset Password Manual')
            ->assertSee('Permintaan Baru')
            ->assertSee('Riwayat Selesai');

        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Admin', 'user' => 'admin']])
            ->post(route('admin.pemulihan-akun.reset-manual'), [
                'guruId' => 'G01',
                'password' => 'manual-password-123',
                'password_confirmation' => 'manual-password-123',
            ])
            ->assertRedirect(route('admin.pemulihan-akun'))
            ->assertSessionHas('sukses', 'Password akun Budi Santoso, S.Pd. berhasil direset secara manual.');

        $teacherAccount = collect(Sekolah::akun())->firstWhere('guruId', 'G01');
        $this->assertTrue(Hash::check('manual-password-123', $teacherAccount['pass']));
        $this->assertArrayNotHasKey('rememberTokenHash', $teacherAccount);
        $this->assertArrayNotHasKey('rememberExpiresAt', $teacherAccount);

        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Admin', 'user' => 'admin']])
            ->post(route('admin.pemulihan-akun.reset-manual'), [
                'guruId' => 'G01',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_password_recovery_requires_admin_approval_and_uses_a_one_time_code(): void
    {
        $this->get(route('password.recovery.create'))
            ->assertOk()
            ->assertSee('Informasi untuk verifikasi');

        $submitted = $this->post(route('password.recovery.store'), [
            'identifier' => 'admin',
            'verification' => 'admin@acsupply.com',
        ])->assertRedirect();
        $reference = basename(parse_url($submitted->headers->get('Location'), PHP_URL_PATH));

        $this->get(route('password.recovery.status', ['reference' => $reference]))
            ->assertOk()
            ->assertSee('Menunggu verifikasi administrator');

        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']])
            ->get(route('admin.notifikasi'))
            ->assertOk()
            ->assertJsonPath('items.0.title', 'Permintaan reset password');

        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']])
            ->get(route('admin.pemulihan-akun'))
            ->assertOk()
            ->assertSee('admin@acsupply.com');

        $approved = $this->post(route('admin.pemulihan-akun.approve', ['reference' => $reference]))
            ->assertSessionHas('recoveryCode');
        $code = $approved->getSession()->get('recoveryCode')['code'];
        $this->assertNotEmpty($code);

        $this->post(route('password.recovery.update', ['reference' => $reference]), [
            'code' => 'KODE-SALAH',
            'password' => 'password-pemulihan-123',
            'password_confirmation' => 'password-pemulihan-123',
        ])->assertSessionHasErrors('code');

        $this->post(route('password.recovery.update', ['reference' => $reference]), [
            'code' => $code,
            'password' => 'password-pemulihan-123',
            'password_confirmation' => 'password-pemulihan-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password-pemulihan-123', Sekolah::cariAkun('admin')['pass']));
        $this->assertSame('completed', collect(app(PasswordRecoveryStore::class)->all())->firstWhere('id', $reference)['status']);
        $this->post(route('login.submit'), [
            'user' => 'admin',
            'pass' => 'password-pemulihan-123',
        ])->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_notifications_refresh_from_current_attendance_status_and_exclude_suspicious_records(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);

        $this->get(route('admin.notifikasi'))
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonMissing(['title' => 'Presensi mencurigakan']);

        $attendance = Sekolah::absensiGuru();
        $attendanceIndex = array_search(8, array_column($attendance, 'id'));
        $this->assertNotFalse($attendanceIndex);
        $attendance[$attendanceIndex]['verifikasi'] = 'Menunggu';
        Sekolah::put('absensi_guru', $attendance);

        $this->get(route('admin.notifikasi'))
            ->assertOk()
            ->assertJsonPath('items.0.title', 'Pengajuan izin');

        $this->post(route('admin.absensi.status'), [
            'id' => 8,
            'status' => 'Hadir',
        ])->assertSessionHas('sukses', 'Status presensi berhasil diperbarui.');

        $this->get(route('admin.notifikasi'))
            ->assertOk()
            ->assertJsonPath('count', 0)
            ->assertJsonMissing(['title' => 'Pengajuan izin'])
            ->assertJsonMissing(['title' => 'Presensi mencurigakan']);
    }

    public function test_admin_can_reject_a_password_recovery_request(): void
    {
        $submitted = $this->post(route('password.recovery.store'), [
            'identifier' => 'admin',
            'verification' => 'data-verifikasi-tidak-cocok',
        ])->assertRedirect();
        $reference = basename(parse_url($submitted->headers->get('Location'), PHP_URL_PATH));

        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']])
            ->post(route('admin.pemulihan-akun.reject', ['reference' => $reference]))
            ->assertSessionHas('sukses', 'Permintaan pemulihan ditolak.');

        $this->get(route('password.recovery.status', ['reference' => $reference]))
            ->assertOk()
            ->assertSee('Tidak disetujui');

        $this->assertSame('admin123', Sekolah::cariAkun('admin')['pass']);
    }

    public function test_expired_password_recovery_codes_are_removed_and_reported(): void
    {
        $submitted = $this->post(route('password.recovery.store'), [
            'identifier' => 'admin',
            'verification' => 'admin@acsupply.com',
        ])->assertRedirect();
        $reference = basename(parse_url($submitted->headers->get('Location'), PHP_URL_PATH));

        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']])
            ->post(route('admin.pemulihan-akun.approve', ['reference' => $reference]))
            ->assertSessionHas('recoveryCode');

        app(PasswordRecoveryStore::class)->mutate(function (array &$requests) use ($reference) {
            foreach ($requests as &$recovery) {
                if (($recovery['id'] ?? null) === $reference) {
                    $recovery['codeExpiresAt'] = now()->subMinute()->toIso8601String();
                    break;
                }
            }
            unset($recovery);
        });

        $this->get(route('password.recovery.status', ['reference' => $reference]))
            ->assertOk()
            ->assertSee('Kedaluwarsa');

        $recovery = collect(app(PasswordRecoveryStore::class)->all())->firstWhere('id', $reference);
        $this->assertSame('expired', $recovery['status']);
        $this->assertArrayNotHasKey('codeHash', $recovery);
    }

    public function test_admin_can_import_csv_teachers_and_download_the_template(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);

        $this->get(route('admin.guru'))
            ->assertOk()
            ->assertSee('id="guru-drawer"', false)
            ->assertSee('id="import-guru-modal"', false)
            ->assertSee('Import Data Guru')
            ->assertSee('data-close-guru', false)
            ->assertSee('Password Login')
            ->assertSee('Versi 1.0.0 - Development')
            ->assertSee('SIAKAD')
            ->assertSee('Sistem Informasi Akademik');

        $template = $this->get(route('admin.guru.template'));
        $template->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('nip,nama,mapel,telepon,status,username,email,password', $template->getContent());

        $response = $this->post(route('admin.guru.impor'), [
            'file' => UploadedFile::fake()->createWithContent(
                'guru.csv',
                "nip,nama,mapel,telepon,status,username,email,password\r\n123456789012,Guru Impor,Matematika,081234567890,Aktif,guru-impor,guru.impor@example.test,password-impor-123\r\n"
            ),
        ]);
        $response->assertRedirect()
            ->assertSessionHas('sukses', '1 guru dan akun berhasil diimpor.')
            ->assertSessionMissing('imporGuru');

        $teacher = collect(Sekolah::guru())->firstWhere('nama', 'Guru Impor');
        $this->assertNotNull($teacher);
        $this->assertSame('081234567890', $teacher['telepon']);
        $account = collect(Sekolah::akun())->firstWhere('guruId', $teacher['id']);
        $this->assertSame('guru-impor', $account['user']);
        $this->assertTrue(Hash::check('password-impor-123', $account['pass']));
    }

    public function test_teacher_import_rejects_invalid_rows_without_partial_saves(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);
        $teacherCount = count(Sekolah::guru());

        $this->post(route('admin.guru.impor'), [
            'file' => UploadedFile::fake()->createWithContent(
                'guru.csv',
                "nip,nama,mapel,status,username,email,password\r\n123456789013,Guru Valid,Matematika,Aktif,guru-valid,guru.valid@example.test,password-valid-123\r\n198503122010011001,Guru Duplikat,Fisika,Aktif,guru-duplikat,guru.duplikat@example.test,password-duplicate-123\r\n"
            ),
        ])->assertRedirect()->assertSessionHasErrors('file');

        $this->assertCount($teacherCount, Sekolah::guru());
        $this->assertNull(collect(Sekolah::guru())->firstWhere('nama', 'Guru Valid'));
    }

    public function test_admin_can_import_xlsx_teachers(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);
        $path = tempnam($this->testStoragePath, 'guru-');
        $archive = new ZipArchive;
        $this->assertSame(true, $archive->open($path, ZipArchive::OVERWRITE));

        $strings = ['nip', 'nama', 'mapel', 'status', 'username', 'email', 'password', '123456789014', 'Guru Excel', 'Fisika', 'Aktif', 'guru-excel', 'guru.excel@example.test', 'password-excel-123'];
        $sharedStrings = '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        foreach ($strings as $string) {
            $sharedStrings .= '<si><t>'.$string.'</t></si>';
        }
        $sharedStrings .= '</sst>';
        $archive->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Guru" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $archive->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/sheet1.xml" Type="worksheet"/></Relationships>');
        $archive->addFromString('xl/sharedStrings.xml', $sharedStrings);
        $headerCells = '';
        foreach (range('A', 'G') as $index => $column) {
            $headerCells .= '<c r="'.$column.'1" t="s"><v>'.$index.'</v></c>';
        }
        $dataCells = '';
        foreach (range('A', 'G') as $index => $column) {
            $dataCells .= '<c r="'.$column.'2" t="s"><v>'.($index + 7).'</v></c>';
        }
        $archive->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1">'.$headerCells.'</row><row r="2">'.$dataCells.'</row></sheetData></worksheet>');
        $archive->close();

        $this->post(route('admin.guru.impor'), [
            'file' => new UploadedFile(
                $path,
                'guru.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            ),
        ])->assertRedirect()->assertSessionHas('sukses', '1 guru dan akun berhasil diimpor.');

        $teacher = collect(Sekolah::guru())->firstWhere('nama', 'Guru Excel');
        $this->assertNotNull($teacher);
        $account = collect(Sekolah::akun())->firstWhere('guruId', $teacher['id']);
        $this->assertTrue(Hash::check('password-excel-123', $account['pass']));
    }

    public function test_new_teacher_account_requires_a_manual_password(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);
        $teacherCount = count(Sekolah::guru());

        $this->post(route('admin.guru.simpan'), [
            'nip' => '123456789015',
            'nama' => 'Guru Tanpa Password',
            'mapel' => 'Fisika',
            'status' => 'Aktif',
            'username' => 'guru-tanpa-password',
            'email' => 'guru.tanpa.password@example.test',
        ])->assertRedirect()->assertSessionHasErrors('password');

        $this->assertCount($teacherCount, Sekolah::guru());
        $this->assertNull(collect(Sekolah::guru())->firstWhere('nama', 'Guru Tanpa Password'));
    }

    public function test_admin_can_search_view_teacher_details_and_create_a_missing_account_with_contact(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);
        $teacher = collect(Sekolah::guru())->firstWhere('id', 'G02');

        $this->get(route('admin.guru'))
            ->assertOk()
            ->assertSee('teacher-search', false)
            ->assertSee('teacher-subject-filter', false)
            ->assertSee('Kontak / WhatsApp')
            ->assertSee('Buat Akun')
            ->assertSee('Detail Guru')
            ->assertSee('Presensi Terbaru')
            ->assertSee('XI IPS 1');

        $this->post(route('admin.guru.simpan'), [
            'id' => $teacher['id'],
            'nip' => $teacher['nip'],
            'nama' => $teacher['nama'],
            'mapel' => $teacher['mapel'],
            'telepon' => '081234567890',
            'status' => $teacher['status'],
            'username' => 'guru-g02',
            'email' => 'guru.g02@example.test',
            'password' => 'manual-password-123',
        ])->assertRedirect()->assertSessionHas('sukses', 'Data guru diperbarui.');

        $updatedTeacher = collect(Sekolah::guru())->firstWhere('id', 'G02');
        $this->assertSame('081234567890', $updatedTeacher['telepon']);
        $account = collect(Sekolah::akun())->firstWhere('guruId', 'G02');
        $this->assertSame('guru.g02@example.test', $account['email']);
        $this->assertTrue(Hash::check('manual-password-123', $account['pass']));
    }

    public function test_admin_and_teacher_pages_render_and_enforce_roles(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);

        foreach ([
            'admin.dashboard',
            'admin.guru',
            'admin.jadwal-guru',
            'admin.jadwal-kelas',
            'admin.absensi',
            'admin.laporan',
            'admin.informasi',
            'admin.profil',
            'admin.pemulihan-akun',
            'admin.notifikasi',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
        $dashboard = $this->get(route('admin.dashboard'));
        $dashboard
            ->assertOk()
            ->assertSee('Total Guru Aktif')
            ->assertSee('Guru Hadir Hari Ini')
            ->assertSee('Guru Terlambat')
            ->assertSee('weekly-attendance-chart')
            ->assertSee('monthly-attendance-chart')
            ->assertSee('Pantau rekapitulasi kehadiran harian')
            ->assertSee("labels: ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa']", false)
            ->assertSee('<span class="nama">Admin</span>', false)
            ->assertDontSee('<span class="role">ADMINISTRATOR</span>', false)
            ->assertSee(route('admin.absensi'))
            ->assertSee(route('admin.laporan'));
        $dashboard->assertSee('class="admin-user-avatar"', false);
        $this->get(route('admin.laporan'))
            ->assertOk()
            ->assertDontSee('Mencurigakan');
        $this->get(route('admin.jadwal-guru'))
            ->assertOk()
            ->assertSee('Peta Mengajar Mingguan');
        $this->get(route('admin.jadwal-kelas', ['kelas' => 'X-1', 'hari' => 'Senin']))
            ->assertOk()
            ->assertSee('Agenda Kelas · Senin')
            ->assertSee('Jam kosong')
            ->assertSee('Atur pelajaran')
            ->assertDontSee('Susunan Pelajaran Mingguan');
        $this->get('/admin/qr-absensi')->assertNotFound();
        $this->get(route('admin.dashboard'))
            ->assertDontSee('Quick Geo-Absence & Rekap Kehadiran Mandiri');
        $this->get(route('admin.dashboard'))
            ->assertDontSee('Kehadiran 7 Hari Terakhir')
            ->assertDontSee('Jarak');
        $this->get(route('admin.absensi'))
            ->assertDontSee('Jarak');

        $this->withSession(['user' => ['role' => 'guru', 'nama' => 'Guru', 'guruId' => 'G01']]);

        foreach ([
            'guru.dashboard',
            'guru.jadwal',
            'guru.presensi',
            'guru.absensi-siswa',
            'guru.nilai',
            'guru.rapor',
            'guru.notifikasi',
        ] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('guru.dashboard'))
            ->assertDontSee('guru-welcome-avatar')
            ->assertDontSee('i.pravatar.cc')
            ->assertDontSee('guru-welcome-mark')
            ->assertSee('guru-welcome-status-widget', false)
            ->assertSee('Belum presensi')
            ->assertSee('Presensi Sekarang')
            ->assertSee('Total ')
            ->assertSee('Tren Mingguan')
            ->assertSee('Absensi Kelas Hari Ini')
            ->assertSee('guru-weekly-attendance-chart', false)
            ->assertSee('guru-teaching-progress-chart', false)
            ->assertSee('data-presence-open', false)
            ->assertSee('Versi 1.0.0 - Development');
        $this->get(route('guru.nilai'))
            ->assertSee('Mata Pelajaran')
            ->assertSee('name="mapel"', false);

        $this->get(route('admin.dashboard'))->assertRedirect(route('guru.dashboard'));
    }

    public function test_teacher_schedule_can_filter_by_day_and_class_and_open_student_attendance(): void
    {
        $this->withSession([
            'user' => ['role' => 'guru', 'nama' => 'Budi Santoso', 'guruId' => 'G01'],
        ]);

        $this->get(route('guru.jadwal', [
            'tanggal' => '2026-10-12',
            'hari' => 'Senin',
            'kelas' => 'X-1',
        ]))
            ->assertOk()
            ->assertSee('Cetak Jadwal')
            ->assertSee('Pilih hari jadwal')
            ->assertSee('aria-current="page"', false)
            ->assertSee('Matematika')
            ->assertSee('Kelas X-1')
            ->assertSee(route('guru.absensi-siswa', ['kelas' => 'X-1', 'tanggal' => '2026-10-12']))
            ->assertDontSee('Kelas X-2');

        $this->get(route('guru.jadwal', ['tanggal' => 'invalid-date']))
            ->assertSessionHasErrors('tanggal');
    }

    public function test_admin_can_manage_teacher_schedule_attendance_and_settings(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator']]);

        $createdTeacher = $this->post(route('admin.guru.simpan'), [
            'nip' => '123456789012',
            'nama' => 'Guru Pengujian',
            'mapel' => 'Bahasa Indonesia',
            'status' => 'Aktif',
            'username' => 'guru-pengujian',
            'email' => 'guru.pengujian@example.test',
            'password' => 'password-guru-123',
        ]);
        $createdTeacher->assertSessionHas('sukses', 'Guru baru ditambahkan.')
            ->assertSessionMissing('passwordSementara');

        $teacher = collect(Sekolah::guru())->firstWhere('nama', 'Guru Pengujian');
        $this->assertNotNull($teacher);
        $teacherAccount = collect(Sekolah::akun())->firstWhere('guruId', $teacher['id']);
        $this->assertSame('guru-pengujian', $teacherAccount['user']);
        $this->assertNotSame('123456789012', $teacherAccount['pass']);
        $this->assertTrue(Hash::check('password-guru-123', $teacherAccount['pass']));

        $this->post(route('login.submit'), [
            'user' => 'guru.pengujian@example.test',
            'pass' => 'password-guru-123',
        ])->assertRedirect(route('guru.dashboard'));
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);

        $this->post(route('admin.guru.simpan'), [
            'id' => $teacher['id'],
            'nip' => '123456789012',
            'nama' => 'Guru Pengujian Diperbarui',
            'mapel' => 'Matematika',
            'status' => 'Aktif',
            'username' => 'guru-pengujian',
            'email' => 'guru.pengujian@example.test',
            'password' => 'password-guru-updated-123',
        ])->assertSessionHas('sukses', 'Data guru diperbarui.');
        $this->assertNotNull(Sekolah::cariGuru($teacher['id']));
        $this->assertTrue(Hash::check('password-guru-updated-123', collect(Sekolah::akun())->firstWhere('guruId', $teacher['id'])['pass']));

        $this->post(route('admin.guru.simpan'), [
            'id' => $teacher['id'],
            'nip' => '123456789012',
            'nama' => 'Guru Pengujian Diperbarui',
            'mapel' => 'Matematika',
            'status' => 'Aktif',
            'username' => 'guru-pengujian',
            'email' => 'guru.pengujian@example.test',
        ])->assertSessionHas('sukses', 'Data guru diperbarui.');
        $this->assertTrue(Hash::check('password-guru-updated-123', collect(Sekolah::akun())->firstWhere('guruId', $teacher['id'])['pass']));

        $this->post(route('admin.guru.hapus'), ['id' => $teacher['id']])
            ->assertSessionHas('sukses', 'Guru diarsipkan. Jadwal dan riwayat presensi tetap tersimpan.');
        $this->assertSame('Nonaktif', Sekolah::cariGuru($teacher['id'])['status']);
        $this->assertFalse(collect(Sekolah::akun())->firstWhere('guruId', $teacher['id'])['aktif']);
        $this->post(route('login.submit'), [
            'user' => 'guru-pengujian',
            'pass' => 'password-guru-updated-123',
        ])->assertSessionHasErrors('login');

        $teacherSchedule = [
            'idGuru' => 'G12',
            'hari' => 'Jumat',
            'jamKe' => 8,
            'kelas' => 'Uji-Guru',
            'ruang' => 'R-Uji-Guru',
            'mapel' => 'Mata Pelajaran Uji',
        ];

        $this->post(route('admin.jadwal-guru.simpan'), $teacherSchedule)
            ->assertSessionHas('sukses', 'Jadwal ditambahkan.');
        $this->post(route('admin.jadwal-guru.simpan'), $teacherSchedule)
            ->assertSessionHasErrors('jadwal');

        $teacherScheduleIndex = collect(Sekolah::jadwal())->search(
            fn (array $schedule) => ($schedule['kelas'] ?? null) === 'Uji-Guru'
        );
        $this->assertNotFalse($teacherScheduleIndex);
        $this->post(route('admin.jadwal-guru.hapus'), ['idx' => $teacherScheduleIndex])
            ->assertSessionHas('sukses');

        $newSchedule = [
            'idGuru' => 'G12',
            'hari' => 'Jumat',
            'jamKe' => 7,
            'kelas' => 'Uji-Flow',
            'ruang' => 'R-Uji',
            'mapel' => 'Mata Pelajaran Uji',
        ];

        $this->post(route('admin.jadwal-kelas.simpan'), $newSchedule)
            ->assertSessionHas('sukses', 'Slot ditambahkan.');

        $scheduleIndex = collect(Sekolah::jadwal())->search(
            fn (array $schedule) => ($schedule['kelas'] ?? null) === 'Uji-Flow'
        );
        $this->assertNotFalse($scheduleIndex);

        $this->post(route('admin.jadwal-kelas.simpan'), $newSchedule)
            ->assertSessionHasErrors('jadwal');

        $this->post(route('admin.jadwal-kelas.hapus'), ['idx' => $scheduleIndex])
            ->assertSessionHas('sukses');

        $this->post(route('admin.absensi.status'), [
            'id' => 1,
            'status' => 'Mencurigakan',
        ])->assertSessionHasErrors('status');
        $this->post(route('admin.absensi.status'), [
            'id' => 1,
            'status' => 'Izin',
        ])->assertSessionHas('sukses', 'Status presensi berhasil diperbarui.');
        $this->assertSame('Izin', collect(Sekolah::absensiGuru())->firstWhere('id', 1)['status']);

        $this->get(route('admin.absensi', ['status' => 'Izin']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('data-absensi-id="2"', false);
        $this->get('/admin/absensi?bulan[]=x')->assertOk();

        $export = $this->get(route('admin.absensi.ekspor'));
        $export->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $export->getContent());

        $sheet = $this->sheetXmlFromXlsx($export->getContent());
        $this->assertStringContainsString('Absensi Guru', $sheet);
        $this->assertStringContainsString('Nama Guru', $sheet);
        $this->assertStringNotContainsString('Verifikasi', $sheet);

        $laporan = $this->get(route('admin.laporan.ekspor'));
        $laporan->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $laporan->getContent());

        $laporanSheet = $this->sheetXmlFromXlsx($laporan->getContent());
        $this->assertStringContainsString('Laporan Kehadiran Guru', $laporanSheet);
        $this->assertStringContainsString('Persentase', $laporanSheet);

        $this->post(route('admin.absensi.hapus'), ['id' => 1])
            ->assertSessionHas('sukses', 'Data presensi berhasil dihapus.');
        $this->assertNull(collect(Sekolah::absensiGuru())->firstWhere('id', 1));
    }

    public function test_monthly_report_filters_by_subject_and_saves_admin_notes(): void
    {
        $this->post(route('login.submit'), [
            'user' => 'admin@acsupply.com',
            'pass' => 'admin123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.laporan', ['bulan' => '2026-10', 'mapel' => 'Matematika']))
            ->assertOk()
            ->assertSee('Tren Kehadiran Bulanan')
            ->assertSee('value="Matematika" selected', false)
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Aminah')
            ->assertSee('data: [20,14]', false)
            ->assertSee('Catatan / Keterangan');

        $this->post(route('admin.laporan.catatan'), [
            'bulan' => '2026-10',
            'guruId' => 'G01',
            'mapel' => 'Matematika',
            'catatan' => 'Perlu tindak lanjut administrasi',
        ])->assertRedirect(route('admin.laporan', ['bulan' => '2026-10', 'mapel' => 'Matematika']))
            ->assertSessionHas('sukses', 'Catatan laporan berhasil disimpan.');

        $this->get(route('admin.laporan', ['bulan' => '2026-10', 'mapel' => 'Matematika']))
            ->assertOk()
            ->assertSee('value="Perlu tindak lanjut administrasi"', false);

        $export = $this->get(route('admin.laporan.ekspor', ['bulan' => '2026-10', 'mapel' => 'Matematika']));
        $export->assertOk();
        $sheet = $this->sheetXmlFromXlsx($export->getContent());
        $this->assertStringContainsString('Catatan', $sheet);
        $this->assertStringContainsString('Perlu tindak lanjut administrasi', $sheet);
        $this->assertStringNotContainsString('Siti Aminah', $sheet);
    }

    public function test_admin_can_normalize_legacy_attendance_and_add_manual_attendance(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);
        $attendance = Sekolah::absensiGuru();
        $legacy = collect($attendance)->firstWhere('status', 'Mencurigakan');
        $this->assertNotNull($legacy);

        $page = $this->get(route('admin.absensi'));
        $page->assertOk()
            ->assertSee('Rapikan '.$this->legacyAttendanceCount($attendance).' Status')
            ->assertSee('Tambah Kehadiran Manual')
            ->assertDontSee('Status lama · perlu diperbarui');

        $this->post(route('admin.absensi.rapikan-status-lama'))
            ->assertSessionHas('sukses', $this->legacyAttendanceCount($attendance).' status lama diperbarui menjadi Belum Hadir.');
        $this->assertSame(
            'Belum Hadir',
            collect(Sekolah::absensiGuru())->firstWhere('id', $legacy['id'])['status']
        );

        $tanggal = now()->subYears(10)->toDateString();
        $this->post(route('admin.absensi.tambah-manual'), [
            'guruId' => 'G12',
            'tgl' => $tanggal,
            'jam' => '',
            'status' => 'Izin',
        ])->assertSessionHas('sukses', 'Kehadiran manual berhasil ditambahkan.');

        $manualRecord = collect(Sekolah::absensiGuru())->first(
            fn (array $record) => ($record['guruId'] ?? null) === 'G12'
                && ($record['tgl'] ?? null) === $tanggal
        );
        $this->assertNotNull($manualRecord);
        $this->assertSame('Izin', $manualRecord['status']);
        $this->assertNull($manualRecord['jam']);

        $this->post(route('admin.absensi.tambah-manual'), [
            'guruId' => 'G12',
            'tgl' => $tanggal,
            'jam' => '07:00',
            'status' => 'Hadir',
        ])->assertSessionHasErrors('guruId');
    }

    public function test_alpa_is_marked_after_configured_cutoff_and_preserves_excused_statuses(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 07:14:59', 'Asia/Jakarta'));
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Admin', 'user' => 'admin']]);
        $tanggal = now()->toDateString();
        $records = Sekolah::absensiGuru();
        $nextId = collect($records)->max(fn (array $record) => (int) ($record['id'] ?? 0)) + 1;
        $records[] = [
            'id' => $nextId,
            'tgl' => $tanggal,
            'guruId' => 'G01',
            'jam' => null,
            'status' => 'Izin',
        ];
        $records[] = [
            'id' => $nextId + 1,
            'tgl' => $tanggal,
            'guruId' => 'G02',
            'jam' => null,
            'status' => 'Belum Hadir',
        ];
        Sekolah::put('absensi_guru', $records);

        try {
            $this->get(route('admin.absensi', ['tgl' => $tanggal]))
                ->assertOk()
                ->assertSee('value="Alpa"', false)
                ->assertSee('07:15')
                ->assertDontSee('status Alpa baru diperbarui');
            $this->assertSame(
                2,
                collect(Sekolah::absensiGuru())->where('tgl', $tanggal)->count()
            );

            Carbon::setTestNow(Carbon::parse('2026-10-19 07:15:00', 'Asia/Jakarta'));
            $page = $this->get(route('admin.absensi', ['tgl' => $tanggal]));
            $page->assertOk()
                ->assertSee('status Alpa baru diperbarui')
                ->assertSee('status-inline is-alpa', false);

            $daily = collect(Sekolah::absensiGuru())->where('tgl', $tanggal);
            $activeTeachers = collect(Sekolah::guru())->where('status', 'Aktif')->pluck('id');
            $this->assertCount($activeTeachers->count(), $daily);
            $this->assertSame('Izin', $daily->firstWhere('guruId', 'G01')['status']);
            $this->assertSame('Alpa', $daily->firstWhere('guruId', 'G02')['status']);
            Sekolah::catatPresensi('G02', [
                'tgl' => $tanggal,
                'jam' => '07:30',
                'status' => 'Terlambat',
            ]);
            $this->assertSame(
                'Terlambat',
                collect(Sekolah::absensiGuru())->first(
                    fn (array $record) => ($record['guruId'] ?? null) === 'G02'
                        && ($record['tgl'] ?? null) === $tanggal
                )['status']
            );
            $this->get(route('admin.dashboard'))
                ->assertOk()
                ->assertSee('Belum Hadir / Alpa')
                ->assertSee('Alpa <b>9</b>', false);
            $this->get(route('admin.absensi', ['tgl' => $tanggal]))
                ->assertOk()
                ->assertDontSee('status Alpa baru diperbarui');
            $this->assertCount($activeTeachers->count(), collect(Sekolah::absensiGuru())->where('tgl', $tanggal));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function legacyAttendanceCount(array $attendance): int
    {
        $validStatuses = array_diff(Sekolah::STATUS_ABSENSI, ['Mencurigakan']);

        return count(array_filter(
            $attendance,
            fn (array $record) => ! in_array($record['status'] ?? null, $validStatuses, true)
        ));
    }

    private function sheetXmlFromXlsx(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sigap-test-xlsx-');
        $this->assertNotFalse($path);
        file_put_contents($path, $content);

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path) === true);
        $sheet = $archive->getFromName('xl/worksheets/sheet1.xml');
        $archive->close();
        @unlink($path);

        $this->assertIsString($sheet);

        return $sheet;
    }

    public function test_admin_can_update_profile_and_manage_published_information(): void
    {
        $this->withSession(['user' => [
            'role' => 'admin',
            'nama' => 'Administrator',
            'user' => 'admin',
        ]]);

        $this->post(route('admin.profil.update'), [
            'nama' => 'Admin SMA Pencawan',
            'email' => 'admin.updated@example.test',
        ])->assertSessionHas('sukses', 'Profil admin berhasil diperbarui.');
        $this->assertSame('admin.updated@example.test', Sekolah::cariAkun('admin')['email']);
        $this->assertSame('Admin', Sekolah::cariAkun('admin')['nama']);
        $this->assertSame('Admin', session('user.nama'));

        $this->post(route('admin.profil.update'), [
            'nama' => 'Admin SMA Pencawan',
            'email' => 'admin.updated@example.test',
            'current_password' => 'admin123',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHas('sukses');
        $this->assertTrue(Hash::check('password-baru-123', Sekolah::cariAkun('admin')['pass']));
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login.submit'), [
            'user' => 'admin.updated@example.test',
            'pass' => 'password-baru-123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.informasi.simpan'), [
            'judul' => 'Arahan Kepala Sekolah',
            'isi' => 'Rapat guru dimulai pukul 10.00.',
            'published' => '1',
        ])->assertSessionHas('sukses', 'Informasi dipublikasikan.');

        $this->withSession(['user' => ['role' => 'guru', 'nama' => 'Budi Santoso', 'guruId' => 'G01']]);
        $dashboard = $this->get(route('guru.dashboard'));
        $dashboard
            ->assertOk()
            ->assertSee('Arahan Kepala Sekolah')
            ->assertSee('Rapat guru dimulai pukul 10.00.')
            ->assertSee('Jadwal')
            ->assertSee('Absensi Siswa')
            ->assertSee('Nilai Siswa')
            ->assertSee('Rekap Absensi')
            ->assertDontSee('shortcut-blue');
        $this->assertSame(4, preg_match_all('/class="guru-shortcut"/', $dashboard->getContent()));
        $this->get(route('guru.notifikasi'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.title', 'Arahan Kepala Sekolah');
    }

    public function test_admin_information_page_uses_responsive_composer_and_urgency_cards(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Admin', 'user' => 'admin']]);
        $this->post(route('admin.informasi.simpan'), [
            'judul' => 'Pengumuman Penting Sekolah',
            'isi' => 'Jadwal kegiatan sekolah diperbarui.',
            'pengirim' => 'Kepala Sekolah',
            'targetKelas' => 'X-1',
            'urgensi' => 'Penting',
            'published' => '1',
        ])->assertSessionHas('sukses', 'Informasi dipublikasikan.');

        $page = $this->get(route('admin.informasi'));
        $page->assertOk()
            ->assertSee('info-form-grid', false)
            ->assertSee('info-form-fields', false)
            ->assertSee('info-form-message', false)
            ->assertSee('info-item-card', false)
            ->assertSee('info-urgency-badge is-important', false)
            ->assertSee('Pengumuman Penting Sekolah')
            ->assertSee('Edit informasi')
            ->assertSee('Hapus informasi');
    }

    public function test_admin_profile_supports_avatar_upload_and_shows_login_audit(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.24',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        ])->post(route('login.submit'), [
            'user' => 'admin',
            'pass' => 'admin123',
        ])->assertRedirect(route('admin.dashboard'));

        $account = Sekolah::cariAkun('admin');
        $this->assertSame('Chrome · Windows', $account['lastLogin']['device']);
        $this->assertSame('203.0.113.24', $account['lastLogin']['ip']);

        $this->get(route('admin.profil'))
            ->assertOk()
            ->assertSee('value="Admin"', false)
            ->assertSee('Unggah Foto Baru')
            ->assertSee('x-data="adminProfile', false)
            ->assertSee('passwordStrength', false)
            ->assertSee('Full Administrator')
            ->assertSee('Super Admin')
            ->assertSee('Riwayat Login Terakhir')
            ->assertSee('203.0.113.24')
            ->assertSee('Chrome · Windows');

        $this->post(route('admin.profil.update'), [
            'nama' => 'Admin',
            'email' => $account['email'],
            'foto' => UploadedFile::fake()->image('admin-profile.png'),
        ])->assertSessionHas('sukses', 'Profil admin berhasil diperbarui.');

        $photoPath = Sekolah::cariAkun('admin')['foto'];
        $this->assertStringStartsWith('uploads/admin/', $photoPath);
        $this->assertFileExists(public_path($photoPath));
        $this->assertSame($photoPath, session('user.foto'));
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(asset($photoPath), false);
        File::delete(public_path($photoPath));
    }

    public function test_admin_contact_settings_are_used_by_login_contact_menu(): void
    {
        $this->withSession(['user' => [
            'role' => 'admin',
            'nama' => 'Administrator',
            'user' => 'admin',
        ]]);

        $this->post(route('admin.profil.update'), [
            'nama' => 'Administrator',
            'email' => 'admin@acsupply.com',
            'teleponAdmin' => '+62 812-3456-7890',
        ])->assertSessionHas('sukses');

        $this->post(route('logout'));
        $this->get('/')
            ->assertOk()
            ->assertSee('https://wa.me/6281234567890', false);
    }

    public function test_admin_controls_teacher_feature_and_teacher_notifications_are_role_protected(): void
    {
        $this->withSession(['user' => ['role' => 'admin', 'nama' => 'Administrator', 'user' => 'admin']]);
        $this->post(route('admin.fitur-guru'), ['aktif' => '0'])
            ->assertSessionHas('sukses', 'Fitur unggulan panel guru dinonaktifkan.');
        $this->assertFalse(Sekolah::pengaturan()['fiturUnggulanGuruAktif']);

        $this->withSession(['user' => ['role' => 'guru', 'nama' => 'Budi Santoso', 'guruId' => 'G01']]);
        $this->get(route('guru.dashboard'))
            ->assertOk()
            ->assertDontSee('Quick Geo-Absence & Rekap Kehadiran Mandiri');
        $this->get(route('guru.presensi'))->assertForbidden();
        $this->get(route('admin.informasi'))->assertRedirect(route('guru.dashboard'));
    }

    public function test_legacy_json_accounts_keep_email_login_and_upgrade_password_hash(): void
    {
        Sekolah::put('akun', [
            ['user' => 'admin', 'pass' => 'admin123', 'role' => 'admin', 'nama' => 'Administrator'],
            ['user' => 'guru', 'pass' => 'guru123', 'role' => 'guru', 'nama' => 'Budi Santoso, S.Pd.', 'guruId' => 'G01'],
        ]);

        $this->post(route('login.submit'), [
            'user' => 'admin@acsupply.com',
            'pass' => 'admin123',
        ])->assertRedirect(route('admin.dashboard'));

        $account = Sekolah::cariAkun('admin');
        $this->assertSame('admin@acsupply.com', $account['email']);
        $this->assertTrue(Hash::check('admin123', $account['pass']));
    }

    public function test_teacher_attendance_is_saved_once_per_day_and_bad_input_is_rejected(): void
    {
        $this->withSession([
            'user' => ['role' => 'guru', 'nama' => 'Budi Santoso', 'guruId' => 'G01'],
        ]);

        $this->post(route('guru.presensi.store'), [
            'jarak' => 101,
            'lat' => 3.5952,
            'lng' => 98.6722,
        ])->assertSessionHas('sukses', 'Absensi berhasil dikirim.');

        $records = collect(Sekolah::absensiGuru())
            ->where('guruId', 'G01')
            ->where('tgl', date('Y-m-d'));
        $this->assertCount(1, $records);
        $this->assertSame('Mencurigakan', $records->first()['status']);

        $this->post(route('guru.presensi.store'), [
            'jarak' => 102,
            'lat' => 3.5952,
            'lng' => 98.6722,
        ])->assertSessionHas('sukses');

        $records = collect(Sekolah::absensiGuru())
            ->where('guruId', 'G01')
            ->where('tgl', date('Y-m-d'));
        $this->assertCount(1, $records);

        $this->from(route('guru.presensi'))
            ->post(route('guru.presensi.store'), [
                'jarak' => -1,
                'lat' => 3.5952,
                'lng' => 98.6722,
            ])
            ->assertSessionHasErrors('jarak');
    }

    public function test_teacher_can_select_only_subjects_assigned_in_their_schedule(): void
    {
        $this->withSession([
            'user' => ['role' => 'guru', 'nama' => 'Budi Santoso', 'guruId' => 'G01'],
        ]);
        $jadwal = Sekolah::jadwal();
        $jadwal[] = [
            'idGuru' => 'G01',
            'hari' => 'Jumat',
            'jamKe' => 1,
            'kelas' => 'X-2',
            'mapel' => 'Informatika',
            'ruang' => 'Lab-Kom',
        ];
        Sekolah::put('jadwal', $jadwal);

        $this->get(route('guru.nilai', ['mapel' => 'Informatika', 'kelas' => 'X-2']))
            ->assertOk()
            ->assertSee('Mata Pelajaran')
            ->assertSee('Informatika')
            ->assertSee('X-2');

        $this->from(route('guru.nilai'))
            ->post(route('guru.nilai.store'), [
                'mapel' => 'Fisika',
                'kelas' => 'X-2',
                'nilai' => [],
            ])
            ->assertSessionHasErrors('mapel');
    }

    public function test_teacher_can_record_attendance_and_manage_grades_only_for_assigned_classes(): void
    {
        $this->withSession([
            'user' => ['role' => 'guru', 'nama' => 'Budi Santoso', 'guruId' => 'G01'],
        ]);

        $attendance = array_fill(0, count(Sekolah::siswaKelas('X-1')), 'Hadir');
        $attendance[0] = 'Sakit';
        $attendance[1] = 'Terlambat';

        $attendancePage = $this->get(route('guru.absensi-siswa'));
        $attendancePage
            ->assertOk()
            ->assertSee('Tandai Semua Hadir')
            ->assertSee('Catatan / Keterangan')
            ->assertSee('data-mark-all-present', false)
            ->assertSee('status-hadir', false)
            ->assertSee('Terlambat')
            ->assertSee('data-attendance-note-wrap', false)
            ->assertSee('guru-attendance-note', false)
            ->assertSee('guru-mark-all-present', false)
            ->assertSee('Simpan Absensi Kelas')
            ->assertSee('guru-save-button', false)
            ->assertSee('class="btn btn-primary guru-save-button', false)
            ->assertSee("button.classList.add('is-saving')", false)
            ->assertSee('id="student-form-modal"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('data-student-modal-close', false)
            ->assertSee('Simpan Siswa Baru')
            ->assertSee('Pilihan orang tua/wali');
        $this->assertMatchesRegularExpression('/data-attendance-note-wrap\s+hidden/', $attendancePage->getContent());
        $this->assertStringContainsString("['izin', 'terlambat', 'sakit'].includes(status)", $attendancePage->getContent());

        $this->from(route('guru.absensi-siswa'))
            ->post(route('guru.absensi-siswa.store'), [
                'kelas' => 'X-1',
                'tanggal' => date('Y-m-d'),
                'status' => array_fill(1, count($attendance), 'Hadir'),
            ])
            ->assertSessionHasErrors('status');

        $this->post(route('guru.absensi-siswa.store'), [
            'kelas' => 'X-1',
            'tanggal' => date('Y-m-d'),
            'status' => $attendance,
            'catatan' => [0 => 'Sedang kurang sehat', 1 => 'Datang terlambat'],
        ])->assertRedirect(route('guru.absensi-siswa', [
            'kelas' => 'X-1',
            'tanggal' => date('Y-m-d'),
        ]));

        $this->assertSame(
            'Sakit',
            Sekolah::absensiSiswa('X-1', date('Y-m-d'))['Adi Nugroho']
        );
        $this->assertSame(
            'Sedang kurang sehat',
            Sekolah::get('catatan_absensi_siswa')['X-1'][date('Y-m-d')]['Adi Nugroho']
        );
        $siswaTerlambat = collect(Sekolah::siswaKelas('X-1'))->sort()->values()[1];
        $this->assertSame(
            'Terlambat',
            Sekolah::absensiSiswa('X-1', date('Y-m-d'))[$siswaTerlambat]
        );
        $this->assertSame(
            'Datang terlambat',
            Sekolah::get('catatan_absensi_siswa')['X-1'][date('Y-m-d')][$siswaTerlambat]
        );
        $this->get(route('guru.absensi-siswa', [
            'kelas' => 'X-1',
            'tanggal' => date('Y-m-d'),
        ]))
            ->assertOk()
            ->assertSee('value="Sedang kurang sehat"', false)
            ->assertSee('guru-save-button is-saved', false);
        $this->assertSame('Hadir', Sekolah::absensiSiswa('X-1', '2026-01-01')['Adi Nugroho']);

        $this->from(route('guru.nilai'))
            ->post(route('guru.nilai.store'), [
                'kelas' => 'XI IPS 1',
                'mapel' => 'Matematika',
                'nilai' => [],
            ])
            ->assertSessionHasErrors('kelas');

        $this->post(route('guru.nilai.store'), [
            'kelas' => 'X-1',
            'mapel' => 'Matematika',
            'nilai' => [
                [
                    'tugas' => 100,
                    'kuis' => 100,
                    'uh' => 100,
                    'uts' => 100,
                    'uas' => 100,
                ],
            ],
        ])->assertSessionHasErrors('nilai');

        $grades = Sekolah::nilai('X-1', 'Matematika');
        foreach ($grades as &$grade) {
            $grade['tugas'] = 100;
            $grade['kuis'] = 100;
            $grade['uh'] = 100;
            $grade['uts'] = 100;
            $grade['uas'] = 100;
        }
        unset($grade);

        $nilaiValid = array_map(
            fn (array $grade) => array_intersect_key(
                $grade,
                array_flip(['tugas', 'kuis', 'uh', 'uts', 'uas'])
            ),
            $grades
        );
        $this->from(route('guru.nilai'))
            ->post(route('guru.nilai.store'), [
                'kelas' => 'X-1',
                'mapel' => 'Matematika',
                'nilai' => array_combine(range(1, count($nilaiValid)), array_values($nilaiValid)),
            ])
            ->assertSessionHasErrors('nilai');

        $this->post(route('guru.nilai.store'), [
            'kelas' => 'X-1',
            'mapel' => 'Matematika',
            'nilai' => $nilaiValid,
        ])->assertRedirect(route('guru.nilai', ['kelas' => 'X-1', 'mapel' => 'Matematika']));

        $this->assertSame(100, nilai_akhir(Sekolah::nilai('X-1', 'Matematika')[0]));
        $gradePage = $this->get(route('guru.nilai', ['kelas' => 'X-1', 'mapel' => 'Matematika']));
        $gradePage->assertOk()
            ->assertSee('Impor Nilai (Excel)')
            ->assertSee('Ekspor Rekap')
            ->assertSee('Catatan Rapor')
            ->assertSee('guru-grade-result', false)
            ->assertSee('Tuntas')
            ->assertSee('data-grade-view-toggle', false)
            ->assertSee('id="grade-report-preview"', false)
            ->assertSee('Nilai Akhir Semester')
            ->assertSee('Catatan Rapor / Capaian Kompetensi')
            ->assertSee('guru-save-button is-saved', false)
            ->assertSee('guru-grade-page-card', false)
            ->assertDontSee('href="'.route('guru.rapor', ['kelas' => 'X-1', 'mapel' => 'Matematika']).'"', false);

        $importLines = ['Nama Siswa,Tugas,Kuis,UH,UTS,UAS,Catatan Rapor'];
        foreach (Sekolah::nilai('X-1', 'Matematika') as $index => $grade) {
            $importLines[] = implode(',', [
                $grade['siswa'],
                $index === 0 ? 50 : 80,
                80,
                80,
                80,
                80,
                $index === 0 ? 'Perlu penguatan materi' : '',
            ]);
        }
        $this->from(route('guru.nilai', ['kelas' => 'X-1', 'mapel' => 'Matematika']))
            ->post(route('guru.nilai.impor'), [
                'kelas' => 'X-1',
                'mapel' => 'Matematika',
                'file' => UploadedFile::fake()->createWithContent('nilai.csv', implode("\n", $importLines)),
            ])
            ->assertRedirect(route('guru.nilai', ['kelas' => 'X-1', 'mapel' => 'Matematika']))
            ->assertSessionHas('sukses', 'Nilai siswa berhasil diimpor.');
        $this->assertSame('Perlu penguatan materi', Sekolah::get('catatan_nilai_siswa')['X-1|Matematika']['Adi Nugroho']);
        $this->assertEquals(50, Sekolah::nilai('X-1', 'Matematika')[0]['tugas']);
        $this->get(route('guru.nilai', ['kelas' => 'X-1', 'mapel' => 'Matematika']))
            ->assertOk()
            ->assertSee('Perlu penguatan materi')
            ->assertSee('Remedial')
            ->assertSee('Nilai Akhir Semester');

        $gradeExport = $this->get(route('guru.nilai.ekspor', ['kelas' => 'X-1', 'mapel' => 'Matematika']));
        $gradeExport->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $gradeSheet = $this->sheetXmlFromXlsx($gradeExport->getContent());
        $this->assertStringContainsString('Catatan Rapor', $gradeSheet);
        $this->assertStringContainsString('Perlu penguatan materi', $gradeSheet);
        $this->assertStringContainsString('Nilai Akhir', $gradeSheet);
        $this->from(route('guru.nilai', ['kelas' => 'X-1', 'mapel' => 'Matematika']))
            ->post(route('guru.nilai.impor'), [
                'kelas' => 'X-1',
                'mapel' => 'Matematika',
                'file' => UploadedFile::fake()->createWithContent('nilai.xlsx', $gradeExport->getContent()),
            ])
            ->assertRedirect(route('guru.nilai', ['kelas' => 'X-1', 'mapel' => 'Matematika']))
            ->assertSessionHas('sukses', 'Nilai siswa berhasil diimpor.');

        $this->get(route('guru.rapor', ['kelas' => 'X-1']))
            ->assertOk()
            ->assertSee('Adi Nugroho')
            ->assertSee('Laporan Absensi Siswa')
            ->assertSee('Total Siswa Aktif')
            ->assertSee('Rata-rata Kehadiran Kelas')
            ->assertSee('Akumulasi Ketidakhadiran')
            ->assertSee('Hadir (H)')
            ->assertSee('Sakit (S)')
            ->assertSee('Izin (I)')
            ->assertSee('Alpa (A)')
            ->assertSee('Total Kehadiran')
            ->assertSee('Nama Siswa')
            ->assertSee('Persentase Kehadiran (%)')
            ->assertSee('student-report-table', false)
            ->assertSee('student-report-print-area', false)
            ->assertDontSee('student-report-calendar', false)
            ->assertDontSee('attendance-calendar');

        $export = $this->get(route('guru.rapor.ekspor', [
            'kelas' => 'X-1',
            'bulan' => date('Y-m'),
        ]));
        $export->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $reportSheet = $this->sheetXmlFromXlsx($export->getContent());
        $this->assertStringContainsString('Laporan Absensi Siswa', $reportSheet);
        $this->assertStringContainsString('Hadir (H)', $reportSheet);
        $this->assertStringContainsString('Total Kehadiran', $reportSheet);
        $this->assertStringContainsString('Persentase Kehadiran', $reportSheet);
        $this->assertStringContainsString('Adi Nugroho', $reportSheet);
    }
}
