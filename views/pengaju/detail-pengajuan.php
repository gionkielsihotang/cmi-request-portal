<?php
require_once __DIR__ . '/../../config/koneksi.php';

$idPengajuan = $_GET['id'] ?? 'REQ-001';
$statusCreated = (($_GET['status'] ?? '') === 'created');

$pengajuan = null;
$daftarBerkas = [];
$daftarRevisi = [];

if ($koneksi) {
    // 1. Ambil detail permohonan pengajuan
    $stmt = mysqli_prepare($koneksi, "
        SELECT p.*, k.asalKementerian 
        FROM PENGAJUAN p 
        LEFT JOIN KEMENTERIAN k ON p.kementerianId = k.kementerianId 
        WHERE p.idPengajuan = ? 
        LIMIT 1
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $idPengajuan);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $pengajuan = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }

    // 2. Ambil berkas lampiran
    $stmtB = mysqli_prepare($koneksi, "SELECT * FROM BERKAS_LAMPIRAN WHERE idPengajuan = ?");
    if ($stmtB) {
        mysqli_stmt_bind_param($stmtB, "s", $idPengajuan);
        mysqli_stmt_execute($stmtB);
        $resB = mysqli_stmt_get_result($stmtB);
        while ($b = mysqli_fetch_assoc($resB)) {
            $daftarBerkas[] = $b;
        }
        mysqli_stmt_close($stmtB);
    }

    // 3. Ambil riwayat catatan revisi jika ada
    $stmtR = mysqli_prepare($koneksi, "SELECT * FROM CATATAN_REVISI WHERE idPengajuan = ? ORDER BY waktuRevisi DESC");
    if ($stmtR) {
        mysqli_stmt_bind_param($stmtR, "s", $idPengajuan);
        mysqli_stmt_execute($stmtR);
        $resR = mysqli_stmt_get_result($stmtR);
        while ($r = mysqli_fetch_assoc($resR)) {
            $daftarRevisi[] = $r;
        }
        mysqli_stmt_close($stmtR);
    }
}

// Fallback jika database belum berisi data yang diminta
if (!$pengajuan) {
    $pengajuan = [
        'idPengajuan' => $idPengajuan,
        'judulRequest' => 'Open Recruitment Staff Ahli BEM FILKOM UB 2026',
        'statusPengajuan' => 'Menunggu Peninjauan',
        'tanggalPengajuan' => '2026-11-20',
        'targetTanggal' => '2026-11-25',
        'jenisMedia' => 'Instagram Feeds',
        'asalKementerian' => 'Kementerian Human Capital',
        'detailKonten' => "Open Recruitment Staff Ahli BEM FILKOM UB 2026 telah resmi dibuka. Pendaftaran berlangsung pada 25–30 November 2026 melalui tautan resmi BEM FILKOM UB.",
        'catatanKelengkapan' => '-',
        'alasanPenolakan' => '-'
    ];
}

