<?php
/**
 * TEST RUNNER & SUITE PENGUJIAN SKENARIO (WHITE BOX TESTING)
 * Target Pengujian : ControlRequestInternal::kirimDataRequest()
 * Acuan SDD       : Bab 3.4 Package Pengajuan & Bab 3.6 (PC-CRI-02)
 * Acuan Modul     : Modul Praktikum Pengujian Perangkat Lunak (White Box Testing)
 * File            : tests/test_kirim_data_request.php
 * Keterangan      : Dapat dijalankan via CLI (Terminal) maupun Browser (HTTP)
 */

require_once __DIR__ . '/../controllers/pengajuan/ControlRequestInternal.php';

// Inisialisasi Class Controller yang diuji
$controller = new ControlRequestInternal();

// Kumpulan Skenario Pengujian (Test Cases)
$testCases = [
    // --- BASIS PATH TESTING (4 JALUR INDEPENDEN) ---
    [
        'id' => 'TC-01',
        'nama' => 'Path 1: Form Tidak Lengkap (Judul Kosong) -> Indikator Error',
        'kategori' => 'Basis Path 1 (D1 = True)',
        'path' => '1 -> 2 -> 3 -> 8',
        'dataForm' => [
            'judulRequest' => '',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi sosialisasi pembukaan beasiswa.'
        ],
        'berkas' => [],
        'ekspektasi' => false,
        'keterangan' => 'Node 1 -> 2 -> 3 -> 8. Memvalidasi D1 bernilai True (lengkap == FALSE) dan memicu tampilIndikatorError().'
    ],
    [
        'id' => 'TC-02',
        'nama' => 'Path 2: Form Lengkap Tanpa Berkas Lampiran -> Lolos ke Preview',
        'kategori' => 'Basis Path 2 (D1 = False, D2 = False)',
        'path' => '1 -> 2 -> 4 -> 7 -> 8',
        'dataForm' => [
            'judulRequest' => 'Ucapan Hari Besar Nasional',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Naskah ucapan peringatan hari pahlawan nasional.'
        ],
        'berkas' => [],
        'ekspektasi' => true,
        'keterangan' => 'Node 1 -> 2 -> 4 -> 7 -> 8. Lampiran berkas kosong, loop di Node 4 dilewati, langsung ke Node 7.'
    ],
    [
        'id' => 'TC-03',
        'nama' => 'Path 3: Form Lengkap, Berkas Tidak Sesuai Ketentuan (.exe) -> Peringatan Berkas',
        'kategori' => 'Basis Path 3 (D1 = False, D2 = True, D3 = True)',
        'path' => '1 -> 2 -> 4 -> 5 -> 6 -> 8',
        'dataForm' => [
            'judulRequest' => 'Workshop Cybersecurity',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi pengantar keamanan sistem informasi.'
        ],
        'berkas' => [
            ['name' => 'payload_script.exe', 'size' => 500 * 1024, 'error' => UPLOAD_ERR_OK]
        ],
        'ekspektasi' => false,
        'keterangan' => 'Node 1 -> 2 -> 4 -> 5 -> 6 -> 8. Berkas terlarang (.exe) ditolak di Node 5 dan memicu tampilPeringatanBerkas().'
    ],
    [
        'id' => 'TC-04',
        'nama' => 'Path 4: Form Lengkap, Berkas Valid (PDF 2MB) -> Berhasil Kirim Preview',
        'kategori' => 'Basis Path 4 (D1 = False, D2 = True, D3 = False)',
        'path' => '1 -> 2 -> 4 -> 5 -> 4 -> 7 -> 8',
        'dataForm' => [
            'judulRequest' => 'Sosialisasi Beasiswa Filkom 2026',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi sosialisasi pembukaan beasiswa semester genap.'
        ],
        'berkas' => [
            ['name' => 'panduan-beasiswa.pdf', 'size' => 2 * 1024 * 1024, 'error' => UPLOAD_ERR_OK]
        ],
        'ekspektasi' => true,
        'keterangan' => 'Node 1 -> 2 -> 4 -> 5 -> 4 -> 7 -> 8. Berkas valid di Node 5, loop kembali ke Node 4, loop selesai ke Node 7 lalu Node 8.'
    ],

    // --- BOUNDARY VALUE ANALYSIS & EQUIVALENCE PARTITIONING ---
    [
        'id' => 'TC-05',
        'nama' => 'Variasi Form: Gagal karena Judul Hanya Whitespace (Spasi)',
        'kategori' => 'BVA: Validasi Form',
        'path' => '1 -> 2 -> 3',
        'dataForm' => [
            'judulRequest' => '     ',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Detail naskah ada namun judul hanya spasi.'
        ],
        'berkas' => [],
        'ekspektasi' => false,
        'keterangan' => 'Fungsi trim() mendeteksi string spasi kosong dan memicu indikator error.'
    ],
    [
        'id' => 'TC-06',
        'nama' => 'Variasi Form: Gagal karena Tanggal Pengajuan Kosong',
        'kategori' => 'BVA: Validasi Form',
        'path' => '1 -> 2 -> 3',
        'dataForm' => [
            'judulRequest' => 'Webinar Kepemimpinan',
            'tanggalPengajuan' => '',
            'detailKonten' => 'Naskah publikasi webinar.'
        ],
        'berkas' => [],
        'ekspektasi' => false,
        'keterangan' => 'Field tanggalPengajuan wajib ada untuk penentuan antrean publikasi.'
    ],
    [
        'id' => 'TC-07',
        'nama' => 'Variasi Form: Gagal karena Detail Konten / Caption Kosong',
        'kategori' => 'BVA: Validasi Form',
        'path' => '1 -> 2 -> 3',
        'dataForm' => [
            'judulRequest' => 'Lomba Esai Nasional 2026',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => ''
        ],
        'berkas' => [],
        'ekspektasi' => false,
        'keterangan' => 'Field detailKonten (naskah caption) tidak boleh kosong.'
    ],
    [
        'id' => 'TC-08',
        'nama' => 'Variasi Form: Gagal karena Seluruh Form Kosong Total',
        'kategori' => 'BVA: Validasi Form',
        'path' => '1 -> 2 -> 3',
        'dataForm' => [],
        'berkas' => [],
        'ekspektasi' => false,
        'keterangan' => 'Array form kosong memicu penolakan kelengkapan formulir.'
    ],
    [
        'id' => 'TC-09',
        'nama' => 'Variasi Berkas: Format Gambar PNG Valid (1.5 MB)',
        'kategori' => 'BVA: Validasi Berkas',
        'path' => '1 -> 2 -> 4 -> 5 -> 6 -> 8',
        'dataForm' => [
            'judulRequest' => 'Open Recruitment Staff Ahli BEM 2026',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Pendaftaran staf ahli dibuka untuk seluruh mahasiswa.'
        ],
        'berkas' => [
            ['name' => 'poster-oprec.png', 'size' => 1.5 * 1024 * 1024, 'error' => UPLOAD_ERR_OK]
        ],
        'ekspektasi' => true,
        'keterangan' => 'Format berkas PNG di bawah batas 10 MB dan isian form lengkap.'
    ],
    [
        'id' => 'TC-10',
        'nama' => 'Variasi Berkas: Gagal karena Format Berkas Arsip (.zip)',
        'kategori' => 'BVA: Validasi Berkas',
        'path' => '1 -> 2 -> 4 -> 5 -> 6 -> 7',
        'dataForm' => [
            'judulRequest' => 'Festival Musik Kampus',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Publikasi festival tahunan.'
        ],
        'berkas' => [
            ['name' => 'arsip_materi.zip', 'size' => 2 * 1024 * 1024, 'error' => UPLOAD_ERR_OK]
        ],
        'ekspektasi' => false,
        'keterangan' => 'Hanya format JPG, PNG, dan PDF yang didukung sesuai ketentuan SOP CMI.'
    ],
    [
        'id' => 'TC-11',
        'nama' => 'Variasi Berkas: Gagal karena Ukuran Berkas Melebihi Batas (> 10 MB)',
        'kategori' => 'BVA: Validasi Berkas',
        'path' => '1 -> 2 -> 4 -> 5 -> 6 -> 7',
        'dataForm' => [
            'judulRequest' => 'Dokumentasi Video Sinematik',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi publikasi sinematik profil biro.'
        ],
        'berkas' => [
            ['name' => 'video_mentah.pdf', 'size' => 15 * 1024 * 1024, 'error' => UPLOAD_ERR_OK]
        ],
        'ekspektasi' => false,
        'keterangan' => 'Ukuran berkas 15 MB melebihi batas maksimal 10 MB yang ditentukan.'
    ],
    [
        'id' => 'TC-12',
        'nama' => 'Variasi Berkas: Gagal karena Error Status Unggahan (Corrupt)',
        'kategori' => 'BVA: Validasi Berkas',
        'path' => '1 -> 2 -> 4 -> 5 -> 6 -> 7',
        'dataForm' => [
            'judulRequest' => 'Bazar Kewirausahaan',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Materi publikasi bazar UMKM mahasiswa.'
        ],
        'berkas' => [
            ['name' => 'poster.jpg', 'size' => 500 * 1024, 'error' => UPLOAD_ERR_PARTIAL]
        ],
        'ekspektasi' => false,
        'keterangan' => 'Error code UPLOAD_ERR_PARTIAL menandakan transmisi berkas terputus di tengah jalan.'
    ],
    [
        'id' => 'TC-13',
        'nama' => 'Variasi Berkas: Multi-Berkas — Salah Satu Berkas Tidak Valid (.xlsx)',
        'kategori' => 'BVA: Validasi Berkas',
        'path' => '1 -> 2 -> 4 -> 5 -> 6 -> 7',
        'dataForm' => [
            'judulRequest' => 'Riset Opini Publik Mahasiswa',
            'tanggalPengajuan' => '2026-11-24',
            'detailKonten' => 'Infografis hasil survei kepuasan fasilitas kampus.'
        ],
        'berkas' => [
            ['name' => 'infografis-valid.pdf', 'size' => 1 * 1024 * 1024, 'error' => UPLOAD_ERR_OK],
            ['name' => 'data_mentah.xlsx', 'size' => 500 * 1024, 'error' => UPLOAD_ERR_OK]
        ],
        'ekspektasi' => false,
        'keterangan' => 'Jika ada salah satu berkas yang tidak valid, seluruh transaksi request ditolak.'
    ]
];

