<?php
require_once __DIR__ . '/../../config/koneksi.php';

$idPengajuan = $_GET['id'] ?? 'REQ-002';
$error = '';

$pengajuan = null;
$berkasDesain = null;

if ($koneksi) {
    $stmt = mysqli_prepare($koneksi, "
        SELECT p.*, k.asalKementerian 
        FROM PENGAJUAN p 
        LEFT JOIN KEMENTERIAN k ON p.kementerianId = k.kementerianId 
        WHERE p.idPengajuan = ? LIMIT 1
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $idPengajuan);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $pengajuan = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    $stmtB = mysqli_prepare($koneksi, "
        SELECT * FROM BERKAS_LAMPIRAN 
        WHERE idPengajuan = ? AND (kategoriBerkas = 'HasilDesain' OR kategoriBerkas = 'Desain') 
        ORDER BY idBerkas DESC LIMIT 1
    ");
    if ($stmtB) {
        mysqli_stmt_bind_param($stmtB, "s", $idPengajuan);
        mysqli_stmt_execute($stmtB);
        $resB = mysqli_stmt_get_result($stmtB);
        $berkasDesain = mysqli_fetch_assoc($resB);
        mysqli_stmt_close($stmtB);
    }
}

if (!$pengajuan) {
    $pengajuan = [
        'idPengajuan' => $idPengajuan,
        'judulRequest' => 'Publikasi Webinar Nasional FILKOM 2026',
        'jenisMedia' => 'Instagram Story',
        'asalKementerian' => 'Kementerian Human Capital',
        'kementerianId' => 'KEM-01',
        'statusPengajuan' => 'Menunggu Verifikasi',
        'sisaWaktuVerifikasi' => 3550
    ];
}

// Proses submit form revisi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teksRevisi = trim($_POST['catatanRevisi'] ?? $_POST['teksRevisi'] ?? '');
    $setujuPenalti = isset($_POST['setujuPenalti']) && $_POST['setujuPenalti'] == '1';

    if (empty($teksRevisi)) {
        $error = 'Catatan rincian perbaikan desain wajib diisi sebelum mengirim revisi.';
    } else {
        $idRevisi = 'REV-' . date('Ymd') . '-' . substr(uniqid(), -4);
        $waktuSekarang = date('Y-m-d H:i:s');
        $kementerianId = $pengajuan['kementerianId'] ?? 'KEM-01';
        $idPengaju = $pengajuan['idPengaju'] ?? 'PGJ-001';

        if ($koneksi) {
            // 1. Simpan catatan revisi
            $stmtR = mysqli_prepare($koneksi, "INSERT INTO CATATAN_REVISI (idRevisi, idPengajuan, teksRevisi, waktuRevisi) VALUES (?, ?, ?, ?)");
            if ($stmtR) {
                mysqli_stmt_bind_param($stmtR, "ssss", $idRevisi, $idPengajuan, $teksRevisi, $waktuSekarang);
                mysqli_stmt_execute($stmtR);
                mysqli_stmt_close($stmtR);
            }

            // 2. Perbarui status pengajuan menjadi 'Dalam Revisi Desain'
            $stmtU = mysqli_prepare($koneksi, "UPDATE PENGAJUAN SET statusPengajuan = 'Dalam Revisi Desain' WHERE idPengajuan = ?");
            if ($stmtU) {
                mysqli_stmt_bind_param($stmtU, "s", $idPengajuan);
                mysqli_stmt_execute($stmtU);
                mysqli_stmt_close($stmtU);
            }

            // 3. Catat penalti jika disetujui/keterlambatan
            if ($setujuPenalti) {
                $idPenalti = 'PNL-' . date('Ymd') . '-' . substr(uniqid(), -4);
                $stmtP = mysqli_prepare($koneksi, "INSERT INTO PENALTI_KEMENTERIAN (idPenalti, idPengajuan, poinPenalti, waktuPenalti, idPengaju, kementerianId) VALUES (?, ?, 5, ?, ?, ?)");
                if ($stmtP) {
                    mysqli_stmt_bind_param($stmtP, "sssss", $idPenalti, $idPengajuan, $waktuSekarang, $idPengaju, $kementerianId);
                    mysqli_stmt_execute($stmtP);
                    mysqli_stmt_close($stmtP);
                }

                $stmtM = mysqli_prepare($koneksi, "UPDATE PENGAJU_INTERNAL SET totalPoinPenalti = totalPoinPenalti + 5 WHERE idPengaju = ?");
                if ($stmtM) {
                    mysqli_stmt_bind_param($stmtM, "s", $idPengaju);
                    mysqli_stmt_execute($stmtM);
                    mysqli_stmt_close($stmtM);
                }
            }
        }

        // Redirect ke detail pengajuan
        header("Location: detail-pengajuan.php?id=" . urlencode($idPengajuan) . "&status=revised");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pengajuan Revisi Desain — CMI BEM FILKOM UB</title>
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
            <a href="pilihan-pengajuan.php">Buat Pengajuan</a>
        </div>
        <div class="right">
            <span class="pill">Pengaju: <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Pengaju Internal') ?></span>
            <a href="../../index.php" style="color: inherit; text-decoration: none;">Keluar</a>
        </div>
    </nav>

    <div class="wrap">
        <div class="crumb">
            <a href="status-pengajuan.php">Status Pengajuan</a> &rsaquo;
            <a href="verifikasi-desain.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>">Verifikasi Desain</a>
            &rsaquo;
            Form Revisi
        </div>

        <div class="card">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="display:block;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="head">
                <div>
                    <h1>Formulir Pengajuan Revisi Desain</h1>
                    <p class="sub">Tuliskan rincian perbaikan desain sebelum batas waktu verifikasi berakhir.</p>
                </div>
                <span class="badge s-waiting" id="timerBadge">
                    ⏱️ Sisa waktu: <?= gmdate("H:i:s", (int) ($pengajuan['sisaWaktuVerifikasi'] ?? 3600)) ?>
                </span>
            </div>

            <!-- Card Info Pengajuan -->
            <div class="meta-grid">
                <div class="meta-item">
                    <div class="lbl">No. Pengajuan</div>
                    <div class="val"><?= htmlspecialchars($pengajuan['idPengajuan']) ?></div>
                </div>
                <div class="meta-item">
                    <div class="lbl">Jenis Publikasi</div>
                    <div class="val"><?= htmlspecialchars($pengajuan['jenisMedia'] ?? 'Instagram Feeds') ?></div>
                </div>
                <div class="meta-item">
                    <div class="lbl">Kementerian</div>
                    <div class="val"><?= htmlspecialchars($pengajuan['asalKementerian'] ?? 'Kementerian BEM') ?></div>
                </div>
            </div>

            <!-- Pratinjau Desain yang Sedang Dikoreksi -->
            <div class="section-block">
                <div class="lbl">Draf Desain Terakhir dari Tim CMI</div>
                <div class="preview"
                    style="height:260px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                    <?php if ($berkasDesain && file_exists(__DIR__ . '/../../' . $berkasDesain['pathPenyimpanan'])): ?>
                        <img src="../../<?= htmlspecialchars($berkasDesain['pathPenyimpanan']) ?>" alt="Draf Desain"
                            style="max-width:100%; max-height:100%; object-fit:contain;">
                    <?php else: ?>
                        <span>🖼️ Gambar Draf Desain Permohonan <?= htmlspecialchars($pengajuan['idPengajuan']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form Isian Revisi -->
            <form method="POST" action="form-revisi.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>">
                <div class="form-group">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                        <label class="field-label" for="catatanRevisi" style="margin-bottom:0;">
                            Catatan Rincian Perbaikan Desain <span class="req">*wajib</span>
                        </label>
                        <span id="charCounter" style="font-size:13px; color:#6b7280;">0/1000 karakter</span>
                    </div>
                    <textarea id="catatanRevisi" name="catatanRevisi" rows="5" required
                        placeholder="Contoh: Tolong sesuaikan logo sponsor di pojok kanan bawah, perbesar font judul acara, dan naikkan kontras warna teks sub-judul."><?= htmlspecialchars($_POST['catatanRevisi'] ?? '') ?></textarea>

                    <div style="display:flex; gap:8px; margin-top:8px;">
                        <button type="button" class="btn-sm"
                            onclick="document.getElementById('catatanRevisi').value += '+ Revisi Tipografi: ';">+
                            Tipografi</button>
                        <button type="button" class="btn-sm"
                            onclick="document.getElementById('catatanRevisi').value += '+ Penyesuaian Logo: ';">+ Posisi
                            Logo</button>
                        <button type="button" class="btn-sm"
                            onclick="document.getElementById('catatanRevisi').value += '+ Palet Warna: ';">+
                            Warna</button>
                    </div>
                </div>

                <div class="infobar" style="font-size:14px; margin-top:20px;">
                    📌 <strong>Ketentuan Editorial BEM FILKOM UB:</strong> Setiap pengajuan memiliki kuota maksimal 2
                    kali siklus revisi minor. Pastikan seluruh poin koreksi telah dirangkum lengkap dalam satu kali
                    pengiriman formulir ini.
                </div>

                <!-- Konfirmasi penalti jika verifikasi melewati 1 jam -->
                <div
                    style="margin:20px 0; padding:14px; background:#fff8f8; border:1px solid #fecaca; border-radius:8px;">
                    <label
                        style="display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:600; color:#991b1b;">
                        <input type="checkbox" name="setujuPenalti" value="1" style="width:18px; height:18px;">
                        Setujui ketentuan penalti -5 poin kementerian jika revisi dikirim setelah batas toleransi 1 jam
                    </label>
                </div>

                <div class="actions">
                    <a href="verifikasi-desain.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>" class="btn">&larr;
                        Kembali ke Verifikasi</a>
                    <button type="submit" class="btn primary">Kirim Revisi &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="foot">&copy; 2026 Biro Center of Media and Information (CMI) &bull; BEM FILKOM Universitas Brawijaya.
    </footer>
    <script src="../../assets/js/countdown.js"></script>
    <script src="../../assets/js/pengaju.js"></script>
</body>

</html>