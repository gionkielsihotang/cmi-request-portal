<?php
require_once __DIR__ . '/../../config/koneksi.php';
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pilihan Jenis Pengajuan — CMI BEM FILKOM UB</title>
  <link rel="stylesheet" href="../../assets/css/global.css">
  <link rel="stylesheet" href="../../assets/css/components.css">
  <link rel="stylesheet" href="../../assets/css/modal.css">
  <link rel="stylesheet" href="../../assets/css/portal.css">
</head>

<body data-page="pilihan">
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
      <a href="status-pengajuan.php">Status Pengajuan</a> &rsaquo; Pilihan Pengajuan
    </div>
    <h1>Pilih Jenis Pengajuan</h1>
    <p class="sub">Pilih jenis layanan pengajuan publikasi yang ingin Anda ajukan ke CMI.</p>

    <!-- UI-02-03 | [CLS - IM-003] PilihanJenisPengajuan -->
    <div class="grid2">
      <!-- Pilihan 1: Publikasi Internal -->
      <div class="card opsi primary">
        <div class="top">
          <div class="ic">🏛️</div>
          <span class="tag">Pengaju Internal</span>
        </div>
        <h2>Request Publikasi Internal</h2>
        <p>Ajukan permohonan publikasi kegiatan atau materi promosi untuk kementerian/biro di lingkungan BEM FILKOM UB.
        </p>
        <a class="btn block btn-primary" id="btnPilihInternal" href="form-request.php">Pilih Internal &rarr;</a>
      </div>

      <!-- Pilihan 2: Media Partner (Eksternal) -->
      <div class="card opsi">
        <div class="top">
          <div class="ic">🤝</div>
          <span class="tag">Pengaju Eksternal</span>
        </div>
        <h2>Media Partner</h2>
        <p>Ajukan permohonan kerja sama publikasi atau kemitraan media bagi lembaga/organisasi di luar BEM FILKOM UB.
        </p>
        <a class="btn block btn-outline" id="btnPilihMedpart" href="#"
          onclick="alert('Alur Media Partner sedang dalam pengembangan. Silakan pilih Request Publikasi Internal.'); return false;">Pilih
          Media Partner</a>
      </div>
    </div>
  </div>

  <footer class="foot">&copy; 2026 Biro Center of Media and Information (CMI) &bull; BEM FILKOM Universitas Brawijaya.
  </footer>
  <script src="../../assets/js/pengaju.js"></script>
</body>

</html>