<?php
use PHPUnit\Framework\TestCase;

/**
 * PENGUJIAN UNIT SISTEM (WHITE BOX TESTING & PHPUNIT)
 * Target Method: kirimDataRequest($dataForm, $daftarBerkas)
 */
class ControlRequestInternalTest extends TestCase
{
    private ControlRequestInternal $controller;

    protected function setUp(): void
    {
        $this->controller = new ControlRequestInternal();
    }

    // =========================================================================
    // BAGIAN 1: PENGUJIAN BASIS PATH TESTING (4 JALUR INDEPENDEN)
    // =========================================================================

    /**
     * TC-01: Menguji Path 1 (1 -> 2 -> 3 -> 8)
     * Kondisi: Keputusan D1 = True (lengkap == FALSE ?)
     * Node  : Node 1 -> Node 2 -> Node 3 -> Node 8
     */
    public function test_path_1_form_tidak_lengkap_indikator_error(): void
    {
        $dataForm = [
            'judulRequest' => '', // Judul kosong -> form tidak lengkap
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi sosialisasi pembukaan beasiswa.'
        ];
        $daftarBerkas = [];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertFalse($hasil, 'Path 1: Seharusnya mengembalikan false dan memicu tampilIndikatorError().');
    }

    /**
     * TC-02: Menguji Path 2 (1 -> 2 -> 4 -> 7 -> 8)
     * Kondisi: Keputusan D1 = False (lengkap == TRUE), D2 = False (berkas kosong / tidak ada loop)
     * Node  : Node 1 -> Node 2 -> Node 4 -> Node 7 -> Node 8
     */
    public function test_path_2_form_lengkap_tanpa_lampiran_berkas(): void
    {
        $dataForm = [
            'judulRequest' => 'Ucapan Hari Besar Nasional',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Naskah ucapan peringatan hari pahlawan nasional.'
        ];
        $daftarBerkas = []; // Array berkas kosong -> alur tanpa lampiran

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertTrue($hasil, 'Path 2: Seharusnya lolos ke preview karena lampiran berkas bersifat opsional.');
    }

    /**
     * TC-03: Menguji Path 3 (1 -> 2 -> 4 -> 5 -> 6 -> 8)
     * Kondisi: Keputusan D1 = False, D2 = True (ada loop), D3 = True (valid == FALSE ?)
     * Node  : Node 1 -> Node 2 -> Node 4 -> Node 5 -> Node 6 -> Node 8
     */
    public function test_path_3_form_lengkap_berkas_tidak_valid(): void
    {
        $dataForm = [
            'judulRequest' => 'Workshop Cybersecurity',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi pengantar keamanan sistem informasi.'
        ];
        $daftarBerkas = [
            ['name' => 'payload_script.exe', 'size' => 500 * 1024, 'error' => UPLOAD_ERR_OK] // Format .exe dilarang
        ];

        $hasil = $this->controller->kirimDataRequest($dataForm, $daftarBerkas);
        $this->assertFalse($hasil, 'Path 3: Seharusnya mengembalikan false dan memicu tampilPeringatanBerkas().');
    }

    /**
     * TC-04: Menguji Path 4 (1 -> 2 -> 4 -> 5 -> 4 -> 7 -> 8)
     * Kondisi: Keputusan D1 = False, D2 = True, D3 = False (valid == TRUE, loop selesai -> preview)
     * Node  : Node 1 -> Node 2 -> Node 4 -> Node 5 -> (loop back) -> Node 4 -> Node 7 -> Node 8
     */
    public function test_path_4_form_lengkap_berkas_valid_berhasil_kirim(): void
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
        $this->assertTrue($hasil, 'Path 4: Seharusnya lolos ke preview karena form dan berkas PDF valid.');
    }

    // =========================================================================
    // BAGIAN 2: PENGUJIAN BOUNDARY VALUE ANALYSIS (BVA) & EQUIVALENCE PARTITIONING
    // =========================================================================

    /**
     * TC-05: Variasi Form - Judul Hanya Berisi Spasi (Whitespace)
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
     * TC-06: Variasi Form - Tanggal Pengajuan Kosong
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
     * TC-07: Variasi Form - Detail Konten Kosong
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
     * TC-08: Variasi Form - Seluruh Field Form Kosong Total
     */
    public function test_tc08_gagal_karena_seluruh_form_kosong(): void
    {
        $hasil = $this->controller->kirimDataRequest([]);
        $this->assertFalse($hasil, 'Seharusnya ditolak jika seluruh dataForm kosong.');
    }

    /**
     * TC-09: Variasi Berkas - Gambar PNG Valid
     */
    public function test_tc09_berkas_gambar_png_valid(): void
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
        $this->assertTrue($hasil, 'Seharusnya lolos dengan format PNG valid <= 10 MB.');
    }

    /**
     * TC-10: Variasi Berkas - Format Berkas Arsip (.zip)
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
     * TC-11: Variasi Berkas - Ukuran Melebihi Batas (> 10 MB)
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
     * TC-12: Variasi Berkas - Status Error Unggahan (Corrupt)
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
     * TC-13: Variasi Berkas - Multi-Berkas: Salah Satu Berkas Tidak Valid (.xlsx)
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
