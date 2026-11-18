<?php
require_once __DIR__ . '/../../config/koneksi.php';

$idPengajuan = $_GET['id'] ?? 'REQ-001';
$error = '';
$sukses = false;

// Handle Form Submission Penolakan (UC-12 / SD-12)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['idPengajuan'] ?? $idPengajuan;
  $alasan = trim($_POST['alasan'] ?? '');
  $idCMI = $_SESSION['admin']['idCMI'] ?? 'CMI-001';

  if (empty($alasan)) {
    $error = 'Alasan penolakan wajib diisi!';
  } else {
    if ($koneksi) {
      $updateSql = "UPDATE PENGAJUAN SET statusPengajuan = 'Ditolak', alasanPenolakan = ? WHERE idPengajuan = ?";
      $stmt = mysqli_prepare($koneksi, $updateSql);
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $alasan, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Rekam Jejak Audit Log CMI
        $idLog = 'LOG-' . strtoupper(bin2hex(random_bytes(4)));
        $logSql = "INSERT INTO RIWAYAT_LOG (idLog, idPengajuan, idCMI, aksi, waktu) VALUES (?, ?, ?, 'Menolak Request', NOW())";
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
          $isiNotif = 'Permohonan publikasi Anda (' . $id . ') telah ditolak oleh CMI. Alasan: ' . $alasan;
          mysqli_query($koneksi, "INSERT INTO NOTIFIKASI (idNotifikasi, idPengajuan, idPengaju, emailTujuan, isiPesan) VALUES ('$idNotif', '$id', '{$rowP['idPengaju']}', '{$rowP['email']}', '" . mysqli_real_escape_string($koneksi, $isiNotif) . "')");
        }

        header("Location: modal-tolak.php?id=" . urlencode($id) . "&status=rejected");
        exit;
      }
    }
  }
}

if (isset($_GET['status']) && $_GET['status'] === 'rejected') {
  $sukses = true;
}

// Ambil data pengajuan dari DB
$req = null;
$berkasList = [];

