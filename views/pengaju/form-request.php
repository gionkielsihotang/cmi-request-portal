<?php
require_once __DIR__ . '/../../config/koneksi.php';

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
    $tanggalPublikasi = trim($_POST['tanggalPublikasi'] ?? '');
    $jenisPublikasi = trim($_POST['jenisPublikasi'] ?? 'Instagram Feeds');
    $detailKonten = trim($_POST['detailKonten'] ?? '');

    if (empty($judulRequest)) {
        $errors[] = 'Judul publikasi wajib diisi.';
    }
    if (empty($kementerianId)) {
        $errors[] = 'Kementerian/Biro pengaju wajib dipilih.';
    }
    if (empty($detailKonten)) {
        $errors[] = 'Detail konten & redaksional (caption) wajib diisi.';
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
        $targetTanggal = !empty($tanggalPublikasi) ? $tanggalPublikasi : $tanggalPengajuan;
        $statusAwal = 'Menunggu Peninjauan';

        if ($koneksi) {
            $stmt = mysqli_prepare($koneksi, "
                INSERT INTO PENGAJUAN (
                    idPengajuan, idPengaju, kementerianId, judulRequest,
                    tanggalPengajuan, targetTanggal, jenisMedia, detailKonten,
                    statusPengajuan, sisaWaktuVerifikasi
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 3600)
            ");
            if ($stmt) {
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssssss",
                    $idPengajuan,
                    $idPengaju,
                    $kementerianId,
                    $judulRequest,
                    $tanggalPengajuan,
                    $targetTanggal,
                    $jenisPublikasi,
                    $detailKonten,
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
            <a class="active" href="pilihan-pengajuan.php">Buat Pengajuan</a>
        </div>
        <div class="right">
            <span class="pill">Pengaju: <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Pengaju Internal') ?></span>
            <a href="../../index.php" style="color: inherit; text-decoration: none;">Keluar</a>
        </div>
    </nav>

    <div class="wrap">
        <div class="crumb">
            <a href="status-pengajuan.php">Status Pengajuan</a> &rsaquo;
            <a href="pilihan-pengajuan.php">Pilihan Pengajuan</a> &rsaquo;
            Form Request Publikasi Internal
        </div>

        <!-- === AWAL BLOK KODE: FORM REQUEST INTERNAL (UI-04-01 s/d UI-04-03) === -->
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

            <form id="formRequestInternal" method="POST" action="form-request.php" enctype="multipart/form-data"
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
                    <div class="form-group">
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

                    <div class="form-group">
                        <label class="field-label" for="tanggalPublikasi">
                            Target Tanggal Publikasi <span class="req">*</span>
                        </label>
                        <input type="date" id="tanggalPublikasi" name="tanggalPublikasi"
                            value="<?= htmlspecialchars($_POST['tanggalPublikasi'] ?? date('Y-m-d', strtotime('+3 days'))) ?>"
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="field-label">
                        Jenis Media Publikasi <span class="req">*</span>
                    </label>
                    <div class="radio-group">
                        <?php $selectedJenis = $_POST['jenisPublikasi'] ?? 'Instagram Feeds'; ?>
                        <label class="radio-chip">
                            <input type="radio" name="jenisPublikasi" value="Instagram Feeds"
                                <?= ($selectedJenis === 'Instagram Feeds' || $selectedJenis === 'Feeds') ? 'checked' : '' ?>>
                            Instagram Feeds
                        </label>
                        <label class="radio-chip">
                            <input type="radio" name="jenisPublikasi" value="Instagram Story"
                                <?= ($selectedJenis === 'Instagram Story' || $selectedJenis === 'Story') ? 'checked' : '' ?>>
                            Instagram Story
                        </label>
                        <label class="radio-chip">
                            <input type="radio" name="jenisPublikasi" value="Reels / Video" <?= ($selectedJenis === 'Reels / Video' || $selectedJenis === 'Reels') ? 'checked' : '' ?>>
                            Reels / Video
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="field-label" for="detailKonten">
                        Detail Konten &amp; Redaksional (Caption) <span class="req">*</span>
                    </label>
                    <textarea id="detailKonten" name="detailKonten"
                        placeholder="Tuliskan poin isi desain, tanggal/tempat kegiatan, narahubung, serta draf caption publikasi..."
                        required><?= htmlspecialchars($_POST['detailKonten'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="field-label" for="berkasAcuan">
                        Berkas Acuan / Referensi Desain (Opsional)
                    </label>
                    <div class="dropzone" id="dropzoneAcuan" onclick="document.getElementById('berkasAcuan').click()">
                        <div class="dropzone-title">Klik untuk memilih berkas referensi</div>
                        <div class="dropzone-hint">Format yang didukung: JPG, JPEG, PNG, atau PDF (Maksimal 5 MB)</div>
                        <div class="file-selected-name" id="namaBerkasTerpilih"></div>
                    </div>
                    <input type="file" id="berkasAcuan" name="berkasAcuan" accept=".jpg,.jpeg,.png,.pdf" hidden>
                </div>

                <div class="actions">
                    <a href="pilihan-pengajuan.php" class="btn">Kembali</a>
                    <button type="button" id="btnNextPreview" class="btn primary">Next &rarr;</button>
                </div>

                <!-- === MODAL POP-UP PREVIEW REQUEST (UI-03-01) === -->
                <div id="modalPreviewRequest" class="modal-overlay" role="dialog" aria-modal="true"
                    aria-labelledby="modalPreviewTitle" hidden>
                    <div class="modal-dialog">
                        <div class="modal-header">
                            <h2 id="modalPreviewTitle">Pratinjau Data Pengajuan</h2>
                            <span class="timer-badge">Sisa Waktu: <span id="countdownTimer">03:00</span></span>
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
                                    <th>Jenis Publikasi</th>
                                    <td id="prevJenis">-</td>
                                </tr>
                                <tr>
                                    <th>Target Tanggal</th>
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