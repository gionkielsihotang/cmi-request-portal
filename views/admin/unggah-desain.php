<?php
require_once __DIR__ . '/../../config/koneksi.php';

$idPengajuan = $_GET['id'] ?? 'REQ-002';
$error = '';
$sukses = false;

// Handle Form Kirim Desain (PC-CPD-01 / UC-13)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['idPengajuan'] ?? $idPengajuan;
    $idCMI = $_SESSION['admin']['idCMI'] ?? 'CMI-001';
    $catatanDesain = trim($_POST['catatanDesain'] ?? '');

    if (!isset($_FILES['fileDesain']) || $_FILES['fileDesain']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Berkas desain wajib diunggah!';
    } else {
        $file = $_FILES['fileDesain'];
        $fileName = basename($file['name']);
        $fileSize = $file['size'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['png', 'jpg', 'jpeg', 'pdf'];

        // Validasi: PNG, JPG, JPEG, PDF & Ukuran maksimal 10 MB
        if (!in_array($ext, $allowedExt) || $fileSize > 10 * 1024 * 1024) {
            $error = 'Format berkas tidak didukung atau ukuran berkas melebihi batas 10MB!';
        } else {
            $uploadDir = __DIR__ . '/../../assets/uploads/hasil_desain/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $uniqueName = 'desain-' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $fileName);
            $destination = $uploadDir . $uniqueName;
            $dbPath = 'assets/uploads/hasil_desain/' . $uniqueName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $fileSizeMB = round($fileSize / (1024 * 1024), 2);
                $idBerkas = 'BRK-' . strtoupper(bin2hex(random_bytes(4)));

                // 1. Simpan berkas dengan kategori HasilDesain
                $sqlBerkas = "INSERT INTO BERKAS_LAMPIRAN (idBerkas, idPengajuan, namaBerkas, formatBerkas, ukuranBerkas, kategoriBerkas, pathPenyimpanan) VALUES (?, ?, ?, ?, ?, 'HasilDesain', ?)";
                $stmtB = mysqli_prepare($koneksi, $sqlBerkas);
                if ($stmtB) {
                    mysqli_stmt_bind_param($stmtB, "ssssds", $idBerkas, $id, $fileName, $ext, $fileSizeMB, $dbPath);
                    mysqli_stmt_execute($stmtB);
                    mysqli_stmt_close($stmtB);
                }

                // 2. Perbarui status pengajuan menjadi 'Menunggu Verifikasi' & timer 3600 detik
                $updateSql = "UPDATE PENGAJUAN SET statusPengajuan = 'Menunggu Verifikasi', sisaWaktuVerifikasi = 3600 WHERE idPengajuan = ?";
                $stmtU = mysqli_prepare($koneksi, $updateSql);
                if ($stmtU) {
                    mysqli_stmt_bind_param($stmtU, "s", $id);
                    mysqli_stmt_execute($stmtU);
                    mysqli_stmt_close($stmtU);
                }

                // 3. Catat audit log CMI
                $idLog = 'LOG-' . strtoupper(bin2hex(random_bytes(4)));
                $logAksi = 'Mengunggah Hasil Desain' . (!empty($catatanDesain) ? ' - ' . substr($catatanDesain, 0, 40) : '');
                $logSql = "INSERT INTO RIWAYAT_LOG (idLog, idPengajuan, idCMI, aksi, waktu) VALUES (?, ?, ?, ?, NOW())";
                $stmtL = mysqli_prepare($koneksi, $logSql);
                if ($stmtL) {
                    mysqli_stmt_bind_param($stmtL, "ssss", $idLog, $id, $idCMI, $logAksi);
                    mysqli_stmt_execute($stmtL);
                    mysqli_stmt_close($stmtL);
                }

                // 4. Kirim notifikasi sistem ke pengaju
                $qP = mysqli_query($koneksi, "SELECT p.idPengaju, pi.email FROM PENGAJUAN p JOIN PENGAJU_INTERNAL pi ON p.idPengaju = pi.idPengaju WHERE p.idPengajuan = '" . mysqli_real_escape_string($koneksi, $id) . "'");
                if ($rowP = mysqli_fetch_assoc($qP)) {
                    $idNotif = 'NOTIF-' . strtoupper(bin2hex(random_bytes(4)));
                    $isiNotif = 'Hasil desain permohonan ' . $id . ' telah diunggah oleh CMI. Silakan verifikasi dalam batas waktu 1 jam.';
                    mysqli_query($koneksi, "INSERT INTO NOTIFIKASI (idNotifikasi, idPengajuan, idPengaju, emailTujuan, isiPesan) VALUES ('$idNotif', '$id', '{$rowP['idPengaju']}', '{$rowP['email']}', '$isiNotif')");
                }

                header("Location: unggah-desain.php?id=" . urlencode($id) . "&status=success");
                exit;
            } else {
                $error = 'Gagal menyimpan berkas ke server penyimpanan.';
            }
        }
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $sukses = true;
}

