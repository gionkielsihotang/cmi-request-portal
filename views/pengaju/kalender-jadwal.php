<?php
require_once __DIR__ . '/../../config/koneksi.php';

// Inisialisasi Bulan dan Tahun yang Ditampilkan (Default November 2026 atau sesuai query param)
$bulan = isset($_GET['bulan']) ? (int) $_GET['bulan'] : (int) date('n');
$tahun = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');

if ($bulan < 1) {
    $bulan = 12;
    $tahun--;
} elseif ($bulan > 12) {
    $bulan = 1;
    $tahun++;
}

$namaBulan = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember'
];

$prevBulan = $bulan - 1;
$prevTahun = $tahun;
if ($prevBulan < 1) {
    $prevBulan = 12;
    $prevTahun--;
}

$nextBulan = $bulan + 1;
$nextTahun = $tahun;
if ($nextBulan > 12) {
    $nextBulan = 1;
    $nextTahun++;
}

// Tanggal awal dan akhir bulan yang dilihat
$startStr = sprintf('%04d-%02d-01', $tahun, $bulan);
$totalHari = (int) date('t', strtotime($startStr));
$endStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $totalHari);

// Hari pertama dalam minggu (1 = Senin, ..., 7 = Minggu)
$hariPertama = (int) date('N', strtotime($startStr));

// Tanggal hari ini (Asia/Jakarta)
$todayStr = date('Y-m-d');
$todayTs = strtotime($todayStr);

// 4 Slot Jam Tetap Sesuai SOP Publikasi CMI
$slotWaktu = ['09:00', '13:00', '16:00', '19:00'];

