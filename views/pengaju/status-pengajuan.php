<?php
require_once __DIR__ . '/../../config/koneksi.php';

$idPengaju = $_SESSION['user']['idPengaju'] ?? 'PGJ-001';
$daftarPengajuan = [];

if ($koneksi) {
    $stmt = mysqli_prepare($koneksi, "
        SELECT p.*, k.asalKementerian 
        FROM PENGAJUAN p 
        LEFT JOIN KEMENTERIAN k ON p.kementerianId = k.kementerianId 
        WHERE p.idPengaju = ? 
        ORDER BY p.tanggalPengajuan DESC, p.idPengajuan DESC
    ");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $idPengaju);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $daftarPengajuan[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pengajuan — CMI BEM FILKOM UB</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link rel="stylesheet" href="../../assets/css/modal.css">
    <link rel="stylesheet" href="../../assets/css/portal.css">
</head>

<body data-page="status">
    <nav>
        <div class="brand">
            <img src="../../assets/img/logo cmi.jpeg" alt="Logo CMI" class="logo">
            CMI BEM FILKOM UB
        </div>
        <div class="menu">
            <a class="active" href="status-pengajuan.php">Status Pengajuan</a>
            <a href="kalender-jadwal.php">Ajukan Request</a>
        </div>
        <div class="right">
            <span class="pill">Pengaju: <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Pengaju Internal') ?></span>
            <a href="../../index.php" style="color: inherit; text-decoration: none;">Keluar</a>
        </div>
    </nav>

    <div class="wrap">
        <div class="crumb"><a href="status-pengajuan.php">Beranda</a> &rsaquo; Status Pengajuan</div>
        <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
            <div>
                <h1 style="margin-bottom:4px;">Status Pengajuan</h1>
                <p class="sub">Daftar riwayat permohonan publikasi beserta label status terbaru dari CMI.</p>
            </div>
            <a href="kalender-jadwal.php" class="btn primary" style="text-decoration:none; display:inline-block; padding:10px 18px;">+ Ajukan Request Baru</a>
        </div>

        <?php if (!empty($daftarPengajuan)): ?>
            <!-- UI-02-01 | [CLS - IM-001] HalamanStatusPengajuan (daftar tersedia) -->
            <div class="card tabel" id="daftarPengajuanCard">
                <table>
                    <thead>
                        <tr>
                            <th>No. Pengajuan</th>
                            <th>Judul Publikasi</th>
                            <th>Jadwal Publikasi</th>
                            <th>Label Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tabelRiwayatBody">
                        <?php foreach ($daftarPengajuan as $row):
                            $status = $row['statusPengajuan'];
                            $badgeClass = 's-waiting';
                            if (stripos($status, 'verifikasi') !== false) {
                                $badgeClass = 'badge verifikasi';
                                $targetUrl = 'verifikasi-desain.php?id=' . urlencode($row['idPengajuan']);
                            } elseif ($status === 'Ditinjau') {
                                $badgeClass = 's-ditinjau';
                                $targetUrl = 'detail-pengajuan.php?id=' . urlencode($row['idPengajuan']);
                            } elseif ($status === 'Diproses') {
                                $badgeClass = 's-diproses';
                                $targetUrl = 'detail-pengajuan.php?id=' . urlencode($row['idPengajuan']);
                            } elseif ($status === 'Fix' || $status === 'Selesai') {
                                $badgeClass = 's-diproses';
                                $targetUrl = 'detail-pengajuan.php?id=' . urlencode($row['idPengajuan']);
                            } elseif ($status === 'Ditolak') {
                                $badgeClass = 's-ditolak';
                                $targetUrl = 'detail-pengajuan.php?id=' . urlencode($row['idPengajuan']);
                            } else {
                                $targetUrl = 'detail-pengajuan.php?id=' . urlencode($row['idPengajuan']);
                            }
                            ?>
                            <tr style="cursor:pointer;" onclick="window.location.href='<?= $targetUrl ?>'">
                                <td><strong><?= htmlspecialchars($row['idPengajuan']) ?></strong></td>
                                <td><?= htmlspecialchars($row['judulRequest']) ?></td>
                                <td>
                                    <?= htmlspecialchars($row['targetTanggal'] ?? $row['tanggalPengajuan']) ?>
                                    <?= !empty($row['targetJam']) ? '<br><small style="color:#64748b; font-weight:600;">Pukul ' . htmlspecialchars($row['targetJam']) . ' WIB</small>' : '' ?>
                                </td>
                                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
                                <td>
                                    <a href="<?= $targetUrl ?>" class="btn-sm" style="text-decoration:none;"
                                        onclick="event.stopPropagation();">
                                        Detail &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <!-- UI-02-02 | [CLS - IM-002] StatePengajuanKosong (daftar kosong) -->
            <div class="card empty" id="emptyState">
                <div class="ikon">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6" />
                    </svg>
                </div>
                <h2>Belum ada pengajuan</h2>
                <p>Kamu belum mengajukan request apa pun. Mulai dengan membuat pengajuan baru.</p>
                <a href="kalender-jadwal.php" class="btn-primary" style="text-decoration:none;">Buat Pengajuan</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="foot">&copy; 2026 Biro Center of Media and Information (CMI) &bull; BEM FILKOM Universitas Brawijaya.
    </footer>
    <script src="../../assets/js/pengaju.js"></script>
</body>

</html>