// Eksekusi Seluruh Pengujian
$hasilPengujian = [];
$totalLolos = 0;
$totalGagal = 0;
$mulaiWaktu = microtime(true);

foreach ($testCases as $tc) {
    $hasilAktual = $controller->kirimDataRequest($tc['dataForm'], $tc['berkas']);
    $statusLolos = ($hasilAktual === $tc['ekspektasi']);

    if ($statusLolos) {
        $totalLolos++;
    } else {
        $totalGagal++;
    }

    $hasilPengujian[] = [
        'id' => $tc['id'],
        'nama' => $tc['nama'],
        'kategori' => $tc['kategori'],
        'path' => $tc['path'],
        'dataForm' => $tc['dataForm'],
        'berkas' => $tc['berkas'],
        'ekspektasi' => $tc['ekspektasi'],
        'aktual' => $hasilAktual,
        'status' => $statusLolos,
        'keterangan' => $tc['keterangan']
    ];
}

$durasi = round((microtime(true) - $mulaiWaktu) * 1000, 2);

// ----------------------------------------------------------------------------
// MODE 1: TAMPILAN CLI (Jika dijalankan lewat terminal `php tests/test_kirim_data_request.php`)
// ----------------------------------------------------------------------------
if (php_sapi_name() === 'cli') {
    echo "=========================================================================\n";
    echo "  TEST SUITE: ControlRequestInternal::kirimDataRequest()\n";
    echo "  Acuan Modul : White Box Testing (Basis Path, Statement, Branch Coverage)\n";
    echo "=========================================================================\n\n";

    foreach ($hasilPengujian as $h) {
        $badge = $h['status'] ? "[PASS]" : "[FAIL]";
        echo sprintf("%s %s : %s\n", $badge, $h['id'], $h['nama']);
        echo sprintf("       Path      : %s\n", $h['path']);
        echo sprintf(
            "       Ekspektasi: %s | Aktual: %s\n",
            $h['ekspektasi'] ? 'TRUE (Lolos)' : 'FALSE (Ditolak)',
            $h['aktual'] ? 'TRUE (Lolos)' : 'FALSE (Ditolak)'
        );
        echo sprintf("       Catatan   : %s\n\n", $h['keterangan']);
    }

    echo "-------------------------------------------------------------------------\n";
    echo sprintf(
        "HASIL PENGUJIAN: %d/%d Skenario Berhasil Lolos (%s ms)\n",
        $totalLolos,
        count($testCases),
        $durasi
    );
    if ($totalGagal === 0) {
        echo "KESIMPULAN: SELURUH SKENARIO TESTING BERJALAN SEMPURNA (100% PASS)\n";
    } else {
        echo sprintf("PERHATIAN: Terdapat %d skenario yang gagal!\n", $totalGagal);
    }
    echo "=========================================================================\n";
    exit(0);
}

