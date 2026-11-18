<?php
require_once __DIR__ . '/../../config/koneksi.php';

// Ambil parameter tanggal & slot jam dari POST atau GET
$tanggalPublikasi = trim($_POST['tanggal_publikasi'] ?? ($_POST['tanggalPublikasi'] ?? ($_GET['tanggal'] ?? '')));
$jamPublikasi = trim($_POST['jam_publikasi'] ?? ($_POST['jamPublikasi'] ?? ($_GET['jam'] ?? '')));

$validJam = ['09:00', '13:00', '15:00', '19:00'];
$todayStr = date('Y-m-d');
$todayTs = strtotime($todayStr);

$targetTs = !empty($tanggalPublikasi) ? strtotime($tanggalPublikasi) : false;
$diffDays = ($targetTs !== false) ? (int) round(($targetTs - $todayTs) / 86400) : -1;
$dayOfWeek = ($targetTs !== false) ? (int) date('w', $targetTs) : -1; // 0 = Minggu

// Syarat 3: Wajib menerima $_GET['tanggal'] dan $_GET['jam'].
// Jika kosong, tidak valid, jatuh pada hari Minggu, atau selisih hari < 3 dari hari ini, redirect kembali ke kalender-jadwal.php
if (empty($tanggalPublikasi) || empty($jamPublikasi) || !in_array($jamPublikasi, $validJam, true) || $targetTs === false || $dayOfWeek === 0 || $diffDays < 3) {
    header("Location: kalender-jadwal.php");
    exit;
}

// Aturan SOP: jika selisih 3 s.d. 6 hari, hanya Story yang aktif
$isStoryOnly = ($diffDays >= 3 && $diffDays <= 6);

$errors = [];
$idPengaju = $_SESSION['user']['idPengaju'] ?? 'PGJ-001';

// Ambil daftar kementerian dari database
$daftarKementerian = [];
if ($koneksi) {
    $resKem = mysqli_query($koneksi, "SELECT * FROM KEMENTERIAN ORDER BY kementerianId ASC");
    if ($resKem) {
        while ($row = mysqli_fetch_assoc($resKem)) {
            $daftarKementerian[] = $row;
        }
    }
}

