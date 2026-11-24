<?php
use PHPUnit\Framework\TestCase;

/**
 * PENGUJIAN UNIT SISTEM (PHPUNIT STANDAR)
 * Target Kelas : ControlRequestInternal
 * Target Method: kirimDataRequest($dataForm, $daftarBerkas)
 * Acuan SDD   : Bab 3.4 & Bab 3.6 (PC-CRI-02)
 * File         : tests/ControlRequestInternalTest.php
 */
class ControlRequestInternalTest extends TestCase
{
    private ControlRequestInternal $controller;

    protected function setUp(): void
    {
        $this->controller = new ControlRequestInternal();
    }

    /**
     * TC-01: Alur Normal - Form Lengkap dengan Berkas PDF Valid
     */
    public function test_tc01_form_lengkap_dan_berkas_pdf_valid(): void
    {
        $dataForm = [
            'judulRequest' => 'Sosialisasi Beasiswa Filkom 2026',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi sosialisasi pembukaan beasiswa semester genap.'
        ];
        $daftarBerkas = [
            ['name' => 'panduan-beasiswa.pdf', 'size' => 2 * 1024 * 1024, 'error' => UPLOAD_ERR_OK]
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertTrue($hasil, 'Seharusnya lolos karena seluruh form dan berkas PDF valid.');
    }

    /**
     * TC-02: Alur Normal - Form Lengkap dengan Berkas Gambar (PNG/JPG) Valid
     */
    public function test_tc02_form_lengkap_dan_berkas_gambar_valid(): void
    {
        $dataForm = [
            'judulRequest' => 'Open Recruitment Staff Ahli BEM 2026',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Pendaftaran staf ahli dibuka untuk seluruh mahasiswa.'
        ];
        $daftarBerkas = [
            ['name' => 'poster-oprec.png', 'size' => 1.5 * 1024 * 1024, 'error' => UPLOAD_ERR_OK]
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertTrue($hasil, 'Seharusnya lolos dengan berkas gambar format PNG <= 10 MB.');
    }

    /**
     * TC-03: Alur Normal - Form Lengkap Tanpa Berkas Lampiran (Opsional)
     */
    public function test_tc03_form_lengkap_tanpa_lampiran_berkas(): void
    {
        $dataForm = [
            'judulRequest' => 'Ucapan Hari Besar Nasional',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Naskah ucapan peringatan hari pahlawan nasional.'
        ];
        $daftarBerkas = [];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertTrue($hasil, 'Seharusnya lolos karena lampiran berkas bersifat opsional.');
    }

    /**
     * TC-04: Alur Alternatif - Gagal karena Judul Request Kosong
     */
    public function test_tc04_gagal_karena_judul_request_kosong(): void
    {
        $dataForm = [
            'judulRequest' => '',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Detail naskah terisi namun judul terlewatkan.'
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika judulRequest kosong.');
    }

    /**
     * TC-05: Alur Alternatif - Gagal karena Judul Hanya Whitespace
     */
    public function test_tc05_gagal_karena_judul_hanya_whitespace(): void
    {
        $dataForm = [
            'judulRequest' => '     ',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Detail naskah ada namun judul hanya spasi.'
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika judul hanya spasi kosong.');
    }

    /**
     * TC-06: Alur Alternatif - Gagal karena Tanggal Pengajuan Kosong
     */
    public function test_tc06_gagal_karena_tanggal_pengajuan_kosong(): void
    {
        $dataForm = [
            'judulRequest' => 'Webinar Kepemimpinan',
            'tanggalPengajuan' => '',
            'detailKonten' => 'Naskah publikasi webinar.'
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika tanggalPengajuan kosong.');
    }

    /**
     * TC-07: Alur Alternatif - Gagal karena Detail Konten / Caption Kosong
     */
    public function test_tc07_gagal_karena_detail_konten_kosong(): void
    {
        $dataForm = [
            'judulRequest' => 'Lomba Esai Nasional 2026',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => ''
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika detailKonten kosong.');
    }

    /**
     * TC-08: Alur Alternatif - Gagal karena Form Kosong Total
     */
    public function test_tc08_gagal_karena_seluruh_form_kosong(): void
    {
        $hasil = $this->controller->kirimDataRequest([]);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika seluruh dataForm kosong.');
    }

    /**
     * TC-09: Alur Alternatif - Gagal karena Format Berkas Terlarang (.exe)
     */
    public function test_tc09_gagal_karena_format_berkas_terlarang_exe(): void
    {
        $dataForm = [
            'judulRequest' => 'Workshop Cybersecurity',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi pengantar keamanan sistem.'
        ];
        $daftarBerkas = [
            ['name' => 'payload_script.exe', 'size' => 500 * 1024, 'error' => UPLOAD_ERR_OK]
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika berkas berekstensi .exe.');
    }

    /**
     * TC-10: Alur Alternatif - Gagal karena Format Berkas Arsip (.zip)
     */
    public function test_tc10_gagal_karena_format_berkas_arsip_zip(): void
    {
        $dataForm = [
            'judulRequest' => 'Festival Musik Kampus',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Publikasi festival tahunan.'
        ];
        $daftarBerkas = [
            ['name' => 'arsip_materi.zip', 'size' => 2 * 1024 * 1024, 'error' => UPLOAD_ERR_OK]
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika berkas berupa arsip .zip.');
    }

    /**
     * TC-11: Alur Alternatif - Gagal karena Ukuran Berkas Melebihi Batas (> 10 MB)
     */
    public function test_tc11_gagal_karena_ukuran_berkas_melebihi_batas(): void
    {
        $dataForm = [
            'judulRequest' => 'Dokumentasi Video Sinematik',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi publikasi sinematik profil biro.'
        ];
        $daftarBerkas = [
            ['name' => 'video_mentah.pdf', 'size' => 15 * 1024 * 1024, 'error' => UPLOAD_ERR_OK] // 15 MB
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika berkas melebihi 10 MB.');
    }

    /**
     * TC-12: Alur Alternatif - Gagal karena Error Status Unggahan (Corrupt)
     */
    public function test_tc12_gagal_karena_error_status_unggahan(): void
    {
        $dataForm = [
            'judulRequest' => 'Bazar Kewirausahaan',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi publikasi bazar UMKM mahasiswa.'
        ];
        $daftarBerkas = [
            ['name' => 'poster.jpg', 'size' => 500 * 1024, 'error' => UPLOAD_ERR_PARTIAL]
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika terjadi error unggah berkas.');
    }

    /**
     * TC-13: Alur Alternatif - Multi-Berkas: Salah Satu Berkas Tidak Valid
     */
    public function test_tc13_gagal_karena_salah_satu_multi_berkas_tidak_valid(): void
    {
        $dataForm = [
            'judulRequest' => 'Riset Opini Publik Mahasiswa',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Infografis hasil survei kepuasan fasilitas kampus.'
        ];
        $daftarBerkas = [
            ['name' => 'infografis-valid.pdf', 'size' => 1 * 1024 * 1024, 'error' => UPLOAD_ERR_OK],
            ['name' => 'data_mentah.xlsx', 'size' => 500 * 1024, 'error' => UPLOAD_ERR_OK]
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika terdapat salah satu berkas yang tidak valid.');
    }
}