// ----------------------------------------------------------------------------
// MODE 2: TAMPILAN BROWSER
// ----------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>White Box Testing: ControlRequestInternal — CMI Portal</title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/portal.css">
    <style>
        .test-container {
            max-width: 1040px;
            margin: 32px auto;
            padding: 0 16px;
        }

        .test-header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 14px rgba(27, 58, 158, 0.05);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .stat-card {
            padding: 16px 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .stat-card.pass {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        .stat-card.fail {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .stat-num {
            font-size: 28px;
            font-weight: 800;
            line-height: 1.1;
        }

        .stat-lbl {
            font-size: 13px;
            font-weight: 600;
            margin-top: 4px;
            opacity: 0.85;
        }

        .case-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.2s ease;
        }

        .case-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .case-card.pass {
            border-left: 6px solid #22c55e;
        }

        .case-card.fail {
            border-left: 6px solid #ef4444;
        }

        .badge-pass {
            background: #dcfce7;
            color: #15803d;
            font-weight: 700;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 99px;
            text-transform: uppercase;
        }

        .badge-fail {
            background: #fee2e2;
            color: #b91c1c;
            font-weight: 700;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 99px;
            text-transform: uppercase;
        }

        .badge-path {
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 6px;
            font-family: monospace;
        }

        .data-dump {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            font-family: Consolas, monospace;
            font-size: 12px;
            color: #334155;
            margin-top: 10px;
            overflow-x: auto;
        }
    </style>
</head>

<body style="background: #f1f5f9;">
    <nav>
        <div class="brand">
            <img src="../assets/img/logo cmi.jpeg" alt="Logo CMI" class="logo">
            CMI BEM FILKOM UB — Test Suite
        </div>
        <div class="right">
            <a href="../index.php" style="color: inherit; text-decoration: none; font-weight: 600;">&larr; Kembali ke
                Portal</a>
        </div>
    </nav>

    <div class="test-container">
        <!-- Header Info Pengujian -->
        <div class="test-header">
            <span class="pill" style="margin-bottom: 8px;">White Box Testing &bull; Basis Path &amp; Coverage
                Analysis</span>
            <h1 style="color: #1b3a9e; font-size: 24px; margin: 6px 0 8px;">Pengujian Skenario:
                ControlRequestInternal::kirimDataRequest()</h1>
            <p style="color: #64748b; font-size: 14px; margin: 0; line-height: 1.5;">
                Verifikasi method logika bisnis pengiriman data request sesuai panduan Modul Praktikum Pengujian
                Perangkat Lunak:
                <strong>Statement Coverage</strong> (15 baris kode), <strong>Branch Coverage</strong> (Keputusan D1, D2,
                D3), dan <strong>Basis Path Testing</strong> (Cyclomatic Complexity V(G) = 4 jalur independen).
            </p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-num"><?= count($testCases) ?></div>
                    <div class="stat-lbl">Total Skenario Diuji</div>
                </div>
                <div class="stat-card pass">
                    <div class="stat-num"><?= $totalLolos ?></div>
                    <div class="stat-lbl">Skenario Lolos (PASS)</div>
                </div>
                <div class="stat-card <?= $totalGagal > 0 ? 'fail' : '' ?>">
                    <div class="stat-num"><?= $totalGagal ?></div>
                    <div class="stat-lbl">Skenario Gagal (FAIL)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-num"><?= $durasi ?> ms</div>
                    <div class="stat-lbl">Waktu Eksekusi</div>
                </div>
            </div>
        </div>

        <!-- Daftar Kartu Skenario Pengujian -->
        <h2 style="font-size: 18px; color: #0f172a; margin-bottom: 16px;">Rincian Eksekusi Kasus Uji (Test Cases)</h2>

        <?php foreach ($hasilPengujian as $h): ?>
            <div class="case-card <?= $h['status'] ? 'pass' : 'fail' ?>">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                            <span
                                style="font-size: 12px; font-weight: 700; color: #64748b;"><?= htmlspecialchars($h['id']) ?>
                                &bull; <?= htmlspecialchars($h['kategori']) ?></span>
                            <span class="badge-path">Path: <?= htmlspecialchars($h['path']) ?></span>
                        </div>
                        <h3 style="font-size: 16px; color: #1e293b; margin: 4px 0;"><?= htmlspecialchars($h['nama']) ?></h3>
                    </div>
                    <span class="<?= $h['status'] ? 'badge-pass' : 'badge-fail' ?>">
                        <?= $h['status'] ? '✓ PASS' : '✗ FAIL' ?>
                    </span>
                </div>

                <p style="font-size: 13.5px; color: #475569; margin: 6px 0; line-height: 1.5;">
                    <?= htmlspecialchars($h['keterangan']) ?>
                </p>

                <div
                    style="display: flex; gap: 24px; font-size: 13px; margin-top: 12px; padding: 8px 12px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <div>
                        <strong>Nilai Ekspektasi:</strong>
                        <code
                            style="color: #0369a1; font-weight: 700;"><?= $h['ekspektasi'] ? 'true (Lolos ke Preview)' : 'false (Ditolak / Error UI)' ?></code>
                    </div>
                    <div>
                        <strong>Nilai Aktual:</strong>
                        <code
                            style="color: <?= $h['status'] ? '#15803d' : '#b91c1c' ?>; font-weight: 700;"><?= $h['aktual'] ? 'true (Lolos ke Preview)' : 'false (Ditolak / Error UI)' ?></code>
                    </div>
                </div>

                <div class="data-dump">
                    <strong>Input $dataForm:</strong> <?= json_encode($h['dataForm'], JSON_UNESCAPED_UNICODE) ?><br>
                    <strong>Input $daftarBerkas:</strong>
                    <?= !empty($h['berkas']) ? json_encode($h['berkas'], JSON_UNESCAPED_UNICODE) : '[] (Tanpa Lampiran)' ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <footer class="foot">&copy; 2026 Biro Center of Media and Information (CMI) &bull; BEM FILKOM Universitas Brawijaya.
    </footer>
</body>

</html>