$status = $pengajuan['statusPengajuan'];
$badgeClass = 's-waiting';
if (stripos($status, 'verifikasi') !== false) {
    $badgeClass = 'badge verifikasi';
} elseif ($status === 'Ditinjau') {
    $badgeClass = 's-ditinjau';
} elseif ($status === 'Diproses') {
    $badgeClass = 's-diproses';
} elseif ($status === 'Fix' || $status === 'Selesai') {
    $badgeClass = 's-diproses';
} elseif ($status === 'Ditolak') {
    $badgeClass = 's-ditolak';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengajuan Publikasi — CMI BEM FILKOM UB</title>
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
            Detail Pengajuan
        </div>

        <!-- === AWAL BLOK KODE: DETAIL PENGAJUAN (UI-02-04 & UI-03-02) === -->
        <div class="card">
            <?php if ($statusCreated): ?>
                <!-- Banner Notifikasi Berhasil Diajukan (muncul otomatis jika URL ?status=created) -->
                <div id="bannerRequestBerhasil" class="alert alert-success" role="status" style="display:block;">
                    Pengajuan publikasi berhasil dikirim! Permohonan Anda telah masuk ke antrean tim CMI dengan status
                    Menunggu Peninjauan.
                </div>
            <?php endif; ?>

            <?php if (($_GET['status'] ?? '') === 'revised'): ?>
                <div class="alert alert-success" role="status" style="display:block;">
                    Catatan revisi berhasil dikirim ke tim CMI. Status pengajuan diperbarui menjadi Dalam Revisi Desain.
                </div>
            <?php endif; ?>

            <div class="head">
                <div>
                    <h1 id="detailJudul"><?= htmlspecialchars($pengajuan['judulRequest']) ?></h1>
                </div>
                <span id="detailStatus" class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
            </div>
            <p class="sub">Rincian informasi permohonan publikasi internal dan riwayat peninjauan CMI.</p>

            <div class="meta-grid">
                <div class="meta-item">
                    <div class="lbl">Nomor Pengajuan</div>
                    <div class="val" id="detailIdPengajuan"><?= htmlspecialchars($pengajuan['idPengajuan']) ?></div>
                </div>
                <div class="meta-item">
                    <div class="lbl">Jadwal Publikasi Target</div>
                    <div class="val" id="detailTanggal">
                        <?= htmlspecialchars($pengajuan['targetTanggal'] ?? $pengajuan['tanggalPengajuan']) ?>
                        <?= !empty($pengajuan['targetJam']) ? ' • Pukul ' . htmlspecialchars($pengajuan['targetJam']) . ' WIB' : '' ?>
                    </div>
                </div>
                <div class="meta-item">
                    <div class="lbl">Jenis Media</div>
                    <div class="val" id="detailJenis">
                        <?= htmlspecialchars($pengajuan['jenisMedia'] ?? 'Instagram Feeds') ?></div>
                </div>
            </div>

            <div class="section-block">
                <div class="lbl">Kementerian / Biro Pengaju</div>
                <div class="content-box" id="detailKementerian">
                    <?= htmlspecialchars($pengajuan['asalKementerian'] ?? 'Kementerian BEM FILKOM') ?></div>
            </div>

            <div class="section-block">
                <div class="lbl">Detail Konten &amp; Redaksional (Caption)</div>
                <div class="content-box" id="detailKonten"><?= nl2br(htmlspecialchars($pengajuan['detailKonten'])) ?>
                </div>
            </div>

            <div class="section-block">
                <div class="lbl">Berkas Acuan / Referensi Desain</div>
                <div class="content-box" id="detailBerkas">
                    <?php if (!empty($daftarBerkas)): ?>
                        <?php foreach ($daftarBerkas as $b): ?>
                            <div>
                                📎 <a href="../../<?= htmlspecialchars($b['pathPenyimpanan']) ?>" target="_blank"
                                    style="color:#2b57a7; font-weight:700;">
                                    <?= htmlspecialchars($b['namaBerkas']) ?>
                                </a> (<?= htmlspecialchars($b['ukuranBerkas']) ?> MB -
                                <?= htmlspecialchars($b['kategoriBerkas']) ?>)
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        Tidak ada berkas lampiran yang diunggah.
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($daftarRevisi)): ?>
                <div class="section-block">
                    <div class="lbl">Riwayat Catatan Revisi</div>
                    <div class="content-box">
                        <?php foreach ($daftarRevisi as $rev): ?>
                            <div style="margin-bottom:8px; padding-bottom:8px; border-bottom:1px dashed #cbd5e1;">
                                <small style="color:#64748b;">[<?= htmlspecialchars($rev['waktuRevisi']) ?>]</small><br>
                                <?= nl2br(htmlspecialchars($rev['teksRevisi'])) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="feedback-grid">
                <div class="feedback-card">
                    <div class="lbl">Catatan Kelengkapan Materi (dari CMI)</div>
                    <div class="val" id="detailCatatanKelengkapan">
                        <?= nl2br(htmlspecialchars($pengajuan['catatanKelengkapan'] ?? '-')) ?: '-' ?></div>
                </div>
                <div class="feedback-card">
                    <div class="lbl">Alasan Penolakan (Jika Ditolak)</div>
                    <div class="val" id="detailAlasanPenolakan"
                        style="color:<?= !empty($pengajuan['alasanPenolakan']) ? '#8a1f1f' : 'inherit' ?>;">
                        <?= nl2br(htmlspecialchars($pengajuan['alasanPenolakan'] ?? '-')) ?: '-' ?>
                    </div>
                </div>
            </div>

            <div class="actions">
                <a href="status-pengajuan.php" id="btnLihatStatus" class="btn">&larr; Kembali ke Status Pengajuan</a>
                <?php if (stripos($status, 'verifikasi') !== false): ?>
                    <a href="verifikasi-desain.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>" id="btnKeVerifikasi"
                        class="btn primary">
                        Verifikasi Hasil Desain &rarr;
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <!-- === AKHIR BLOK KODE: DETAIL PENGAJUAN === -->
    </div>

    <footer class="foot">&copy; 2026 Biro Center of Media and Information (CMI) &bull; BEM FILKOM Universitas Brawijaya.
    </footer>
    <script src="../../assets/js/countdown.js"></script>
    <script src="../../assets/js/pengaju.js"></script>
</body>

</html>