<?php
require_once __DIR__ . '/../../config/koneksi.php';

$idPengajuan = $_GET['id'] ?? 'REQ-002';
$notifMsg = '';

// Proses POST persetujuan desain
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'setuju') {
    if ($koneksi) {
        $stmt = mysqli_prepare($koneksi, "UPDATE PENGAJUAN SET statusPengajuan = 'Fix', sisaWaktuVerifikasi = 0 WHERE idPengajuan = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $idPengajuan);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    header("Location: verifikasi-desain.php?id=" . urlencode($idPengajuan) . "&status=approved");
    exit;
}

if (($_GET['status'] ?? '') === 'approved') {
    $notifMsg = 'Desain berhasil disetujui! Status permohonan sekarang telah berubah menjadi Fix.';
}

$pengajuan = null;
$berkasDesain = null;

if ($koneksi) {
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM PENGAJUAN WHERE idPengajuan = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $idPengajuan);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $pengajuan = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    $stmtB = mysqli_prepare($koneksi, "SELECT * FROM BERKAS_LAMPIRAN WHERE idPengajuan = ? AND (kategoriBerkas = 'HasilDesain' OR kategoriBerkas = 'Desain') ORDER BY idBerkas DESC LIMIT 1");
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
        'statusPengajuan' => 'Menunggu Verifikasi',
        'sisaWaktuVerifikasi' => 3550
    ];
}

$status = $pengajuan['statusPengajuan'];
$isFix = ($status === 'Fix' || $status === 'Selesai');
$sisaWaktu = (int) ($pengajuan['sisaWaktuVerifikasi'] ?? 3600);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Hasil Desain — CMI BEM FILKOM UB</title>
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
            <a href="kalender-jadwal.php">Ajukan Request</a>
        </div>
        <div class="right">
            <span class="pill">Pengaju: <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Pengaju Internal') ?></span>
            <a href="../../index.php" style="color: inherit; text-decoration: none;">Keluar</a>
        </div>
    </nav>

    <div class="wrap">
        <div class="crumb">
            <a href="status-pengajuan.php">Status Pengajuan</a> &rsaquo;
            <a href="detail-pengajuan.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>">Detail Pengajuan</a> &rsaquo;
            Verifikasi Desain
        </div>

        <!-- === AWAL BLOK KODE: ADINDA (UI-05-01 s/d UI-05-04 | REV - IM-004, IM-005, IM-006, IM-007) === -->
        <?php if (!empty($notifMsg)): ?>
            <div id="bannerVerifikasi" class="alert alert-success" style="display:block;">
                <?= htmlspecialchars($notifMsg) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="head">
                <h1>Verifikasi Hasil Desain</h1>
                <?php if (!$isFix): ?>
                    <span id="timerBadge" class="badge s-waiting" data-sisa-detik="<?= $sisaWaktu ?>">
                        Sisa waktu: <?= gmdate("H:i:s", $sisaWaktu) ?>
                    </span>
                <?php else: ?>
                    <span class="badge s-diproses">Status: FIX (Disetujui)</span>
                <?php endif; ?>
            </div>
            <p class="sub">Periksa kesesuaian pratinjau desain dari CMI sebelum menyetujui.</p>

            <div class="info">
                <div>
                    <div class="lbl">No. pengajuan</div>
                    <div class="val" id="noPengajuan"><?= htmlspecialchars($pengajuan['idPengajuan']) ?></div>
                </div>
                <div>
                    <div class="lbl">Jenis publikasi</div>
                    <div class="val" id="jenisPublikasi">
                        <?= htmlspecialchars($pengajuan['jenisMedia'] ?? 'Instagram Feeds') ?></div>
                </div>
                <div>
                    <div class="lbl">Label status desain</div>
                    <span id="labelStatusDesain" class="badge <?= $isFix ? 's-diproses' : 's-waiting' ?>"
                        style="margin-top: 6px;">
                        <?= htmlspecialchars($status) ?>
                    </span>
                </div>
            </div>

            <div class="lbl">Pratinjau desain dari CMI</div>
            <div class="preview" id="previewDesain"
                style="overflow:hidden; display:flex; align-items:center; justify-content:center;">
                <?php if ($berkasDesain && file_exists(__DIR__ . '/../../' . $berkasDesain['pathPenyimpanan'])): ?>
                    <img src="../../<?= htmlspecialchars($berkasDesain['pathPenyimpanan']) ?>" alt="Hasil Desain CMI"
                        style="max-width:100%; max-height:100%; object-fit:contain;">
                <?php else: ?>
                    <svg preserveAspectRatio="none" viewBox="0 0 100 100">
                        <line x1="0" y1="0" x2="100" y2="100" stroke="#c4c9d1" stroke-width="0.4"
                            vector-effect="non-scaling-stroke" />
                        <line x1="100" y1="0" x2="0" y2="100" stroke="#c4c9d1" stroke-width="0.4"
                            vector-effect="non-scaling-stroke" />
                    </svg>
                    <span>🖼️ Pratinjau Desain: <?= htmlspecialchars($pengajuan['judulRequest']) ?></span>
                <?php endif; ?>
            </div>

            <div class="infobar">
                💡 <em>Ketentuan SOP:</em> Pengaju memiliki batas waktu toleransi verifikasi 1 jam. Jika tidak ada
                tanggapan revisi, sistem dapat menyetujui desain secara otomatis (Auto-Approve).
            </div>

            <div class="actions">
                <a href="detail-pengajuan.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>" class="btn">&larr; Detail
                    Pengajuan</a>
                <div class="grp">
                    <?php if (!$isFix): ?>
                        <a href="form-revisi.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>" class="btn" id="btnRevisi">
                            Ajukan Revisi
                        </a>
                        <button class="btn primary" id="btnSetuju" type="button">Setujui Desain</button>
                    <?php else: ?>
                        <button class="btn primary" disabled>Desain Sudah Disetujui (Fix)</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Setujui Desain -->
        <div class="modal-overlay" id="modalSetuju" hidden>
            <div class="modal-dialog">
                <h2>Setujui desain ini?</h2>
                <p>
                    Setelah disetujui, label status permohonan akan diperbarui menjadi <strong>"Fix"</strong> dan berkas
                    siap dipublikasikan oleh tim CMI.
                </p>
                <form method="POST" action="verifikasi-desain.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>">
                    <input type="hidden" name="action" value="setuju">
                    <div class="actions">
                        <button class="btn" id="btnBatalSetuju" type="button">Batal</button>
                        <button class="btn primary" id="btnYaSetuju" type="submit">Ya, Setujui</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- === AKHIR BLOK KODE: ADINDA === -->
    </div>

    <footer class="foot">&copy; 2026 Biro Center of Media and Information (CMI) &bull; BEM FILKOM Universitas Brawijaya.
    </footer>
    <script src="../../assets/js/countdown.js"></script>
    <script src="../../assets/js/pengaju.js"></script>
</body>

</html>