// Query SELECT slot yang sudah dipesan (status bukan 'Ditolak')
$bookedSlots = [];
if ($koneksi) {
    $sql = "
        SELECT idPengajuan, 
               COALESCE(tanggal_publikasi, targetTanggal) AS tglPublikasi, 
               COALESCE(jam_publikasi, targetJam) AS jamPublikasi, 
               judulRequest, 
               COALESCE(status_pengajuan, statusPengajuan) AS stPengajuan 
        FROM PENGAJUAN 
        WHERE (
            (tanggal_publikasi BETWEEN '$startStr' AND '$endStr') 
            OR (targetTanggal BETWEEN '$startStr' AND '$endStr')
        )
        AND (status_pengajuan != 'Ditolak' AND statusPengajuan != 'Ditolak')
    ";
    $res = mysqli_query($koneksi, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $tgl = $row['tglPublikasi'];
            $rawJam = $row['jamPublikasi'] ?? '09:00';
            $jam = substr($rawJam, 0, 5);
            $bookedSlots[$tgl . '_' . $jam] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalender Jadwal Publikasi — CMI BEM FILKOM UB</title>
    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link rel="stylesheet" href="../../assets/css/portal.css">
    <style>
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .calendar-nav {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .calendar-nav a {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .calendar-nav a:hover {
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        .legend-bar {
            display: flex;
            gap: 16px;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 12.5px;
            color: #1e3a8a;
            margin-bottom: 20px;
            flex-wrap: wrap;
            align-items: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }

        .indicator {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            display: inline-block;
        }

        .indicator.all-content {
            background: #16a34a;
        }

        .indicator.story-only {
            background: #d97706;
        }

        .indicator.closed {
            background: #94a3b8;
        }

        .indicator.sunday {
            background: #dc2626;
        }

        .indicator.booked {
            background: #ef4444;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-top: 10px;
        }

        .cal-day-name {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 6px;
            text-align: center;
            font-weight: 700;
            font-size: 12.5px;
            color: #475569;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cal-day-cell {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 8px;
            min-height: 155px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.2s ease;
        }

        .cal-day-cell.is-today {
            border: 2px solid #2563eb;
            background: #fbfdff;
        }

        .cal-day-cell.sunday-cell {
            background: #fff8f8;
            border-color: #fecaca;
        }

        .cal-day-cell.closed-cell {
            background: #f8fafc;
            border-color: #e2e8f0;
        }

        .cal-day-cell.story-cell {
            background: #fffdf5;
            border-color: #fef08a;
        }

        .cal-day-cell.all-cell {
            background: #ffffff;
            border-color: #bbf7d0;
        }

        .cal-day-cell.empty {
            background: #f8fafc;
            border-style: dashed;
            opacity: 0.5;
        }

        .cal-date-top {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 4px;
        }

        .cal-date-num {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }

        .date-sop-tag {
            font-size: 9.5px;
            font-weight: 700;
            padding: 3px 6px;
            border-radius: 4px;
            display: block;
            margin-bottom: 8px;
            line-height: 1.2;
            text-align: center;
        }

        .tag-libur {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .tag-closed {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }

        .tag-story {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .tag-all {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .slot-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 4px;
        }

        .slot-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 5px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid transparent;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }

        .slot-btn.available {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .slot-btn.available:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #1d4ed8;
            transform: translateY(-1px);
        }

        .slot-btn.booked {
            background: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
            cursor: not-allowed;
            opacity: 0.85;
        }

        .slot-btn.locked {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .slot-status-tag {
            font-size: 9.5px;
            font-weight: 800;
            text-transform: uppercase;
        }

        @media (max-width: 900px) {
            .calendar-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .cal-day-name {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .calendar-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="brand">
            <img src="../../assets/img/logo cmi.jpeg" alt="Logo CMI" class="logo">
            CMI BEM FILKOM UB
        </div>
        <div class="menu">
            <a href="status-pengajuan.php">Status Pengajuan</a>
            <a class="active" href="kalender-jadwal.php">Ajukan Request</a>
        </div>
        <div class="right">
            <span class="pill">Pengaju: <?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Pengaju Internal') ?></span>
            <a href="../../index.php" style="color: inherit; text-decoration: none;">Keluar</a>
        </div>
    </nav>

    <div class="wrap">
        <div class="crumb">
            <a href="status-pengajuan.php">Status Pengajuan</a> &rsaquo;
            Pilih Jadwal Kalender
        </div>

        <div class="card" style="padding: 28px 24px;">
            <div class="calendar-header">
                <div>
                    <h1 style="margin-bottom: 4px; font-size: 24px;">Pilih Jadwal Publikasi</h1>
                    <p class="sub">Pilih tanggal dan slot jam publikasi yang masih tersedia sesuai ketentuan SOP waktu
                        pengajuan CMI.</p>
                </div>
                <div class="calendar-nav">
                    <a href="kalender-jadwal.php?bulan=<?= $prevBulan ?>&tahun=<?= $prevTahun ?>">&larr; Bulan
                        Sebelumnya</a>
                    <span style="font-weight: 800; font-size: 15px; color: #1e3a8a; padding: 0 8px;">
                        <?= $namaBulan[$bulan] ?> <?= $tahun ?>
                    </span>
                    <a href="kalender-jadwal.php?bulan=<?= $nextBulan ?>&tahun=<?= $nextTahun ?>">Bulan Berikutnya
                        &rarr;</a>
                </div>
            </div>

            <!-- Petunjuk SOP & Legend Slot Jadwal -->
            <div class="legend-bar">
                <div class="legend-item">
                    <span class="indicator all-content"></span>
                    <span>&ge; H-7: Semua Konten (Feeds, Reels, Story)</span>
                </div>
                <div class="legend-item">
                    <span class="indicator story-only"></span>
                    <span>H-3 s.d. H-6: Khusus Story</span>
                </div>
                <div class="legend-item">
                    <span class="indicator closed"></span>
                    <span>&lt; H-3 / Lewat: Tertutup</span>
                </div>
                <div class="legend-item">
                    <span class="indicator sunday"></span>
                    <span>Minggu: Libur</span>
                </div>
                <div class="legend-item">
                    <span class="indicator booked"></span>
                    <span>Slot Penuh / Terisi</span>
                </div>
                <div style="margin-left: auto; font-size: 12px; color: #1e40af; font-weight:600;">
                    ⏱️ Slot: <strong>09:00</strong> &bull; <strong>13:00</strong> &bull; <strong>15:00</strong> &bull;
                    <strong>19:00 WIB</strong>
                </div>
            </div>

            <!-- Header Nama Hari (Senin s/d Minggu) -->
            <div class="calendar-grid" style="margin-bottom: 0;">
                <div class="cal-day-name">Senin</div>
                <div class="cal-day-name">Selasa</div>
                <div class="cal-day-name">Rabu</div>
                <div class="cal-day-name">Kamis</div>
                <div class="cal-day-name">Jumat</div>
                <div class="cal-day-name">Sabtu</div>
                <div class="cal-day-name" style="color: #dc2626;">Minggu</div>
            </div>

            <!-- Grid Tanggal & Slot Waktu -->
            <div class="calendar-grid">
                <?php
                // 1. Kotak kosong sebelum hari pertama (padding awal bulan)
                for ($blank = 1; $blank < $hariPertama; $blank++):
                    ?>
                    <div class="cal-day-cell empty"></div>
                <?php endfor; ?>

                <?php
                // 2. Loop setiap tanggal di bulan aktif
                for ($d = 1; $d <= $totalHari; $d++):
                    $currentDate = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                    $currentTs = strtotime($currentDate);
                    $diffDays = (int) round(($currentTs - $todayTs) / 86400);
                    $dayOfWeek = (int) date('w', $currentTs); // 0 = Minggu
                
                    $isMinggu = ($dayOfWeek === 0);
                    $isPastOrTooSoon = ($diffDays < 3); // < 3 hari dari hari ini termasuk tanggal yang sudah lewat
                    $isStoryOnly = ($diffDays >= 3 && $diffDays <= 6 && !$isMinggu);
                    $isAllContent = ($diffDays >= 7 && !$isMinggu);

                    $isDateLocked = ($isMinggu || $isPastOrTooSoon);

                    // Tentukan kelas CSS cell
                    $cellClass = 'cal-day-cell';
                    if ($currentDate === $todayStr)
                        $cellClass .= ' is-today';
                    if ($isMinggu)
                        $cellClass .= ' sunday-cell';
                    elseif ($isPastOrTooSoon)
                        $cellClass .= ' closed-cell';
                    elseif ($isStoryOnly)
                        $cellClass .= ' story-cell';
                    elseif ($isAllContent)
                        $cellClass .= ' all-cell';
                    ?>
                    <div class="<?= $cellClass ?>">
                        <div>
                            <div class="cal-date-top">
                                <span class="cal-date-num" style="<?= $isMinggu ? 'color:#dc2626;' : '' ?>"><?= $d ?></span>
                                <small
                                    style="font-size: 10px; font-weight: 600; color: #94a3b8;"><?= $namaBulan[$bulan] ?></small>
                            </div>

                            <!-- Label SOP Waktu Pengajuan -->
                            <?php if ($isMinggu): ?>
                                <span class="date-sop-tag tag-libur">Libur (Minggu)</span>
                            <?php elseif ($isPastOrTooSoon): ?>
                                <span class="date-sop-tag tag-closed">Tertutup (&lt; H-3)</span>
                            <?php elseif ($isStoryOnly): ?>
                                <span class="date-sop-tag tag-story">Khusus Story</span>
                            <?php elseif ($isAllContent): ?>
                                <span class="date-sop-tag tag-all">Semua Konten</span>
                            <?php endif; ?>
                        </div>

                        <!-- 4 Slot Jam Tetap (09:00, 13:00, 15:00, 19:00) -->
                        <div class="slot-list">
                            <?php foreach ($slotWaktu as $slot):
                                $slotKey = $currentDate . '_' . $slot;
                                $isBooked = isset($bookedSlots[$slotKey]);
                                ?>
                                <?php if ($isDateLocked): ?>
                                    <!-- Tanggal Terkunci (Minggu atau < H-3): Seluruh slot jam disabled -->
                                    <button type="button" class="slot-btn locked" disabled
                                        title="<?= $isMinggu ? 'Hari Minggu merupakan hari libur' : 'Batas pengajuan minimal H-3 sebelum publikasi' ?>">
                                        <span><?= $slot ?></span>
                                        <span class="slot-status-tag"><?= $isMinggu ? 'Libur' : 'Tutup' ?></span>
                                    </button>
                                <?php elseif ($isBooked): ?>
                                    <!-- Tanggal Aktif tetapi Slot Sudah Dipesan: Disabled (Terisi) -->
                                    <button type="button" class="slot-btn booked" disabled
                                        title="Slot jam <?= $slot ?> WIB sudah dipesan (<?= htmlspecialchars($bookedSlots[$slotKey]['judulRequest'] ?? 'Terisi') ?>)">
                                        <span><?= $slot ?></span>
                                        <span class="slot-status-tag">Terisi</span>
                                    </button>
                                <?php else: ?>
                                    <!-- Tanggal Aktif & Slot Tersedia: Arahkan ke form-request.php -->
                                    <a href="form-request.php?tanggal=<?= urlencode($currentDate) ?>&jam=<?= urlencode($slot) ?>"
                                        class="slot-btn available"
                                        title="Pilih slot <?= $slot ?> WIB pada <?= $currentDate ?> (<?= $isStoryOnly ? 'Khusus Story' : 'Semua Konten' ?>)">
                                        <span><?= $slot ?></span>
                                        <span class="slot-status-tag">Pilih &rarr;</span>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endfor; ?>

                <?php
                // 3. Kotak kosong setelah hari terakhir (padding akhir bulan agar genap 7 kolom)
                $totalCells = ($hariPertama - 1) + $totalHari;
                $rem = $totalCells % 7;
                if ($rem !== 0) {
                    $padEnd = 7 - $rem;
                    for ($p = 0; $p < $padEnd; $p++):
                        ?>
                        <div class="cal-day-cell empty"></div>
                        <?php
                    endfor;
                }
                ?>
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center;">
                <a href="status-pengajuan.php" class="btn" style="text-decoration: none;">&larr; Kembali ke Status
                    Pengajuan</a>
            </div>
        </div>
    </div>

    <footer class="foot">&copy; 2026 Biro Center of Media and Information (CMI) &bull; BEM FILKOM Universitas Brawijaya.
    </footer>
</body>

</html>