if ($koneksi) {
  $safeId = mysqli_real_escape_string($koneksi, $idPengajuan);
  $q = mysqli_query($koneksi, "
        SELECT p.*, k.asalKementerian, pi.nama AS namaPengaju, pi.email AS emailPengaju 
        FROM PENGAJUAN p 
        LEFT JOIN KEMENTERIAN k ON p.kementerianId = k.kementerianId 
        LEFT JOIN PENGAJU_INTERNAL pi ON p.idPengaju = pi.idPengaju 
        WHERE p.idPengajuan = '$safeId'
    ");
  if ($q && mysqli_num_rows($q) > 0) {
    $req = mysqli_fetch_assoc($q);

    $qB = mysqli_query($koneksi, "SELECT * FROM BERKAS_LAMPIRAN WHERE idPengajuan = '$safeId'");
    if ($qB) {
      while ($b = mysqli_fetch_assoc($qB)) {
        $berkasList[] = $b;
      }
    }
  }
}

if (!$req) {
  $req = [
    'idPengajuan' => $idPengajuan,
    'judulRequest' => 'Permohonan Publikasi',
    'statusPengajuan' => 'Ditinjau',
    'asalKementerian' => 'Kementerian Human Capital',
    'namaPengaju' => 'Gion Yehezkiel Sihotang',
    'jenisMedia' => 'Feeds',
    'targetTanggal' => date('Y-m-d'),
    'detailKonten' => 'Detail pengajuan publikasi...',
    'alasanPenolakan' => ''
  ];
}

$instansi = $req['asalKementerian'] ?: ($req['namaPengaju'] ?: 'Pengaju Internal');
$isDitolak = ($req['statusPengajuan'] === 'Ditolak' || $sukses);
$isDitinjau = ($req['statusPengajuan'] === 'Ditinjau' || $req['statusPengajuan'] === 'Menunggu Peninjauan') && !$sukses;
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tolak Request <?= htmlspecialchars($req['idPengajuan']) ?> – CMI BEM FILKOM UB</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --navy: #1b3a9e;
      --navy-dark: #16307f;
      --ink: #1c2757;
      --muted: #6b7599;
      --bg: #eef2fb;
      --card: #fff;
      --line: #e6eaf5;
      --head: #f3f5fb;
      --red: #d93025;
      --red-dark: #bd271d;
      --red-bg: #fff1f0;
      --green: #1f7a4d;
      --green-bg: #e9f8ef;
      --green-line: #8fd3ae;
      --font: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
    }

    * {
      box-sizing: border-box;
      margin: 0
    }

    html {
      height: 100%
    }

    body {
      min-height: 100%;
      display: flex;
      flex-direction: column;
      background: var(--bg);
      color: var(--ink);
      font: 14px/1.5 var(--font)
    }

    a {
      color: inherit;
      text-decoration: none
    }

    button {
      font: inherit;
      cursor: pointer
    }

    :focus-visible {
      outline: 2px solid var(--navy);
      outline-offset: 2px
    }

    h1:focus,
    h1:focus-visible {
      outline: none
    }

    [hidden] {
      display: none !important
    }

    /* ===== Navbar ===== */
    nav {
      background: #fff;
      border-bottom: 1px solid var(--line);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 0 28px;
      height: 64px;
      position: sticky;
      top: 0;
      z-index: 5
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px
    }

    .logo {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      object-fit: cover;
      display: block
    }

    .brand b {
      display: block;
      font-size: 12.5px;
      line-height: 1.2
    }

    .brand small {
      display: block;
      font-size: 10px;
      color: var(--muted)
    }

    .menu {
      display: flex;
      gap: 28px;
      height: 100%
    }

    .menu a {
      display: flex;
      align-items: center;
      height: 100%;
      font-size: 13px;
      color: var(--muted);
      border-bottom: 2px solid transparent;
      font-weight: 500
    }

    .menu a.active {
      color: var(--navy);
      font-weight: 700;
      border-bottom-color: var(--navy)
    }

    .right {
      display: flex;
      align-items: center;
      gap: 18px;
      font-size: 12px;
      color: var(--muted)
    }

    .pill {
      background: #dfe7fb;
      color: var(--navy);
      font-weight: 600;
      padding: 6px 20px;
      border-radius: 99px;
      font-size: 12px
    }

    .right a:hover {
      color: var(--navy)
    }

    /* ===== Layout ===== */
    main {
      flex: 1;
      width: 100%;
      max-width: 1040px;
      margin: 0 auto;
      padding: 26px 28px 48px
    }

    .crumb {
      font-size: 11px;
      color: var(--muted)
    }

    .crumb a:hover {
      color: var(--navy);
      text-decoration: underline
    }

    .titlebar {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 12px;
      margin: 2px 0 20px
    }

    h1 {
      font-size: 26px;
      line-height: 1.2;
      font-weight: 800
    }

    .status-top {
      font-size: 11px;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 8px;
      padding-bottom: 4px
    }

    footer {
      padding: 18px 28px;
      font-size: 11px;
      color: var(--muted)
    }

    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 14px
    }

    /* ===== Badge ===== */
    .badge {
      display: inline-block;
      font-size: 10.5px;
      font-weight: 700;
      padding: 3px 11px;
      border-radius: 99px;
      white-space: nowrap
    }

    .s-rev {
      background: #dce8fe;
      color: #1d4fd8
    }

    .s-rej {
      background: #fde0df;
      color: #c0281f
    }

    /* ===== Banner sukses (UI-12-03) ===== */
    .banner {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--green-bg);
      border: 1.5px solid var(--green-line);
      color: var(--green);
      border-radius: 99px;
      padding: 11px 18px;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 18px
    }

    /* ===== Detail ===== */
    .grid {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 270px;
      gap: 20px;
      align-items: start
    }

    .panel {
      padding: 24px 26px
    }

    .panel h2 {
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 16px
    }

    .row {
      display: grid;
      grid-template-columns: 130px 1fr;
      gap: 12px;
      padding: 9px 0;
      font-size: 12px;
      border-bottom: 1px solid var(--line)
    }

    .row:last-of-type {
      border-bottom: 0
    }

    .row dt {
      color: var(--muted)
    }

    .row dd {
      font-weight: 700;
      margin: 0
    }

    .sect {
      margin-top: 20px;
      padding-top: 18px;
      border-top: 1px solid var(--line)
    }

    .sect h3 {
      font-size: 12.5px;
      font-weight: 700;
      margin-bottom: 12px
    }

    .file {
      display: flex;
      align-items: center;
      gap: 12px;
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 10px 12px;
      margin-bottom: 10px;
      background: #fff;
      color: inherit
    }

    .ficon {
      width: 26px;
      height: 26px;
      border-radius: 6px;
      background: #e8edfa;
      display: grid;
      place-items: center;
      flex: none
    }

    .fname {
      font-size: 12px;
      font-weight: 700;
      display: block
    }

    .fsize {
      font-size: 10px;
      color: var(--muted);
      display: block
    }

    .side {
      padding: 20px
    }

    .side h2 {
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 12px
    }

    .side .info {
      font-size: 11px;
      color: var(--muted);
      margin-top: 10px
    }

    .btn {
      display: block;
      width: 100%;
      padding: 12px;
      border-radius: 9px;
      font-weight: 700;
      font-size: 13px;
      margin-top: 12px;
      border: 1.5px solid transparent;
      text-align: center
    }

    .btn.primary {
      background: var(--navy);
      color: #fff
    }

    .btn.primary:hover {
      background: var(--navy-dark)
    }

    .btn.danger {
      background: #fff;
      color: var(--red);
      border-color: var(--red)
    }

    .btn.danger:hover {
      background: var(--red-bg)
    }

    /* ===== Modal (UI-12-01, UI-12-02) ===== */
    .overlay {
      position: fixed;
      inset: 0;
      background: rgba(28, 39, 87, .55);
      display: grid;
      place-items: center;
      padding: 20px;
      z-index: 20
    }

    .dialog {
      background: #fff;
      border-radius: 14px;
      width: 100%;
      max-width: 440px;
      padding: 22px 24px 24px;
      box-shadow: 0 20px 50px rgba(20, 30, 70, .3)
    }

    .dialog h2 {
      font-size: 15px;
      font-weight: 800
    }

    .dialog .desc {
      font-size: 11.5px;
      color: var(--muted);
      margin-top: 6px
    }

    .dialog label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      margin: 14px 0 6px
    }

    .dialog label span {
      color: var(--red)
    }

    .dialog textarea {
      width: 100%;
      min-height: 96px;
      resize: vertical;
      border: 1.5px solid #d4dcf2;
      border-radius: 8px;
      padding: 10px 12px;
      font: inherit;
      font-size: 12.5px;
      color: var(--ink);
      background: #f6f8fe
    }

    .dialog textarea:focus {
      outline: none;
      border-color: var(--navy);
      background: #fff
    }

    .dialog textarea.err {
      border-color: var(--red);
      background: var(--red-bg)
    }

    .errmsg {
      font-size: 11px;
      font-weight: 600;
      color: var(--red);
      margin-top: 6px;
      min-height: 17px
    }

    .dactions {
      display: flex;
      gap: 12px;
      margin-top: 10px
    }

    .dactions button {
      flex: 1;
      padding: 12px;
      border-radius: 9px;
      font-weight: 700;
      font-size: 13px;
      border: 1.5px solid transparent
    }

    .btn-cancel {
      background: #fff;
      color: var(--ink);
      border-color: #d4dcf2 !important
    }

    .btn-cancel:hover {
      background: var(--head)
    }

    .btn-send {
      background: var(--red);
      color: #fff
    }

    .btn-send:hover {
      background: var(--red-dark)
    }

    @media (max-width:860px) {
      nav {
        padding: 10px 16px;
        height: auto;
        flex-wrap: wrap
      }

      .menu {
        order: 3;
        width: 100%;
        height: 40px;
        gap: 20px
      }

      main {
        padding: 20px 16px 40px
      }

      .grid {
        grid-template-columns: 1fr
      }

      .titlebar {
        flex-direction: column;
        align-items: flex-start
      }

      .row {
        grid-template-columns: 100px 1fr
      }
    }
  </style>
