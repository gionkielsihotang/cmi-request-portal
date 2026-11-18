<?php
require_once __DIR__ . '/../../config/koneksi.php';

$daftarRequest = [];
if ($koneksi) {
    $sql = "
        SELECT p.*, k.asalKementerian, pi.nama AS namaPengaju 
        FROM PENGAJUAN p 
        LEFT JOIN KEMENTERIAN k ON p.kementerianId = k.kementerianId 
        LEFT JOIN PENGAJU_INTERNAL pi ON p.idPengaju = pi.idPengaju 
        ORDER BY p.tanggalPengajuan DESC, p.idPengajuan DESC
    ";
    $res = mysqli_query($koneksi, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $daftarRequest[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard CMI — BEM FILKOM UB</title>
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
            <a class="active" href="dashboard-cmi.php">Dashboard CMI</a>
            <a href="daftar-permohonan.php">Daftar Permohonan</a>
        </div>
        <div class="right">
            <span class="pill">Staf CMI: <?= htmlspecialchars($_SESSION['admin']['namaAdmin'] ?? 'Admin CMI') ?></span>
            <a href="../../index.php" style="color: inherit; text-decoration: none;">Keluar</a>
        </div>
    </nav>

    <div class="wrap">
        <div class="crumb">Dashboard CMI</div>

        <!-- === AWAL BLOK KODE: ADINDA (UI-11-01 | AM - IM-003 DashboardCMI) === -->
        <div class="card">
            <h1>Daftar Request Publikasi</h1>

            <div class="filters" id="filterStatus">
                <span class="f on" data-filter="Semua">Semua</span>
                <span class="f" data-filter="Waiting">Waiting / Menunggu</span>
                <span class="f" data-filter="Ditinjau">Ditinjau</span>
                <span class="f" data-filter="Diproses">Diproses</span>
            </div>

            <div class="lbl">Request masuk (Total: <?= count($daftarRequest) ?>)</div>
            <table>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Pengaju / Kementerian</th>
                        <th>Jenis Media</th>
                        <th>Tgl Publikasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="daftarRequest">
                    <?php if (!empty($daftarRequest)): ?>
                        <?php foreach ($daftarRequest as $row):
                            $status = $row['statusPengajuan'];
                            $badgeClass = 's-waiting';
                            if ($status === 'Ditinjau')
                                $badgeClass = 's-ditinjau';
                            elseif ($status === 'Diproses')
                                $badgeClass = 's-diproses';
                            elseif ($status === 'Fix' || $status === 'Selesai')
                                $badgeClass = 's-diproses';
                            elseif ($status === 'Ditolak')
                                $badgeClass = 's-ditolak';
                            elseif (stripos($status, 'verifikasi') !== false)
                                $badgeClass = 'badge verifikasi';

                            $instansi = $row['asalKementerian'] ?: ($row['namaPengaju'] ?: 'Pengaju Internal');
                            ?>
                            <tr data-id="<?= htmlspecialchars($row['idPengajuan']) ?>"
                                data-status="<?= htmlspecialchars($status) ?>">
                                <td class="no"><?= htmlspecialchars($row['idPengajuan']) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($instansi) ?></strong><br>
                                    <small style="color:#64748b;"><?= htmlspecialchars($row['judulRequest']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['jenisMedia'] ?? 'Instagram Feeds') ?></td>
                                <td>
                                    <?= htmlspecialchars($row['targetTanggal'] ?? $row['tanggalPengajuan']) ?>
                                    <?= !empty($row['targetJam']) ? '<br><small style="color:#64748b; font-weight:600;">' . htmlspecialchars($row['targetJam']) . ' WIB</small>' : '' ?>
                                </td>
                                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span></td>
                                <td>
                                    <a href="detail-review.php?id=<?= urlencode($row['idPengajuan']) ?>"
                                        class="btn btn-lihat-detail" style="text-decoration:none;">
                                        Lihat Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:32px; color:#64748b;">
                                Belum ada permohonan request publikasi.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- === AKHIR BLOK KODE: ADINDA === -->
    </div>

    <footer class="foot">&copy; 2026 Biro Center of Media and Information (CMI) &bull; BEM FILKOM Universitas Brawijaya.
    </footer>
    <script src="../../assets/js/countdown.js"></script>
    <script src="../../assets/js/admin.js"></script>
</body>

</html>