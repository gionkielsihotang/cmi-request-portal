<?php
require_once __DIR__ . '/../../config/koneksi.php';

// Ambil data pengajuan dari database
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

// Cek apakah sedang melihat detail permohonan spesifik (?id=...)
$selectedId = $_GET['id'] ?? null;
$detailData = null;
$berkasList = [];

if ($selectedId && $koneksi) {
  $safeId = mysqli_real_escape_string($koneksi, $selectedId);
  $qDetail = mysqli_query($koneksi, "
        SELECT p.*, k.asalKementerian, pi.nama AS namaPengaju, pi.email AS emailPengaju 
        FROM PENGAJUAN p 
        LEFT JOIN KEMENTERIAN k ON p.kementerianId = k.kementerianId 
        LEFT JOIN PENGAJU_INTERNAL pi ON p.idPengaju = pi.idPengaju 
        WHERE p.idPengajuan = '$safeId'
    ");
  if ($qDetail && mysqli_num_rows($qDetail) > 0) {
    $detailData = mysqli_fetch_assoc($qDetail);

    $qB = mysqli_query($koneksi, "SELECT * FROM BERKAS_LAMPIRAN WHERE idPengajuan = '$safeId'");
    if ($qB) {
      while ($b = mysqli_fetch_assoc($qB)) {
        $berkasList[] = $b;
      }
    }
  }
}

// Helper untuk class badge status
function getBadgeClass($status)
{
  switch ($status) {
    case 'Menunggu Peninjauan':
      return 's-wait';
    case 'Ditinjau':
      return 's-rev';
    case 'Diproses':
      return 's-proc';
    case 'Ditolak':
      return 's-rej';
    case 'Fix':
    case 'Selesai':
      return 's-done';
    default:
      return 's-rev';
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Permohonan – CMI BEM FILKOM UB</title>
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
      --red-bg: #fff5f4;
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
      margin: 2px 0 22px
    }

    h1 {
      font-size: 26px;
      line-height: 1.2;
      font-weight: 800;
      color: var(--ink);
      outline: none
    }

    .sub {
      font-size: 12.5px;
      color: var(--muted);
      margin-top: 4px
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

    [hidden] {
      display: none !important
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

    .s-wait {
      background: #fdecc8;
      color: #8a5200
    }

    .s-rev {
      background: #dce8fe;
      color: #1d4fd8
    }

    .s-proc {
      background: #ece3fb;
      color: #6a3db8
    }

    .s-rej {
      background: #fde0df;
      color: #c0281f
    }

    .s-done {
      background: #d8f1e2;
      color: #23744a
    }

    /* ===== UI-10-01 Daftar ===== */
    .tbl-wrap {
      overflow-x: auto;
      padding: 0 0 18px
    }

    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 640px
    }

    th {
      background: var(--head);
      text-align: left;
      font-size: 11px;
      font-weight: 600;
      color: var(--muted);
      padding: 13px 18px
    }

    td {
      padding: 15px 18px;
      font-size: 12px;
      border-top: 1px solid var(--line)
    }

    th:first-child,
    td:first-child {
      padding-left: 26px
    }

    tbody tr {
      cursor: pointer
    }

    tbody tr:hover,
    tbody tr:focus-visible {
      background: #f6f8fe
    }

    td.no {
      font-weight: 600
    }

    td.go {
      width: 44px;
      text-align: right;
      color: var(--navy);
      font-weight: 800;
      font-size: 16px
    }

    .empty {
      padding: 36px;
      text-align: center;
      color: var(--muted)
    }

    /* ===== UI-10-02 Detail ===== */
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
      width: 100%;
      text-align: left;
      color: inherit
    }

    .ficon {
      width: 26px;
      height: 26px;
      border-radius: 6px;
      background: #e8edfa;
      display: grid;
      place-items: center;
      flex: none;
      font-size: 13px
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

    .btn.secondary {
      background: #f1f5f9;
      color: #334155;
      border-color: #cbd5e1
    }

    .lock {
      font-size: 11.5px;
      color: var(--muted);
      background: var(--head);
      border-radius: 9px;
      padding: 10px 12px;
      margin-top: 12px
    }

    .notes {
      font-size: 10px;
      color: var(--muted);
      margin: 14px 4px 0;
      line-height: 1.7
    }

    @media (max-width:860px) {
      nav {
        padding: 0 16px;
        height: auto;
        flex-wrap: wrap;
        padding-block: 10px
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

    @media (prefers-reduced-motion:reduce) {
      * {
        transition: none !important;
        animation: none !important
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
    <?php if (!$detailData): ?>
      <!-- ========== UI-10-01 · DaftarPermohonanCMI (REV – IM-004) ========== -->
      <section id="viewDaftar" aria-labelledby="hDaftar">
        <div class="crumb"><a href="dashboard-cmi.php">Beranda</a> › Daftar Permohonan</div>
        <div class="titlebar">
          <div>
            <h1 id="hDaftar" tabindex="-1">Daftar Permohonan</h1>
            <p class="sub">Seluruh request masuk beserta label status.</p>
          </div>
        </div>
        <div class="card tbl-wrap">
          <table>
            <thead>
              <tr>
                <th>No. Pengajuan</th>
                <th>Judul &amp; Jenis</th>
                <th>Tanggal Publikasi</th>
                <th>Pengaju / Kementerian</th>
                <th>Label Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="listBody">
              <?php if (!empty($daftarRequest)): ?>
                <?php foreach ($daftarRequest as $r):
                  $instansi = $r['asalKementerian'] ?: ($r['namaPengaju'] ?: 'Pengaju Internal');
                  $badgeCls = getBadgeClass($r['statusPengajuan']);
                  ?>
                  <tr onclick="location.href='daftar-permohonan.php?id=<?= urlencode($r['idPengajuan']) ?>'" tabindex="0">
                    <td class="no"><?= htmlspecialchars($r['idPengajuan']) ?></td>
                    <td>
                      <strong><?= htmlspecialchars($r['judulRequest']) ?></strong><br>
                      <small
                        style="color:var(--muted);"><?= htmlspecialchars($r['jenisMedia'] ?? 'Instagram Feeds') ?></small>
                    </td>
                    <td>
                      <?= htmlspecialchars($r['targetTanggal'] ?? $r['tanggalPengajuan']) ?>
                      <?= !empty($r['targetJam']) ? '<br><small style="color:var(--muted); font-weight:600;">' . htmlspecialchars($r['targetJam']) . ' WIB</small>' : '' ?>
                    </td>
                    <td><?= htmlspecialchars($instansi) ?></td>
                    <td><span class="badge <?= $badgeCls ?>"><?= htmlspecialchars($r['statusPengajuan']) ?></span></td>
                    <td class="go">›</td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td class="empty" colspan="6">Belum ada request masuk.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>

    <?php else:
      $instansi = $detailData['asalKementerian'] ?: ($detailData['namaPengaju'] ?: 'Pengaju Internal');
      $badgeCls = getBadgeClass($detailData['statusPengajuan']);
      $canAct = ($detailData['statusPengajuan'] === 'Ditinjau' || $detailData['statusPengajuan'] === 'Menunggu Peninjauan');
      ?>
      <!-- ========== UI-10-02 · DetailRequestCMI (REV – IM-005) ========== -->
      <section id="viewDetail" aria-labelledby="hDetail">
        <div class="crumb">
          <a href="dashboard-cmi.php">Beranda</a> › <a href="daftar-permohonan.php">Daftar Permohonan</a> › Detail Request
        </div>
        <div class="titlebar">
          <h1 id="hDetail" tabindex="-1">Detail Request <?= htmlspecialchars($detailData['idPengajuan']) ?></h1>
          <div class="status-top">Label status: <span id="topBadge"
              class="badge <?= $badgeCls ?>"><?= htmlspecialchars($detailData['statusPengajuan']) ?></span></div>
        </div>

        <div class="grid">
          <div class="card panel">
            <h2>Rincian Isi Form</h2>
            <dl>
              <div class="row">
                <dt>No. Pengajuan</dt>
                <dd><?= htmlspecialchars($detailData['idPengajuan']) ?></dd>
              </div>
              <div class="row">
                <dt>Judul request</dt>
                <dd><?= htmlspecialchars($detailData['judulRequest']) ?></dd>
              </div>
              <div class="row">
                <dt>Jenis publikasi</dt>
                <dd><?= htmlspecialchars($detailData['jenisMedia'] ?? 'Instagram Feeds') ?></dd>
              </div>
              <div class="row">
                <dt>Jadwal publikasi</dt>
                <dd>
                  <?= htmlspecialchars($detailData['targetTanggal'] ?? $detailData['tanggalPengajuan']) ?>
                  <?= !empty($detailData['targetJam']) ? ' • Pukul ' . htmlspecialchars($detailData['targetJam']) . ' WIB' : '' ?>
                </dd>
              </div>
              <div class="row">
                <dt>Pengaju</dt>
                <dd><?= htmlspecialchars($instansi) ?></dd>
              </div>
              <div class="row">
                <dt>Redaksional</dt>
                <dd style="white-space:pre-line;"><?= htmlspecialchars($detailData['detailKonten']) ?></dd>
              </div>
              <?php if (!empty($detailData['catatanKelengkapan'])): ?>
                <div class="row">
                  <dt>Catatan CMI</dt>
                  <dd style="color:#2563eb;"><?= htmlspecialchars($detailData['catatanKelengkapan']) ?></dd>
                </div>
              <?php endif; ?>
              <?php if (!empty($detailData['alasanPenolakan'])): ?>
                <div class="row">
                  <dt>Alasan Penolakan</dt>
                  <dd style="color:var(--red);"><?= htmlspecialchars($detailData['alasanPenolakan']) ?></dd>
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
                  <p style="font-size:12px; color:var(--muted);">Tidak ada berkas lampiran.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <aside>
            <div class="card side">
              <h2>Label Status</h2>
              <span id="sideBadge" class="badge <?= $badgeCls ?>"
                style="margin-bottom:12px;"><?= htmlspecialchars($detailData['statusPengajuan']) ?></span>

              <div id="actions">
                <?php if ($canAct): ?>
                  <a href="detail-review.php?id=<?= urlencode($detailData['idPengajuan']) ?>" class="btn primary">Review
                    &amp; Terima</a>
                  <a href="modal-tolak.php?id=<?= urlencode($detailData['idPengajuan']) ?>" class="btn danger">Tolak</a>
                <?php elseif ($detailData['statusPengajuan'] === 'Diproses' || $detailData['statusPengajuan'] === 'Dalam Revisi Desain' || $detailData['statusPengajuan'] === 'Menunggu Verifikasi'): ?>
                  <a href="unggah-desain.php?id=<?= urlencode($detailData['idPengajuan']) ?>" class="btn primary">🎨 Unggah
                    Hasil Desain</a>
                  <a href="detail-review.php?id=<?= urlencode($detailData['idPengajuan']) ?>" class="btn secondary">Lihat
                    Audit Review</a>
                <?php else: ?>
                  <a href="detail-review.php?id=<?= urlencode($detailData['idPengajuan']) ?>" class="btn secondary">Lihat
                    Detail Lengkap</a>
                <?php endif; ?>
                <a href="daftar-permohonan.php" class="btn"
                  style="background:#fff; border:1px solid var(--line); color:var(--ink);">Kembali ke Daftar</a>
              </div>

              <?php if (!$canAct): ?>
                <p class="lock" id="lockInfo">
                  Request berstatus "<?= htmlspecialchars($detailData['statusPengajuan']) ?>".
                </p>
              <?php endif; ?>
            </div>

          </aside>
        </div>
      </section>
    <?php endif; ?>
  </main>

  <footer>© 2026 Biro Center of Media and Information (CMI) • BEM FILKOM Universitas Brawijaya.</footer>
</body>

</html>