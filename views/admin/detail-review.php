<?php
require_once __DIR__ . '/../../config/koneksi.php';

$idPengajuan = $_GET['id'] ?? 'REQ-001';
$pesanSukses = '';
$pesanError = '';

// Alur 2: Peninjauan Admin CMI (UPDATE status Proses / Tolak & Catat Kelengkapan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $id = $_POST['idPengajuan'] ?? $idPengajuan;
    $idCMI = $_SESSION['admin']['idCMI'] ?? 'CMI-001';
    $aksi = $_POST['aksi'];

    if ($aksi === 'proses') {
        $updateSql = "UPDATE PENGAJUAN SET statusPengajuan = 'Diproses' WHERE idPengajuan = ?";
        $stmt = mysqli_prepare($koneksi, $updateSql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Rekam Jejak Audit Log CMI
            $idLog = 'LOG-' . strtoupper(bin2hex(random_bytes(4)));
            $logSql = "INSERT INTO RIWAYAT_LOG (idLog, idPengajuan, idCMI, aksi, waktu) VALUES (?, ?, ?, 'Memproses Request', NOW())";
            $logStmt = mysqli_prepare($koneksi, $logSql);
            if ($logStmt) {
                mysqli_stmt_bind_param($logStmt, "sss", $idLog, $id, $idCMI);
                mysqli_stmt_execute($logStmt);
                mysqli_stmt_close($logStmt);
            }

            // Notifikasi ke Pengaju
            $qPengaju = mysqli_query($koneksi, "SELECT p.idPengaju, pi.email FROM PENGAJUAN p JOIN PENGAJU_INTERNAL pi ON p.idPengaju = pi.idPengaju WHERE p.idPengajuan = '" . mysqli_real_escape_string($koneksi, $id) . "'");
            if ($rowP = mysqli_fetch_assoc($qPengaju)) {
                $idNotif = 'NOTIF-' . strtoupper(bin2hex(random_bytes(4)));
                $isiNotif = 'Permohonan publikasi Anda (' . $id . ') telah disetujui dan sedang diproses oleh Biro CMI.';
                mysqli_query($koneksi, "INSERT INTO NOTIFIKASI (idNotifikasi, idPengajuan, idPengaju, emailTujuan, isiPesan) VALUES ('$idNotif', '$id', '{$rowP['idPengaju']}', '{$rowP['email']}', '$isiNotif')");
            }

            header("Location: detail-review.php?id=" . urlencode($id) . "&status=processed");
            exit;
        }
    } elseif ($aksi === 'tolak') {
        $alasan = trim($_POST['alasanPenolakan'] ?? '');
        if (empty($alasan)) {
            $pesanError = 'Alasan penolakan wajib diisi!';
        } else {
            $updateSql = "UPDATE PENGAJUAN SET statusPengajuan = 'Ditolak', alasanPenolakan = ? WHERE idPengajuan = ?";
            $stmt = mysqli_prepare($koneksi, $updateSql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ss", $alasan, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Log audit
                $idLog = 'LOG-' . strtoupper(bin2hex(random_bytes(4)));
                $logSql = "INSERT INTO RIWAYAT_LOG (idLog, idPengajuan, idCMI, aksi, waktu) VALUES (?, ?, ?, 'Menolak Request', NOW())";
                $logStmt = mysqli_prepare($koneksi, $logSql);
                if ($logStmt) {
                    mysqli_stmt_bind_param($logStmt, "sss", $idLog, $id, $idCMI);
                    mysqli_stmt_execute($logStmt);
                    mysqli_stmt_close($logStmt);
                }

                // Notifikasi penolakan
                $qPengaju = mysqli_query($koneksi, "SELECT p.idPengaju, pi.email FROM PENGAJUAN p JOIN PENGAJU_INTERNAL pi ON p.idPengaju = pi.idPengaju WHERE p.idPengajuan = '" . mysqli_real_escape_string($koneksi, $id) . "'");
                if ($rowP = mysqli_fetch_assoc($qPengaju)) {
                    $idNotif = 'NOTIF-' . strtoupper(bin2hex(random_bytes(4)));
                    $isiNotif = 'Permohonan publikasi Anda (' . $id . ') ditolak. Alasan: ' . $alasan;
                    mysqli_query($koneksi, "INSERT INTO NOTIFIKASI (idNotifikasi, idPengajuan, idPengaju, emailTujuan, isiPesan) VALUES ('$idNotif', '$id', '{$rowP['idPengaju']}', '{$rowP['email']}', '" . mysqli_real_escape_string($koneksi, $isiNotif) . "')");
                }

                header("Location: detail-review.php?id=" . urlencode($id) . "&status=rejected");
                exit;
            }
        }
    } elseif ($aksi === 'catat_kelengkapan') {
        $catatan = trim($_POST['catatanKelengkapan'] ?? '');
        $updateSql = "UPDATE PENGAJUAN SET catatanKelengkapan = ? WHERE idPengajuan = ?";
        $stmt = mysqli_prepare($koneksi, $updateSql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $catatan, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            header("Location: detail-review.php?id=" . urlencode($id) . "&status=noted");
            exit;
        }
    }
}

