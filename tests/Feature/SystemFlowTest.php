<?php

namespace Tests\Feature;

use App\Http\Middleware\RestoreRememberedLogin;
use App\Support\PasswordRecoveryStore;
use App\Support\Sekolah;
use Illuminate\Http\UploadedFile;
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
            ->assertSee('SIGAP')
            ->assertSee('SMA Pencawan')
            ->assertSee('Sistem Informasi Kehadiran & Agenda Pengajar')
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

        $this->get(route('admin.dashboard'))->assertOk();

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
            ->assertOk();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertArrayNotHasKey('rememberTokenHash', Sekolah::cariAkun('admin'));
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
            ->assertSee('SIGAP')
            ->assertSee('Sistem Informasi Kehadiran & Agenda Pengajar');

        $template = $this->get(route('admin.guru.template'));
        $template->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('nip,nama,mapel,status,username,email,password', $template->getContent());

        $response = $this->post(route('admin.guru.impor'), [
            'file' => UploadedFile::fake()->createWithContent(
                'guru.csv',
                "nip,nama,mapel,status,username,email,password\r\n123456789012,Guru Impor,Matematika,Aktif,guru-impor,guru.impor@example.test,password-impor-123\r\n"
            ),
        ]);
        $response->assertRedirect()
            ->assertSessionHas('sukses', '1 guru dan akun berhasil diimpor.')
            ->assertSessionMissing('imporGuru');

        $teacher = collect(Sekolah::guru())->firstWhere('nama', 'Guru Impor');
        $this->assertNotNull($teacher);
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

        $this->get(route('admin.dashboard'))->assertRedirect(route('guru.dashboard'));
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

        $this->post(route('admin.absensi.verifikasi'), [
            'id' => 1,
            'status' => 'Ditolak',
        ])->assertSessionHas('sukses');
        $this->assertSame('Ditolak', collect(Sekolah::absensiGuru())->firstWhere('id', 1)['verifikasi']);

        $this->post(route('admin.absensi.status'), [
            'id' => 1,
            'status' => 'Izin',
        ])->assertSessionHas('sukses', 'Status presensi berhasil diperbarui.');
        $this->assertSame('Izin', collect(Sekolah::absensiGuru())->firstWhere('id', 1)['status']);

        $this->get(route('admin.absensi', ['status' => 'Izin']))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertDontSee('Siti Aminah');
        $this->get('/admin/absensi?bulan[]=x')->assertOk();

        $export = $this->get(route('admin.absensi.ekspor'));
        $export->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $export->getContent());

        $sheet = $this->sheetXmlFromXlsx($export->getContent());
        $this->assertStringContainsString('Absensi Guru', $sheet);
        $this->assertStringContainsString('Nama Guru', $sheet);
        $this->assertStringContainsString('Ditolak', $sheet);

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
        $this->assertSame('Admin SMA Pencawan', session('user.nama'));

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
        $this->get(route('guru.dashboard'))
            ->assertOk()
            ->assertSee('Arahan Kepala Sekolah')
            ->assertSee('Rapat guru dimulai pukul 10.00.');
        $this->get(route('guru.notifikasi'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('items.0.title', 'Arahan Kepala Sekolah');
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

    public function test_teacher_can_record_attendance_and_manage_grades_only_for_assigned_classes(): void
    {
        $this->withSession([
            'user' => ['role' => 'guru', 'nama' => 'Budi Santoso', 'guruId' => 'G01'],
        ]);

        $attendance = array_fill(0, count(Sekolah::siswaKelas('X-1')), 'Hadir');
        $attendance[0] = 'Sakit';

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
        ])->assertRedirect(route('guru.absensi-siswa', [
            'kelas' => 'X-1',
            'tanggal' => date('Y-m-d'),
        ]));

        $this->assertSame(
            'Sakit',
            Sekolah::absensiSiswa('X-1', date('Y-m-d'))['Adi Nugroho']
        );
        $this->assertSame('Hadir', Sekolah::absensiSiswa('X-1', '2026-01-01')['Adi Nugroho']);

        $this->from(route('guru.nilai'))
            ->post(route('guru.nilai.store'), [
                'kelas' => 'XI IPS 1',
                'nilai' => [],
            ])
            ->assertSessionHasErrors('kelas');

        $this->post(route('guru.nilai.store'), [
            'kelas' => 'X-1',
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
                'nilai' => array_combine(range(1, count($nilaiValid)), array_values($nilaiValid)),
            ])
            ->assertSessionHasErrors('nilai');

        $this->post(route('guru.nilai.store'), [
            'kelas' => 'X-1',
            'nilai' => $nilaiValid,
        ])->assertRedirect(route('guru.nilai', ['kelas' => 'X-1']));

        $this->assertSame(100, nilai_akhir(Sekolah::nilai('X-1', 'Matematika')[0]));
        $this->get(route('guru.rapor', ['kelas' => 'X-1']))
            ->assertOk()
            ->assertSee('Adi Nugroho')
            ->assertSee('Tuntas');
    }
}