</head>

<body>

  <nav>
    <div class="brand">
      <img src="../../assets/img/logo cmi.jpeg" alt="Logo CMI" class="logo">
      <div><b>CMI BEM FILKOM UB</b><small>Portal Publikasi &amp; Riset</small></div>
    </div>
    <div class="menu">
      <a href="dashboard-cmi.php">Dashboard</a>
      <a href="daftar-permohonan.php" class="active" id="navDaftar">Daftar Permohonan</a>
    </div>
    <div class="right">
      <span class="pill">CMI: <?= htmlspecialchars($_SESSION['admin']['namaAdmin'] ?? 'Admin CMI') ?></span>
      <a href="../../index.php">Keluar</a>
    </div>
  </nav>

  <main>
    <div class="crumb">
      <a href="dashboard-cmi.php">Beranda</a> › <a href="daftar-permohonan.php">Daftar Permohonan</a> › Tolak Request
    </div>
    <div class="titlebar">
      <h1 id="hDetail" tabindex="-1">Detail Request <?= htmlspecialchars($req['idPengajuan']) ?></h1>
      <div class="status-top">Label status: <span id="topBadge"
          class="badge <?= $isDitolak ? 's-rej' : 's-rev' ?>"><?= $isDitolak ? 'Ditolak' : htmlspecialchars($req['statusPengajuan']) ?></span>
      </div>
    </div>

    <!-- UI-12-03 · DetailRequestDitolak (REV – IM-008): banner hijau -->
    <?php if ($sukses || $req['statusPengajuan'] === 'Ditolak'): ?>
      <div class="banner" id="banner" role="status">
        <span aria-hidden="true">✓</span>
        <span>Pengajuan berhasil ditolak. Notifikasi penolakan telah dikirim ke email pengaju.</span>
      </div>
    <?php endif; ?>

    <div class="grid">
      <div class="card panel">
        <h2>Rincian Isi Form</h2>
        <dl>
          <div class="row">
            <dt>No. Pengajuan</dt>
            <dd id="dNo"><?= htmlspecialchars($req['idPengajuan']) ?></dd>
          </div>
          <div class="row">
            <dt>Judul request</dt>
            <dd><?= htmlspecialchars($req['judulRequest']) ?></dd>
          </div>
          <div class="row">
            <dt>Jenis publikasi</dt>
            <dd id="dJenis"><?= htmlspecialchars($req['jenisMedia'] ?? 'Instagram Feeds') ?></dd>
          </div>
          <div class="row">
            <dt>Tanggal publikasi</dt>
            <dd id="dTgl"><?= htmlspecialchars($req['targetTanggal'] ?? $req['tanggalPengajuan']) ?></dd>
          </div>
          <div class="row">
            <dt>Pengaju</dt>
            <dd id="dPengaju"><?= htmlspecialchars($instansi) ?></dd>
          </div>
          <div class="row">
            <dt>Redaksional</dt>
            <dd id="dRedaksi" style="white-space:pre-line;"><?= htmlspecialchars($req['detailKonten']) ?></dd>
          </div>
          <?php if (!empty($req['alasanPenolakan'])): ?>
            <div class="row">
              <dt>Alasan Penolakan</dt>
              <dd style="color:var(--red);"><?= htmlspecialchars($req['alasanPenolakan']) ?></dd>
            </div>
          <?php endif; ?>
        </dl>
        <div class="sect">
          <h3>Berkas Lampiran</h3>
          <div id="dFiles">
            <?php if (!empty($berkasList)): ?>
              <?php foreach ($berkasList as $f): ?>
                <a href="../../<?= htmlspecialchars($f['pathPenyimpanan']) ?>" target="_blank" class="file">
                  <span class="ficon">📎</span>
                  <span>
                    <span class="fname"><?= htmlspecialchars($f['namaBerkas']) ?></span>
                    <span class="fsize"><?= htmlspecialchars($f['ukuranBerkas']) ?> MB •
                      <?= htmlspecialchars($f['kategoriBerkas']) ?></span>
                  </span>
                </a>
              <?php endforeach; ?>
            <?php else: ?>
              <p style="font-size:12px; color:var(--muted);">Tidak ada berkas lampiran terunggah.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <aside class="card side">
        <h2>Label Status</h2>
        <span id="sideBadge"
          class="badge <?= $isDitolak ? 's-rej' : 's-rev' ?>"><?= $isDitolak ? 'Ditolak' : htmlspecialchars($req['statusPengajuan']) ?></span>
        <p class="info" id="sideInfo">
          <?= $isDitolak ? 'Alasan penolakan telah dikirim ke email pengaju.' : ($isDitinjau ? 'Request sedang ditinjau. Pilih Terima atau Tolak.' : 'Request berstatus "' . htmlspecialchars($req['statusPengajuan']) . '".') ?>
        </p>
        <div id="actions">
          <?php if ($isDitinjau): ?>
            <a href="detail-review.php?id=<?= urlencode($req['idPengajuan']) ?>" class="btn primary" id="btnTerima">Terima
              &amp; Proses</a>
            <button class="btn danger" id="btnBukaModalTolak" type="button"
              onclick="document.getElementById('modal').hidden = false;">Tolak</button>
          <?php else: ?>
            <a href="daftar-permohonan.php" class="btn primary">Kembali ke Permohonan</a>
          <?php endif; ?>
        </div>
      </aside>
    </div>
  </main>

  <footer>© 2026 Biro Center of Media and Information (CMI) • BEM FILKOM Universitas Brawijaya.</footer>

  <!-- UI-12-01 · ModalAlasanPenolakan (REV – IM-006) + UI-12-02 · ModalPenolakanError -->
  <div class="overlay" id="modal" <?= ($isDitinjau && !empty($error)) ? '' : 'hidden' ?>>
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="mTitle">
      <h2 id="mTitle">Tolak Request <?= htmlspecialchars($req['idPengajuan']) ?></h2>
      <p class="desc" id="mDesc">Tuliskan alasan penolakan request ini. Alasan akan dikirim ke pengaju melalui sistem
        dan email.</p>

      <form method="POST" action="modal-tolak.php?id=<?= urlencode($req['idPengajuan']) ?>" id="formTolak">
        <input type="hidden" name="idPengajuan" value="<?= htmlspecialchars($req['idPengajuan']) ?>">
        <label for="alasan">Alasan penolakan <span aria-hidden="true">*</span></label>
        <textarea name="alasan" id="alasan" class="<?= !empty($error) ? 'err' : '' ?>"
          placeholder="Tulis alasan penolakan di sini..." required></textarea>

        <div class="errmsg" id="alasanErr" role="alert">
          <?= !empty($error) ? htmlspecialchars($error) : '' ?>
        </div>

        <div class="dactions">
          <button class="btn-cancel" type="button"
            onclick="document.getElementById('modal').hidden = true;">Cancel</button>
          <button class="btn-send" type="submit" id="btnSend">Send Penolakan</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Validasi client-side sederhana sebelum submit
    document.getElementById('formTolak')?.addEventListener('submit', function (e) {
      var val = document.getElementById('alasan').value.trim();
      if (!val) {
        e.preventDefault();
        document.getElementById('alasan').classList.add('err');
        document.getElementById('alasanErr').textContent = '! Alasan penolakan wajib diisi';
      }
    });
  </script>
</body>

</html>