// Ambil Status Query Param untuk Banner Notifikasi
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'processed') {
        $pesanSukses = 'Request berhasil diproses. Notifikasi otomatis telah terkirim ke pengaju.';
    } elseif ($_GET['status'] === 'rejected') {
        $pesanSukses = 'Request berhasil ditolak. Catatan alasan penolakan telah dikirimkan ke pengaju.';
    } elseif ($_GET['status'] === 'noted') {
        $pesanSukses = 'Catatan kelengkapan materi berhasil disimpan.';
    }
}

// Ambil Data Pengajuan dari Database
$pengajuan = null;
$berkasList = [];
$riwayatLogList = [];

if ($koneksi) {
    $idSafe = mysqli_real_escape_string($koneksi, $idPengajuan);
    $q = mysqli_query($koneksi, "
        SELECT p.*, k.asalKementerian, pi.nama AS namaPengaju, pi.email AS emailPengaju, pi.nim
        FROM PENGAJUAN p
        LEFT JOIN KEMENTERIAN k ON p.kementerianId = k.kementerianId
        LEFT JOIN PENGAJU_INTERNAL pi ON p.idPengaju = pi.idPengaju
        WHERE p.idPengajuan = '$idSafe'
    ");
    if ($q && mysqli_num_rows($q) > 0) {
        $pengajuan = mysqli_fetch_assoc($q);
    }

    if ($pengajuan) {
        // Ambil Berkas Lampiran
        $qBerkas = mysqli_query($koneksi, "SELECT * FROM BERKAS_LAMPIRAN WHERE idPengajuan = '$idSafe'");
        if ($qBerkas) {
            while ($b = mysqli_fetch_assoc($qBerkas)) {
                $berkasList[] = $b;
            }
        }

        // Ambil Riwayat Audit CMI
        $qLog = mysqli_query($koneksi, "
            SELECT rl.*, c.namaAdmin 
            FROM RIWAYAT_LOG rl 
            LEFT JOIN CMI c ON rl.idCMI = c.idCMI 
            WHERE rl.idPengajuan = '$idSafe' 
            ORDER BY rl.waktu DESC
        ");
        if ($qLog) {
            while ($l = mysqli_fetch_assoc($qLog)) {
                $riwayatLogList[] = $l;
            }
        }
    }
}

// Fallback jika id tidak ditemukan
if (!$pengajuan) {
    $pengajuan = [
        'idPengajuan' => $idPengajuan,
        'judulRequest' => 'Permohonan Publikasi',
        'statusPengajuan' => 'Menunggu Peninjauan',
        'asalKementerian' => 'Kementerian Human Capital',
        'namaPengaju' => 'Gion Yehezkiel Sihotang',
        'jenisMedia' => 'Instagram Feeds',
        'tanggalPengajuan' => date('Y-m-d'),
        'targetTanggal' => date('Y-m-d', strtotime('+3 days')),
        'detailKonten' => 'Detail konten pengajuan publikasi...',
        'catatanKelengkapan' => '',
        'alasanPenolakan' => ''
    ];
}

$status = $pengajuan['statusPengajuan'];
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

$instansi = $pengajuan['asalKementerian'] ?: ($pengajuan['namaPengaju'] ?: 'Pengaju Internal');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Review Request — CMI BEM FILKOM UB</title>
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
            <a href="dashboard-cmi.php">Dashboard CMI</a>
            <a href="daftar-permohonan.php">Daftar Permohonan</a>
        </div>
        <div class="right">
            <span class="pill">Staf CMI: <?= htmlspecialchars($_SESSION['admin']['namaAdmin'] ?? 'Admin CMI') ?></span>
            <a href="../../index.php" style="color: inherit; text-decoration: none;">Keluar</a>
        </div>
    </nav>

    <div class="wrap">
        <div class="crumb">
            <a href="dashboard-cmi.php">Dashboard CMI</a> &rsaquo; Detail Request
            <?= htmlspecialchars($pengajuan['idPengajuan']) ?>
        </div>

        <!-- [ADN - IM-006] DetailRequestDiproses (UI-11-04): banner notifikasi server-side -->
        <?php if (!empty($pesanSukses)): ?>
            <div class="alert alert-success" id="bannerRequestDiproses" style="display: block; margin-bottom: 20px;">
                <?= htmlspecialchars($pesanSukses) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesanError)): ?>
            <div class="alert alert-danger"
                style="display: block; margin-bottom: 20px; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:12px 16px; border-radius:8px;">
                <?= htmlspecialchars($pesanError) ?>
            </div>
        <?php endif; ?>

        <?php if ($status === 'Ditolak' && !empty($pengajuan['alasanPenolakan'])): ?>
            <div class="alert"
                style="display: block; margin-bottom: 20px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:14px 18px; border-radius:8px;">
                <strong>Pengajuan Ini Telah Ditolak</strong><br>
                <span>Alasan Penolakan: <?= htmlspecialchars($pengajuan['alasanPenolakan']) ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="head">
                <h1>Detail Request <?= htmlspecialchars($pengajuan['idPengajuan']) ?></h1>
                <span class="badge <?= $badgeClass ?>" id="badgeStatusReview"><?= htmlspecialchars($status) ?></span>
            </div>

            <div class="info">
                <div>
                    <div class="lbl">Pengaju / Lembaga</div>
                    <div class="val"><?= htmlspecialchars($instansi) ?></div>
                </div>
                <div>
                    <div class="lbl">Tanggal Publikasi Target</div>
                    <div class="val">
                        <?= htmlspecialchars($pengajuan['targetTanggal'] ?? $pengajuan['tanggalPengajuan']) ?></div>
                </div>
                <div>
                    <div class="lbl">Jenis Postingan</div>
                    <div class="val"><?= htmlspecialchars($pengajuan['jenisMedia'] ?? 'Instagram Feeds') ?></div>
                </div>
            </div>

            <div class="lbl">Judul Request</div>
            <div class="box" style="font-weight: 700; margin-bottom: 16px;">
                <?= htmlspecialchars($pengajuan['judulRequest']) ?>
            </div>

            <div class="lbl">Detail Konten / Redaksional</div>
            <div class="box" style="white-space: pre-line; margin-bottom: 16px;">
                <?= htmlspecialchars($pengajuan['detailKonten']) ?>
            </div>

            <div class="lbl">File Lampiran Acuan / Desain</div>
            <div class="chips" style="margin-bottom: 20px;">
                <?php if (!empty($berkasList)): ?>
                    <?php foreach ($berkasList as $berkas): ?>
                        <a href="../../<?= htmlspecialchars($berkas['pathPenyimpanan']) ?>" target="_blank" class="chip"
                            style="text-decoration:none; color:inherit; display:inline-flex; align-items:center; gap:6px;">
                            <span>📎</span>
                            <strong><?= htmlspecialchars($berkas['namaBerkas']) ?></strong>
                            <small>(<?= htmlspecialchars($berkas['ukuranBerkas']) ?> MB -
                                <?= htmlspecialchars($berkas['kategoriBerkas']) ?>)</small>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="chip" style="color:#64748b;">Tidak ada berkas lampiran terunggah</span>
                <?php endif; ?>
            </div>

            <!-- [ADN - IM-004] DetailRequestSOP (UI-11-02): checklist kesesuaian SOP -->
            <div class="lbl">Pemeriksaan Kesesuaian SOP Publikasi BEM FILKOM</div>
            <label class="check" style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <input type="checkbox" class="cek-sop" checked>
                Materi konten sesuai dengan norma, identitas, dan ketentuan BEM FILKOM UB
            </label>
            <label class="check" style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
                <input type="checkbox" class="cek-sop" checked>
                Syarat pengajuan terpenuhi (Minimal H-3 sebelum rencana publikasi)
            </label>
            <label class="check" style="display:flex; align-items:center; gap:8px; margin-bottom:16px;">
                <input type="checkbox" class="cek-sop" checked>
                Bukti pemenuhan syarat dan kelengkapan materi jelas
            </label>

            <!-- [ADN - IM-007] CatatanKelengkapanMateri (UI-11-05): catatan kelengkapan -->
            <form method="POST" action="detail-review.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>"
                id="formCatatanKelengkapan">
                <input type="hidden" name="aksi" value="catat_kelengkapan">
                <input type="hidden" name="idPengajuan" value="<?= htmlspecialchars($pengajuan['idPengajuan']) ?>">

                <div class="lbl">Catatan Kelengkapan Materi (dari Staf CMI)</div>
                <textarea name="catatanKelengkapan" id="textareaCatatanKelengkapan" rows="3"
                    placeholder="Tulis catatan kelengkapan materi yang perlu diperhatikan atau diperbaiki pengaju..."><?= htmlspecialchars($pengajuan['catatanKelengkapan'] ?? '') ?></textarea>
            </form>

            <!-- [ADN - IM-006] Riwayat Jejak Audit CMI -->
            <div class="lbl" style="margin-top:20px;">Riwayat Aktivitas &amp; Log CMI</div>
            <ul class="box" id="auditLogList" style="list-style:none; padding:12px 16px; margin-bottom:24px;">
                <?php if (!empty($riwayatLogList)): ?>
                    <?php foreach ($riwayatLogList as $log): ?>
                        <li style="padding:6px 0; border-bottom:1px solid #e2e8f0; font-size:13px;">
                            <strong><?= htmlspecialchars($log['waktu']) ?></strong> —
                            <span style="color:#1d4ed8; font-weight:600;"><?= htmlspecialchars($log['aksi']) ?></span>
                            oleh <em><?= htmlspecialchars($log['namaAdmin'] ?? 'Admin CMI') ?></em>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li style="color:#64748b;">Belum ada riwayat aktivitas CMI pada permohonan ini.</li>
                <?php endif; ?>
            </ul>

            <div class="actions"
                style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <a class="btn" href="dashboard-cmi.php">Kembali</a>

                <div class="grp" style="display:flex; gap:10px; align-items:center;">
                    <!-- Simpan Catatan Kelengkapan -->
                    <button class="btn" type="button"
                        onclick="document.getElementById('formCatatanKelengkapan').submit()">Simpan Catatan</button>

                    <?php if ($status === 'Menunggu Peninjauan' || $status === 'Ditinjau'): ?>
                        <!-- Tombol Tolak memicu Modal Tolak -->
                        <button class="btn" type="button" id="btnTolakRequest"
                            style="background:#fee2e2; color:#b91c1c; border-color:#fca5a5;">Tolak Request</button>

                        <!-- Tombol Proses memicu Modal Konfirmasi -->
                        <button class="btn primary" type="button" id="btnProsesRequest">Terima &amp; Proses Request</button>
                    <?php elseif ($status === 'Diproses' || $status === 'Dalam Revisi Desain' || $status === 'Menunggu Verifikasi'): ?>
                        <a href="unggah-desain.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>" class="btn primary"
                            style="text-decoration:none;">
                            🎨 Unggah Hasil Desain
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- [ADN - IM-005] DialogKonfirmasiProses (UI-11-03): modal konfirmasi Terima & Proses -->
        <div class="modal-overlay" id="modalKonfirmasiProses" hidden>
            <div class="modal-dialog">
                <h2>Proses request ini?</h2>
                <p>Status request akan diperbarui menjadi <strong>"Diproses"</strong> dan pengaju akan menerima
                    notifikasi otomatis.</p>
                <form method="POST" action="detail-review.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>">
                    <input type="hidden" name="aksi" value="proses">
                    <input type="hidden" name="idPengajuan" value="<?= htmlspecialchars($pengajuan['idPengajuan']) ?>">
                    <div class="actions" style="margin-top:20px; display:flex; justify-content:flex-end; gap:12px;">
                        <button class="btn" type="button" id="btnBatalProses">Batal</button>
                        <button class="btn primary" type="submit" id="btnYaProses">Ya, Proses Request</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- [UI-12-01] Modal Alasan Penolakan Request -->
        <div class="modal-overlay" id="modalAlasanPenolakan" hidden>
            <div class="modal-dialog">
                <h2>Tolak Request <?= htmlspecialchars($pengajuan['idPengajuan']) ?></h2>
                <p style="font-size:13px; color:#64748b; margin-top:4px;">
                    Tuliskan alasan penolakan secara jelas. Catatan ini akan dikirimkan kepada kementerian pengaju.
                </p>
                <form method="POST" action="detail-review.php?id=<?= urlencode($pengajuan['idPengajuan']) ?>"
                    id="formTolakRequest">
                    <input type="hidden" name="aksi" value="tolak">
                    <input type="hidden" name="idPengajuan" value="<?= htmlspecialchars($pengajuan['idPengajuan']) ?>">

                    <div style="margin: 16px 0;">
                        <label for="textareaAlasanPenolakan"
                            style="display:block; font-weight:700; font-size:13px; margin-bottom:6px;">
                            Alasan Penolakan <span style="color:#ef4444;">*</span>
                        </label>
                        <textarea name="alasanPenolakan" id="textareaAlasanPenolakan" required rows="4"
                            style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:10px; font-size:13px;"
                            placeholder="Tulis alasan penolakan, misalnya berkas tidak lengkap atau melanggar SOP H-3..."></textarea>
                    </div>

                    <div class="actions" style="display:flex; justify-content:flex-end; gap:12px;">
                        <button class="btn" type="button" id="btnCancelTolak">Batal</button>
                        <button class="btn primary" type="submit"
                            style="background:#ef4444; border-color:#ef4444;">Kirim Penolakan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/js/admin.js"></script>
</body>

</html>