// Query pengajuan & berkas referensi
$req = null;
$referensiList = [];

if ($koneksi) {
    $safeId = mysqli_real_escape_string($koneksi, $idPengajuan);
    $q = mysqli_query($koneksi, "
        SELECT p.*, k.asalKementerian, pi.nama AS namaPengaju 
        FROM PENGAJUAN p 
        LEFT JOIN KEMENTERIAN k ON p.kementerianId = k.kementerianId 
        LEFT JOIN PENGAJU_INTERNAL pi ON p.idPengaju = pi.idPengaju 
        WHERE p.idPengajuan = '$safeId'
    ");
    if ($q && mysqli_num_rows($q) > 0) {
        $req = mysqli_fetch_assoc($q);

        $qRef = mysqli_query($koneksi, "SELECT * FROM BERKAS_LAMPIRAN WHERE idPengajuan = '$safeId' AND kategoriBerkas = 'Referensi'");
        if ($qRef) {
            while ($ref = mysqli_fetch_assoc($qRef)) {
                $referensiList[] = $ref;
            }
        }
    }
}

if (!$req) {
    $req = [
        'idPengajuan' => $idPengajuan,
        'judulRequest' => 'Permohonan Publikasi',
        'asalKementerian' => 'Kementerian Human Capital',
        'jenisMedia' => 'Instagram Feeds',
        'statusPengajuan' => 'Diproses'
    ];
}

$instansi = $req['asalKementerian'] ?: ($req['namaPengaju'] ?: 'Pengaju Internal');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengunggahan Hasil Desain - CMI BEM FILKOM</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <style>
        .upload-card {
            max-width: 900px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .spec-bar {
            display: flex;
            gap: 20px;
            background: #eff6ff;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            color: #1e40af;
            margin: 15px 0;
            flex-wrap: wrap;
        }

        .dropzone {
            border: 2px dashed #94a3b8;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .dropzone:hover {
            border-color: #2563eb;
            background: #f1f5f9;
        }

        .dropzone.error {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .ref-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 12px;
            margin: 15px 0;
        }

        .ref-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }

        .banner-alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .banner-danger {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .banner-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <img src="../../assets/img/logo cmi.jpeg" alt="Logo CMI"
                style="width:32px; height:32px; border-radius:6px; object-fit:cover; margin-right:8px; vertical-align:middle;">
            CMI BEM FILKOM UB <small>Portal Publikasi &amp; Riset</small>
        </div>
        <div class="nav-menu">
            <a href="dashboard-cmi.php">Dashboard</a>
            <a href="daftar-permohonan.php" class="active">Daftar Permohonan</a>
        </div>
        <div class="nav-user">
            <span class="user-role">Staf CMI:
                <?= htmlspecialchars($_SESSION['admin']['namaAdmin'] ?? 'Admin CMI') ?></span>
            <a href="../../index.php" class="btn-logout">Keluar</a>
        </div>
    </nav>

    <div class="container">
        <div class="breadcrumb">
            <a href="dashboard-cmi.php" style="color:inherit; text-decoration:none;">Beranda</a> &gt;
            <a href="daftar-permohonan.php" style="color:inherit; text-decoration:none;">Daftar Permohonan</a> &gt;
            <?= htmlspecialchars($req['idPengajuan']) ?> &gt; Unggah Desain
        </div>

        <div class="upload-card">
            <!-- UI-13-02: Alert Berkas Gagal/Melebihi Batas -->
            <?php if (!empty($error)): ?>
                <div id="alertFileError" class="banner-alert banner-danger" style="display:block;">
                    <strong>Format berkas tidak didukung atau ukuran berkas melebihi batas 10MB!</strong><br>
                    <?= htmlspecialchars($error) ?> Sistem hanya menerima format PNG, JPG, JPEG, atau PDF dengan kapasitas
                    maksimal 10MB.
                </div>
            <?php endif; ?>

            <!-- UI-13-03: Alert Berhasil Diunggah & Timer 1 Jam Aktif -->
            <?php if ($sukses || $req['statusPengajuan'] === 'Menunggu Verifikasi'): ?>
                <div id="alertUploadSuccess" class="banner-alert banner-success" style="display:block;">
                    <strong>Berkas desain berhasil diunggah dan dikirimkan ke pemohon internal!</strong><br>
                    Status permohonan kini diperbarui menjadi <strong>Menunggu Verifikasi</strong>. Batas toleransi
                    verifikasi 1 jam telah diaktifkan di sisi Pengaju.
                </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap:wrap; gap:10px;">
                <div>
                    <span style="font-size: 11px; font-weight: 700; color: #64748b; letter-spacing: 1px;">PORTAL TIM KREATIF CMI</span>
                    <h2 style="color: #1e3a8a; margin-top: 4px;">Formulir Pengunggahan Hasil Desain</h2>
                </div>
                <span class="badge badge-process"
                    id="badgeStatusSaatIni"><?= htmlspecialchars($req['statusPengajuan']) ?></span>
            </div>

            <!-- Ringkasan Info Permohonan -->
            <div
                style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; background: #f8fafc; padding: 14px; border-radius: 8px; margin-top: 15px;">
                <div><small style="color: #64748b;">No.
                        Pengajuan:</small><br><strong><?= htmlspecialchars($req['idPengajuan']) ?></strong></div>
                <div><small
                        style="color: #64748b;">Kementerian/Biro:</small><br><strong><?= htmlspecialchars($instansi) ?></strong>
                </div>
                <div><small style="color: #64748b;">Jenis
                        Publikasi:</small><br><strong><?= htmlspecialchars($req['jenisMedia'] ?? 'Instagram Feeds') ?></strong>
                </div>
            </div>

            <!-- Ketentuan Desain Singkat Sesuai SOP -->
            <div class="spec-bar">
                <span>📐 Rasio 1:1 (Square) / 9:16 (Story)</span>
                <span>🎨 Warna dominan biru BEM FILKOM</span>
                <span>🏛️ Memuat logo resmi BEM &amp; FILKOM UB</span>
            </div>

            <!-- Form Upload Desain (PC-CPD-01) -->
            <form id="formKirimDesain" method="POST" action="unggah-desain.php?id=<?= urlencode($req['idPengajuan']) ?>"
                enctype="multipart/form-data">
                <input type="hidden" name="idPengajuan" value="<?= htmlspecialchars($req['idPengajuan']) ?>">

                <div class="form-group" style="margin-top:16px;">
                    <label><strong>Berkas Desain Final *</strong></label>
                    <div class="dropzone" id="dropzoneArea" onclick="document.getElementById('inputDesain').click()">
                        <div style="font-size: 36px; color: #3b82f6; margin-bottom: 8px;">☁️</div>
                        <p style="font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 4px;">
                            Tarik &amp; letakkan berkas desain di sini, atau <span style="color: #2563eb;">Klik untuk
                                Menelusuri</span>
                        </p>
                        <p style="font-size: 12px; color: #64748b;">Format yang didukung: PNG, JPG, JPEG, PDF (Maksimal
                            10MB)</p>
                        <div id="fileInfoChip"
                            style="display: none; margin-top: 15px; font-weight: 600; color: #15803d; background: #f0fdf4; padding: 8px; border-radius: 6px;">
                        </div>
                    </div>
                    <input type="file" name="fileDesain" id="inputDesain" style="display: none;"
                        accept=".png,.jpg,.jpeg,.pdf" required onchange="handleFileSelected(this)">
                </div>

                <div class="form-group" style="margin-top: 16px;">
                    <label for="catatanDesain"><strong>Catatan Desainer (Opsional)</strong></label>
                    <textarea name="catatanDesain" id="catatanDesain" class="form-control" rows="2"
                        style="width:100%; border:1px solid #cbd5e1; border-radius:8px; padding:10px; font-family:inherit; font-size:13px;"
                        placeholder="Berikan catatan singkat mengenai versi revisi, penyesuaian font, atau aspek visual tertentu..."></textarea>
                </div>

                <!-- Referensi Acuan dari Pengaju -->
                <div style="margin-top: 20px;">
                    <span style="font-size: 13px; font-weight: 600; color: #475569;">Referensi Materi &amp; Konsep
                        Terkait (dari Pengaju):</span>
                    <div class="ref-card-grid">
                        <?php if (!empty($referensiList)): ?>
                            <?php foreach ($referensiList as $ref): ?>
                                <div class="ref-item">
                                    <div>
                                        📄 <strong><?= htmlspecialchars($ref['namaBerkas']) ?></strong><br>
                                        <small style="color: #64748b;"><?= htmlspecialchars($ref['ukuranBerkas']) ?> MB &bull;
                                            Referensi</small>
                                    </div>
                                    <a href="../../<?= htmlspecialchars($ref['pathPenyimpanan']) ?>" target="_blank"
                                        style="color: #2563eb; text-decoration: none; font-size: 13px; font-weight:600;">Unduh /
                                        Buka</a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="ref-item" style="color:#64748b; font-size:12px;">Tidak ada dokumen referensi
                                terlampir.</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div
                    style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; flex-wrap:wrap; gap:12px;">
                    <a href="daftar-permohonan.php" class="btn btn-secondary" style="text-decoration:none;">&larr;
                        Kembali ke Daftar</a>
                    <button type="submit" class="btn btn-primary" id="btnSubmitDesain">Kirim Desain &rarr;</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function handleFileSelected(input) {
            const chip = document.getElementById('fileInfoChip');
            if (input.files && input.files[0]) {
                const f = input.files[0];
                const sizeMB = (f.size / (1024 * 1024)).toFixed(2);
                chip.style.display = 'block';
                chip.innerHTML = '✓ Berkas terpilih: <strong>' + f.name + '</strong> (' + sizeMB + ' MB)';
            }
        }
    </script>
</body>

</html>