// Proses submit form pengajuan baru (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judulRequest = trim($_POST['judulRequest'] ?? '');
    $kementerianId = trim($_POST['kementerianId'] ?? '');
    $jenisKonten = trim($_POST['jenis_konten'] ?? ($_POST['jenisPublikasi'] ?? ($isStoryOnly ? 'Story' : 'Feeds')));
    $detailKonten = trim($_POST['detailKonten'] ?? '');

    if ($isStoryOnly) {
        $jenisKonten = 'Story';
    }

    if (empty($judulRequest)) {
        $errors[] = 'Judul publikasi wajib diisi.';
    }
    if (empty($kementerianId)) {
        $errors[] = 'Kementerian/Biro pengaju wajib dipilih.';
    }
    if (empty($detailKonten)) {
        $errors[] = 'Detail konten & redaksional (caption) wajib diisi.';
    }

    // Syarat 4.a: Tolak jika tanggal_publikasi adalah hari Minggu
    if ($dayOfWeek === 0) {
        $errors[] = 'Hari Minggu merupakan hari libur operasional CMI. Pengajuan tidak dapat dijadwalkan pada hari Minggu.';
    }

    // Syarat 4.b: Tolak jika jenis_konten adalah Feeds atau Reels tetapi selisih hari < 7 hari, atau jika Story tetapi selisih hari < 3 hari
    if (in_array($jenisKonten, ['Feeds', 'Reels', 'Instagram Feeds', 'Reels / Video'], true)) {
        if ($diffDays < 7) {
            $errors[] = "Jenis konten {$jenisKonten} membutuhkan waktu pengerjaan minimal H-7 sebelum publikasi. Untuk tanggal {$tanggalPublikasi} (H-{$diffDays}), hanya jenis konten Story yang dapat diajukan.";
        }
    } elseif ($jenisKonten === 'Story' || $jenisKonten === 'Instagram Story') {
        if ($diffDays < 3) {
            $errors[] = 'Jenis konten Story wajib diajukan minimal H-3 sebelum tanggal publikasi.';
        }
    }

    // Syarat 4.c: Tolak jika kombinasi tanggal_publikasi dan jam_publikasi sudah terisi di tabel pengajuan dengan status selain 'Ditolak'
    if ($koneksi && empty($errors)) {
        $cekSql = "SELECT COUNT(*) AS total FROM PENGAJUAN 
                   WHERE (tanggal_publikasi = ? OR targetTanggal = ?) 
                     AND (jam_publikasi = ? OR targetJam = ?) 
                     AND (status_pengajuan != 'Ditolak' AND statusPengajuan != 'Ditolak')";
        $stmtCek = mysqli_prepare($koneksi, $cekSql);
        if ($stmtCek) {
            mysqli_stmt_bind_param($stmtCek, "ssss", $tanggalPublikasi, $tanggalPublikasi, $jamPublikasi, $jamPublikasi);
            mysqli_stmt_execute($stmtCek);
            $resCek = mysqli_stmt_get_result($stmtCek);
            $rowCek = mysqli_fetch_assoc($resCek);
            mysqli_stmt_close($stmtCek);

            if (($rowCek['total'] ?? 0) > 0) {
                $errors[] = "Slot jadwal publikasi pada tanggal {$tanggalPublikasi} pukul {$jamPublikasi} WIB telah dipesan oleh pengajuan lain. Silakan pilih slot lain pada kalender.";
            }
        }
    }

    // Penanganan upload berkas acuan/referensi jika disertakan
    $berkasData = null;
    if (isset($_FILES['berkasAcuan']) && $_FILES['berkasAcuan']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['berkasAcuan'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $maxBytes = 5 * 1024 * 1024; // 5 MB

            if (!in_array($ext, $allowed, true)) {
                $errors[] = 'Format berkas acuan tidak valid (hanya JPG, PNG, atau PDF).';
            } elseif ($file['size'] > $maxBytes) {
                $errors[] = 'Ukuran berkas acuan melebihi batas 5 MB.';
            } else {
                $uploadDir = __DIR__ . '/../../assets/uploads/referensi/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $safeName = 'REF_' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
                $destPath = $uploadDir . $safeName;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $berkasData = [
                        'idBerkas' => 'BRK-' . date('Ymd') . '-' . substr(uniqid(), -4),
                        'namaBerkas' => $file['name'],
                        'formatBerkas' => $ext,
                        'ukuranBerkas' => round($file['size'] / (1024 * 1024), 2),
                        'kategoriBerkas' => 'Referensi',
                        'pathPenyimpanan' => 'assets/uploads/referensi/' . $safeName
                    ];
                } else {
                    $errors[] = 'Gagal menyimpan berkas yang diunggah ke server.';
                }
            }
        } else {
            $errors[] = 'Terjadi kesalahan saat mengunggah berkas acuan.';
        }
    }

    // Jika validasi sukses, simpan ke database
    if (empty($errors)) {
        $idPengajuan = 'REQ-' . date('Ymd') . '-' . rand(100, 999);
        $tanggalPengajuan = date('Y-m-d');
        $statusAwal = 'Menunggu Peninjauan';

        if ($koneksi) {
            $stmt = mysqli_prepare($koneksi, "
                INSERT INTO PENGAJUAN (
                    idPengajuan, idPengaju, kementerianId, judulRequest,
                    tanggalPengajuan, tanggal_publikasi, jam_publikasi, jenis_konten,
                    targetTanggal, targetJam, jenisMedia, detailKonten,
                    statusPengajuan, status_pengajuan, sisaWaktuVerifikasi
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 3600)
            ");
            if ($stmt) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssssssssssss",
                    $idPengajuan,
                    $idPengaju,
                    $kementerianId,
                    $judulRequest,
                    $tanggalPengajuan,
                    $tanggalPublikasi,
                    $jamPublikasi,
                    $jenisKonten,
                    $tanggalPublikasi,
                    $jamPublikasi,
                    $jenisKonten,
                    $detailKonten,
                    $statusAwal,
                    $statusAwal
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Simpan metadata berkas jika ada
                if ($berkasData) {
                    $stmtB = mysqli_prepare($koneksi, "
                        INSERT INTO BERKAS_LAMPIRAN (
                            idBerkas, idPengajuan, namaBerkas, formatBerkas,
                            ukuranBerkas, kategoriBerkas, pathPenyimpanan
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    if ($stmtB) {
                        mysqli_stmt_bind_param(
                            $stmtB,
                            "ssssdss",
                            $berkasData['idBerkas'],
                            $idPengajuan,
                            $berkasData['namaBerkas'],
                            $berkasData['formatBerkas'],
                            $berkasData['ukuranBerkas'],
                            $berkasData['kategoriBerkas'],
                            $berkasData['pathPenyimpanan']
                        );
                        mysqli_stmt_execute($stmtB);
                        mysqli_stmt_close($stmtB);
                    }
                }

                // Redirect server-side ke halaman detail pengajuan
                header("Location: detail-pengajuan.php?id=" . urlencode($idPengajuan) . "&status=created");
                exit;
            } else {
                $errors[] = 'Gagal menyimpan pengajuan ke sistem. Silakan periksa kembali data Anda.';
            }
        } else {
            // Mode fallback lokal tanpa database aktif
            header("Location: detail-pengajuan.php?id=" . urlencode($idPengajuan) . "&status=created");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Request Publikasi Internal — CMI BEM FILKOM UB</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link rel="stylesheet" href="../../assets/css/modal.css">
    <link rel="stylesheet" href="../../assets/css/portal.css">
</head>

<body>
    <nav>
        <div class="brand">
            <img src="../../assets/img/logo cmi.jpeg" alt="Logo CMI" class="logo">
            CMI BEM FILKOM UB
        </div>
        <div class="menu">
            <a href="status-pengajuan.php">Status Pengajuan</a>
            <a class="active" href="kalender-jadwal.php">Ajukan Request</a>
        </div>
        <div class="right">
            <span class="pill">Pengaju: <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Pengaju Internal') ?></span>
            <a href="../../index.php" style="color: inherit; text-decoration: none;">Keluar</a>
        </div>
    </nav>

    <div class="wrap">
        <div class="crumb">
            <a href="status-pengajuan.php">Status Pengajuan</a> &rsaquo;
            <a href="kalender-jadwal.php">Kalender Jadwal</a> &rsaquo;
            Form Request Publikasi Internal
        </div>

        <!-- === AWAL BLOK KODE: FORM REQUEST INTERNAL === -->
        <div class="card">
            <h1>Form Request Publikasi Internal</h1>
            <p class="sub">Lengkapi detail materi publikasi kementerian/biro untuk ditinjau dan diproses oleh tim CMI.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="display:block;">
                    <strong>Perhatian:</strong>
                    <ul style="margin:6px 0 0; padding-left:20px;">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Banner Error Validasi Form Kosong (UI-04-02) -->
            <div id="alertErrorKosong" class="alert alert-error" role="alert">
                Lengkapi kolom yang ditandai.
            </div>

            <!-- Banner Error Validasi Berkas Lampiran (UI-04-03) -->
            <div id="alertErrorBerkas" class="alert alert-error" role="alert">
                Ukuran berkas melebihi batas 5 MB atau format tidak valid (JPG/PNG/PDF).
            </div>

            <form id="formRequestInternal" method="POST" action="form-request.php?tanggal=<?= urlencode($tanggalPublikasi) ?>&jam=<?= urlencode($jamPublikasi) ?>" enctype="multipart/form-data"
                novalidate>
                <div class="form-group">
                    <label class="field-label" for="judulRequest">
                        Judul Publikasi <span class="req">*</span>
                    </label>
                    <input type="text" id="judulRequest" name="judulRequest"
                        placeholder="Contoh: Open Recruitment Staff Ahli BEM FILKOM UB 2026"
                        value="<?= htmlspecialchars($_POST['judulRequest'] ?? '') ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group" style="flex: 2;">
                        <label class="field-label" for="kementerianId">
                            Kementerian / Biro Pengaju <span class="req">*</span>
                        </label>
                        <select id="kementerianId" name="kementerianId" required>
                            <option value="" disabled <?= empty($_POST['kementerianId']) ? 'selected' : '' ?> hidden>
                                Pilih Kementerian Anda</option>
                            <?php if (!empty($daftarKementerian)): ?>
                                <?php foreach ($daftarKementerian as $kem): ?>
                                    <option value="<?= htmlspecialchars($kem['kementerianId']) ?>" <?= (($_POST['kementerianId'] ?? '') === $kem['kementerianId']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kem['asalKementerian']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="KEM-01">Kementerian Human Capital</option>
                                <option value="KEM-02">Kementerian Social Equity Environtment</option>
                                <option value="KEM-03">Kementerian Inter Agency AFF</option>
                                <option value="KEM-04">Kementerian IT Solution</option>
                                <option value="KEM-05">Kementerian Administration and Finance</option>
                                <option value="KEM-06">Kementerian Talent Growth</option>
                                <option value="KEM-07">Kementerian Creative Enterprise</option>
                                <option value="KEM-08">Kementerian Student and Strategic Action</option>
                                <option value="KEM-09">Kementerian Student Advokasi and Walfare</option>
                                <option value="KEM-10">Kementerian Creative Media Information</option>
                            <?php endif; ?>
                        </select>
                    </div>

                    <!-- Syarat 3: Field tanggal_publikasi (Readonly) -->
                    <div class="form-group" style="flex: 1.2;">
                        <label class="field-label" for="tanggal_publikasi">
                            Tanggal Publikasi <span class="req">*</span>
                        </label>
                        <input type="date" id="tanggal_publikasi" name="tanggal_publikasi"
                            value="<?= htmlspecialchars($tanggalPublikasi) ?>" readonly
                            style="background: #f8fafc; cursor: not-allowed; border-color: #cbd5e1;" required>
                        <input type="hidden" name="tanggalPublikasi" id="tanggalPublikasi" value="<?= htmlspecialchars($tanggalPublikasi) ?>">
                    </div>

                    <!-- Syarat 3: Field jam_publikasi (Readonly) -->
                    <div class="form-group" style="flex: 1;">
                        <label class="field-label" for="jam_publikasi">
                            Jam Publikasi <span class="req">*</span>
                        </label>
                        <input type="text" id="jam_publikasi" name="jam_publikasi"
                            value="<?= htmlspecialchars($jamPublikasi) ?>" readonly
                            style="background: #f8fafc; cursor: not-allowed; border-color: #cbd5e1;" required>
                        <input type="hidden" name="jamPublikasi" id="jamPublikasi" value="<?= htmlspecialchars($jamPublikasi) ?>">
                    </div>
                </div>

                <div style="margin-top: -10px; margin-bottom: 20px; font-size: 13px; color: #1e3a8a; background: #eff6ff; padding: 10px 14px; border-radius: 8px; border: 1px solid #bfdbfe;">
                    🗓️ <em>Jadwal terpilih:</em> <strong><?= htmlspecialchars($tanggalPublikasi) ?></strong> pukul <strong><?= htmlspecialchars($jamPublikasi) ?> WIB</strong>.
                    <a href="kalender-jadwal.php" style="color: #1d4ed8; font-weight: 700; margin-left: 8px; text-decoration: underline;">Ubah Jadwal di Kalender &rarr;</a>
                </div>

                <!-- Syarat 3: Pilihan Jenis Konten (Feeds, Reels, Story) dengan aturan H-3 s.d. H-6 dan >= H-7 -->
                <div class="form-group">
                    <label class="field-label">
                        Jenis Konten Publikasi <span class="req">*</span>
                    </label>
                    <div class="radio-group">
                        <?php 
                        $selectedJenis = $_POST['jenis_konten'] ?? ($_POST['jenisPublikasi'] ?? ($isStoryOnly ? 'Story' : 'Feeds'));
                        if ($isStoryOnly) {
                            $selectedJenis = 'Story';
                        }
                        ?>
                        <!-- Feeds: disabled jika 3 s.d. 6 hari -->
                        <label class="radio-chip" style="<?= $isStoryOnly ? 'opacity: 0.55; cursor: not-allowed; background: #f8fafc;' : '' ?>">
                            <input type="radio" name="jenis_konten" id="radioFeeds" value="Feeds"
                                <?= ($selectedJenis === 'Feeds' || $selectedJenis === 'Instagram Feeds') ? 'checked' : '' ?>
                                <?= $isStoryOnly ? 'disabled' : '' ?>>
                            Feeds <?= $isStoryOnly ? '<small style="color:#ef4444; font-weight:700;">(Min. H-7)</small>' : '' ?>
                        </label>

                        <!-- Reels: disabled jika 3 s.d. 6 hari -->
                        <label class="radio-chip" style="<?= $isStoryOnly ? 'opacity: 0.55; cursor: not-allowed; background: #f8fafc;' : '' ?>">
                            <input type="radio" name="jenis_konten" id="radioReels" value="Reels"
                                <?= ($selectedJenis === 'Reels' || $selectedJenis === 'Reels / Video') ? 'checked' : '' ?>
                                <?= $isStoryOnly ? 'disabled' : '' ?>>
                            Reels <?= $isStoryOnly ? '<small style="color:#ef4444; font-weight:700;">(Min. H-7)</small>' : '' ?>
                        </label>

                        <!-- Story: selalu aktif jika >= H-3 -->
                        <label class="radio-chip">
                            <input type="radio" name="jenis_konten" id="radioStory" value="Story"
                                <?= ($selectedJenis === 'Story' || $selectedJenis === 'Instagram Story') ? 'checked' : '' ?>>
                            Story <?= $isStoryOnly ? '<small style="color:#16a34a; font-weight:700;">(Tersedia Min. H-3)</small>' : '' ?>
                        </label>
                    </div>

                    <?php if ($isStoryOnly): ?>
                        <input type="hidden" name="jenis_konten" value="Story">
                        <small style="display:block; margin-top:8px; color:#d97706; font-weight:600; font-size:12.5px;">
                            ℹ️ Tanggal ini berjarak <strong><?= $diffDays ?> hari</strong> dari hari ini (H-3 s.d. H-6). Sesuai ketentuan SOP CMI, slot ini khusus untuk jenis konten <strong>Story</strong>. Opsi Feeds dan Reels membutuhkan waktu pengerjaan minimal H-7.
                        </small>
                    <?php else: ?>
                        <small style="display:block; margin-top:8px; color:#16a34a; font-weight:600; font-size:12.5px;">
                            ✓ Tanggal ini berjarak <strong><?= $diffDays ?> hari</strong> (&ge; H-7). Seluruh jenis konten (Feeds, Reels, Story) dapat dipilih.
                        </small>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="field-label" for="detailKonten">
                        Detail Konten &amp; Redaksional (Caption) <span class="req">*</span>
                    </label>
                    <textarea id="detailKonten" name="detailKonten" rows="5"
                        placeholder="Tuliskan materi konten lengkap, tanggal kegiatan, sasaran kegiatan, dan naskah caption publikasi..."
                        required><?= htmlspecialchars($_POST['detailKonten'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="field-label">Berkas Acuan / Referensi Desain (Opsional)</label>
                    <div class="dropzone" id="dropzoneAcuan"
                        onclick="document.getElementById('berkasAcuan').click();">
                        <div class="dropzone-icon">📁</div>
                        <div class="dropzone-text">
                            <strong>Klik atau seret berkas acuan ke area ini</strong><br>
                            Mendukung berkas JPG, PNG, atau PDF (maks. 5 MB)
                        </div>
                    </div>
                    <input type="file" id="berkasAcuan" name="berkasAcuan" accept=".jpg,.jpeg,.png,.pdf"
                        style="display:none;">
                    <div class="file-selected-name" id="namaBerkasTerpilih"></div>
                </div>

                <div class="form-actions">
                    <a href="kalender-jadwal.php" class="btn" style="text-decoration:none;">&larr; Kembali ke Kalender</a>
                    <button type="button" id="btnNextPreview" class="btn primary">Next &rarr; Pratinjau</button>
                </div>

                <!-- [GYS - IM-003] DialogKonfirmasiPengajuan: Modal Pratinjau 180 Detik -->
                <div class="modal-overlay" id="modalPreviewRequest" hidden>
                    <div class="modal-dialog">
                        <div class="modal-header">
                            <h2>Pratinjau Request Publikasi Internal</h2>
                            <span id="countdownTimer" class="badge-timer">Waktu verifikasi: 180s</span>
                        </div>
                        <p class="modal-desc">
                            Periksa kembali seluruh detail pengajuan publikasi Anda sebelum dikirimkan ke tim CMI.
                        </p>

                        <table class="preview-table">
                            <tbody>
                                <tr>
                                    <th>Judul Publikasi</th>
                                    <td id="prevJudul">-</td>
                                </tr>
                                <tr>
                                    <th>Kementerian / Biro</th>
                                    <td id="prevKementerian">-</td>
                                </tr>
                                <tr>
                                    <th>Jenis Konten</th>
                                    <td id="prevJenis">-</td>
                                </tr>
                                <tr>
                                    <th>Jadwal Publikasi</th>
                                    <td id="prevTanggal">-</td>
                                </tr>
                                <tr>
                                    <th>Detail Konten</th>
                                    <td id="prevRedaksional">-</td>
                                </tr>
                                <tr>
                                    <th>Berkas Acuan</th>
                                    <td id="prevBerkas">Tidak ada lampiran</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="modal-actions">
                            <button type="button" id="btnBackToForm" class="btn">Back</button>
                            <button type="submit" id="btnSubmitRequest" class="btn primary">Submit Request</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- === AKHIR BLOK KODE: FORM REQUEST INTERNAL === -->
    </div>

    <script src="../../assets/js/countdown.js"></script>
    <script src="../../assets/js/pengaju.js"></script>
</body>

</html>
