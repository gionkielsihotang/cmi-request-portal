-- ============================================================================
-- DATABASE SCHEMA & SEED DATA: CMI REQUEST PORTAL BEM FILKOM UB
-- File        : database/schema_dan_seed.sql
-- Keterangan  : DDL Struktur Tabel Relasional & Seed Data Dummy Awal
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `cmi_portal` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `cmi_portal`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `NOTIFIKASI`;
DROP TABLE IF EXISTS `RIWAYATberkas_lampirancatatan_revisi_LOG`;
DROP TABLE IF EXISTS `PENALTI_KEMENTERIAN`;
DROP TABLE IF EXISTS `CATATAN_REVISI`;
DROP TABLE IF EXISTS `BERKAS_LAMPIRAN`;
DROP TABLE IF EXISTS `PENGAJUAN`;
DROP TABLE IF EXISTS `CMI`;
DROP TABLE IF EXISTS `PENGAJU_INTERNAL`;
DROP TABLE IF EXISTS `KEMENTERIAN`;
SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. TABEL MASTER KEMENTERIAN (DDL-TAB-04)
-- ----------------------------------------------------------------------------
CREATE TABLE `KEMENTERIAN` (
    `kementerianId` VARCHAR(20) NOT NULL,
    `asalKementerian` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`kementerianId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 2. TABEL PENGAJU INTERNAL / MAHASISWA (DDL-TAB-03)
-- ----------------------------------------------------------------------------
CREATE TABLE `PENGAJU_INTERNAL` (
    `idPengaju` VARCHAR(20) NOT NULL,
    `kementerianId` VARCHAR(20) NOT NULL,
    `nim` VARCHAR(20) NOT NULL UNIQUE,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `totalPoinPenalti` INT NOT NULL DEFAULT 0,
    PRIMARY KEY (`idPengaju`),
    CONSTRAINT `fk_pengaju_kementerian` FOREIGN KEY (`kementerianId`) REFERENCES `KEMENTERIAN` (`kementerianId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 3. TABEL CMI / ADMIN STAF (DDL-TAB-07)
-- ----------------------------------------------------------------------------
CREATE TABLE `CMI` (
    `idCMI` VARCHAR(20) NOT NULL,
    `namaAdmin` VARCHAR(100) NOT NULL,
    `accountAdmin` VARCHAR(100) NOT NULL UNIQUE,
    PRIMARY KEY (`idCMI`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 4. TABEL PENGAJUAN PUBLIKASI (DDL-TAB-01)
-- ----------------------------------------------------------------------------
CREATE TABLE `PENGAJUAN` (
    `idPengajuan` VARCHAR(30) NOT NULL,
    `idPengaju` VARCHAR(20) NOT NULL,
    `kementerianId` VARCHAR(20) NULL,
    `judulRequest` VARCHAR(200) NOT NULL,
    `tanggalPengajuan` DATE NOT NULL,
    `tanggal_publikasi` DATE NULL,
    `jam_publikasi` VARCHAR(10) NULL DEFAULT '09:00',
    `jenis_konten` VARCHAR(50) DEFAULT 'Feeds',
    `targetTanggal` DATE NULL,
    `targetJam` VARCHAR(10) NULL DEFAULT '09:00',
    `jenisMedia` VARCHAR(50) DEFAULT 'Feeds',
    `detailKonten` TEXT NOT NULL,
    `statusPengajuan` VARCHAR(40) NOT NULL DEFAULT 'Dalam Antrian',
    `status_pengajuan` VARCHAR(40) NOT NULL DEFAULT 'Dalam Antrian',
    `catatanKelengkapan` TEXT NULL,
    `alasanPenolakan` TEXT NULL,
    `sisaWaktuVerifikasi` INT NOT NULL DEFAULT 3600,
    PRIMARY KEY (`idPengajuan`),
    CONSTRAINT `fk_pengajuan_pengaju` FOREIGN KEY (`idPengaju`) REFERENCES `PENGAJU_INTERNAL` (`idPengaju`) ON DELETE CASCADE,
    CONSTRAINT `fk_pengajuan_kementerian` FOREIGN KEY (`kementerianId`) REFERENCES `KEMENTERIAN` (`kementerianId`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 5. TABEL BERKAS LAMPIRAN & HASIL DESAIN (DDL-TAB-02)
-- ----------------------------------------------------------------------------
CREATE TABLE `BERKAS_LAMPIRAN` (
    `idBerkas` VARCHAR(30) NOT NULL,
    `idPengajuan` VARCHAR(30) NOT NULL,
    `namaBerkas` VARCHAR(150) NOT NULL,
    `formatBerkas` VARCHAR(10) NOT NULL,
    `ukuranBerkas` DOUBLE NOT NULL,
    `kategoriBerkas` VARCHAR(50) NOT NULL DEFAULT 'Referensi',
    `pathPenyimpanan` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`idBerkas`),
    CONSTRAINT `fk_berkas_pengajuan` FOREIGN KEY (`idPengajuan`) REFERENCES `PENGAJUAN` (`idPengajuan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 6. TABEL CATATAN REVISI DESAIN (DDL-TAB-08)
-- ----------------------------------------------------------------------------
CREATE TABLE `CATATAN_REVISI` (
    `idRevisi` VARCHAR(30) NOT NULL,
    `idPengajuan` VARCHAR(30) NOT NULL,
    `teksRevisi` TEXT NOT NULL,
    `waktuRevisi` DATETIME NOT NULL,
    PRIMARY KEY (`idRevisi`),
    CONSTRAINT `fk_revisi_pengajuan` FOREIGN KEY (`idPengajuan`) REFERENCES `PENGAJUAN` (`idPengajuan`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 7. TABEL PENALTI KEMENTERIAN (DDL-TAB-09)
-- ----------------------------------------------------------------------------
CREATE TABLE `PENALTI_KEMENTERIAN` (
    `idPenalti` VARCHAR(30) NOT NULL,
    `idPengajuan` VARCHAR(30) NOT NULL,
    `poinPenalti` INT NOT NULL DEFAULT 5,
    `waktuPenalti` DATETIME NOT NULL,
    `idPengaju` VARCHAR(20) NOT NULL,
    `kementerianId` VARCHAR(20) NOT NULL,
    PRIMARY KEY (`idPenalti`),
    CONSTRAINT `fk_penalti_pengajuan` FOREIGN KEY (`idPengajuan`) REFERENCES `PENGAJUAN` (`idPengajuan`) ON DELETE CASCADE,
    CONSTRAINT `fk_penalti_pengaju` FOREIGN KEY (`idPengaju`) REFERENCES `PENGAJU_INTERNAL` (`idPengaju`) ON DELETE CASCADE,
    CONSTRAINT `fk_penalti_kementerian` FOREIGN KEY (`kementerianId`) REFERENCES `KEMENTERIAN` (`kementerianId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 8. TABEL RIWAYAT LOG AUDIT (DDL-TAB-05)
-- ----------------------------------------------------------------------------
CREATE TABLE `RIWAYAT_LOG` (
    `idLog` VARCHAR(30) NOT NULL,
    `idPengajuan` VARCHAR(30) NOT NULL,
    `idCMI` VARCHAR(20) NOT NULL,
    `aksi` VARCHAR(100) NOT NULL,
    `waktu` DATETIME NOT NULL,
    PRIMARY KEY (`idLog`),
    CONSTRAINT `fk_log_pengajuan` FOREIGN KEY (`idPengajuan`) REFERENCES `PENGAJUAN` (`idPengajuan`) ON DELETE CASCADE,
    CONSTRAINT `fk_log_cmi` FOREIGN KEY (`idCMI`) REFERENCES `CMI` (`idCMI`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 9. TABEL NOTIFIKASI SISTEM (DDL-TAB-06)
-- ----------------------------------------------------------------------------
CREATE TABLE `NOTIFIKASI` (
    `idNotifikasi` VARCHAR(30) NOT NULL,
    `idPengajuan` VARCHAR(30) NOT NULL,
    `idPengaju` VARCHAR(20) NOT NULL,
    `emailTujuan` VARCHAR(100) NOT NULL,
    `isiPesan` TEXT NOT NULL,
    PRIMARY KEY (`idNotifikasi`),
    CONSTRAINT `fk_notif_pengajuan` FOREIGN KEY (`idPengajuan`) REFERENCES `PENGAJUAN` (`idPengajuan`) ON DELETE CASCADE,
    CONSTRAINT `fk_notif_pengaju` FOREIGN KEY (`idPengaju`) REFERENCES `PENGAJU_INTERNAL` (`idPengaju`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ============================================================================
-- SEED DATA DUMMY AWAL
-- ============================================================================

-- 1. Data Kementerian / Biro Pengaju BEM FILKOM UB
INSERT INTO `KEMENTERIAN` (`kementerianId`, `asalKementerian`) VALUES
('KEM-01', 'Kementerian Human Capital'),
('KEM-02', 'Kementerian Social Equity Environtment'),
('KEM-03', 'Kementerian Inter Agency AFF'),
('KEM-04', 'Kementerian IT Solution'),
('KEM-05', 'Kementerian Administration and Finance'),
('KEM-06', 'Kementerian Talent Growth'),
('KEM-07', 'Kementerian Creative Enterprise'),
('KEM-08', 'Kementerian Student and Strategic Action'),
('KEM-09', 'Kementerian Student Advokasi and Walfare'),
('KEM-10', 'Kementerian Creative Media Information');

-- 2. Data Akun Demo Pengaju Internal & Staf Admin CMI
INSERT INTO `PENGAJU_INTERNAL` (`idPengaju`, `kementerianId`, `nim`, `nama`, `email`, `totalPoinPenalti`) VALUES
('PGJ-001', 'KEM-01', '245150201111001', 'Gion Yehezkiel Sihotang', 'gion@student.ub.ac.id', 0),
('PGJ-002', 'KEM-04', '245150201111002', 'Revalia Rahmadani', 'revalia@student.ub.ac.id', 0);

INSERT INTO `CMI` (`idCMI`, `namaAdmin`, `accountAdmin`) VALUES
('CMI-001', 'Adinda Staf CMI', 'cmi_admin@filkom.ub.ac.id'),
('CMI-002', 'Callysta Desainer CMI', 'callysta_cmi@filkom.ub.ac.id');

-- 3. Data Pengajuan Publikasi Berbagai Status
INSERT INTO `PENGAJUAN` (`idPengajuan`, `idPengaju`, `kementerianId`, `judulRequest`, `tanggalPengajuan`, `tanggal_publikasi`, `jam_publikasi`, `jenis_konten`, `targetTanggal`, `targetJam`, `jenisMedia`, `detailKonten`, `statusPengajuan`, `status_pengajuan`, `catatanKelengkapan`, `alasanPenolakan`, `sisaWaktuVerifikasi`) VALUES
('REQ-001', 'PGJ-001', 'KEM-01', 'Open Recruitment Staff Ahli BEM FILKOM UB 2026', '2026-11-20', '2026-11-25', '09:00', 'Feeds', '2026-11-25', '09:00', 'Feeds', 'Open Recruitment Staff Ahli BEM FILKOM UB 2026 telah resmi dibuka. Pendaftaran berlangsung pada 25–30 November 2026 melalui tautan resmi BEM FILKOM UB.', 'Menunggu Peninjauan', 'Menunggu Peninjauan', NULL, NULL, 3600),
('REQ-002', 'PGJ-001', 'KEM-01', 'Publikasi Webinar Nasional FILKOM 2026', '2026-11-18', '2026-11-21', '13:00', 'Story', '2026-11-21', '13:00', 'Story', 'Webinar Nasional Masa Depan AI dan Rekayasa Perangkat Lunak bersama pembicara industri.', 'Menunggu Verifikasi', 'Menunggu Verifikasi', 'Materi konten dan bukti H-3 sudah lengkap dan terverifikasi.', NULL, 3550),
('REQ-003', 'PGJ-001', 'KEM-01', 'Workshop Desain Grafis & Multimedia CMI', '2026-11-15', '2026-11-26', '15:00', 'Reels', '2026-11-26', '15:00', 'Reels', 'Pelatihan pembuatan konten visual kreatif dan video interaktif bagi pengurus himpunan.', 'Diproses', 'Diproses', 'Materi sesuai SOP, sedang dalam pengerjaan tim desain CMI.', NULL, 3600),
('REQ-004', 'PGJ-001', 'KEM-01', 'Lomba Karya Tulis Mahasiswa Nasional', '2026-11-14', '2026-11-16', '19:00', 'Feeds', '2026-11-16', '19:00', 'Feeds', 'Publikasi kompetisi karya ilmiah tingkat nasional mahasiswa se-Indonesia.', 'Ditolak', 'Ditolak', NULL, 'Format berkas acuan tidak jelas dan jadwal publikasi melanggar batas waktu H-3 SOP CMI.', 3600),
('REQ-005', 'PGJ-001', 'KEM-01', 'Sosialisasi Beasiswa Filkom 2026', '2026-11-10', '2026-11-28', '09:00', 'Story', '2026-11-28', '09:00', 'Story', 'Pengumuman pembukaan beasiswa prestasi dan bantuan pendidikan semester genap.', 'Fix', 'Fix', 'Disetujui oleh kementerian pengaju.', NULL, 0);

-- 4. Data Berkas Lampiran Referensi & Hasil Desain
INSERT INTO `BERKAS_LAMPIRAN` (`idBerkas`, `idPengajuan`, `namaBerkas`, `formatBerkas`, `ukuranBerkas`, `kategoriBerkas`, `pathPenyimpanan`) VALUES
('BRK-001', 'REQ-001', 'acuan-oprec-bem2026.pdf', 'pdf', 2.4, 'Referensi', 'assets/uploads/referensi/acuan-oprec-bem2026.pdf'),
('BRK-002', 'REQ-002', 'poster-webinar-referensi.png', 'png', 1.8, 'Referensi', 'assets/uploads/referensi/poster-webinar-referensi.png'),
('BRK-003', 'REQ-002', 'hasil-desain-webinar-v1.jpg', 'jpg', 2.1, 'HasilDesain', 'assets/uploads/hasil_desain/hasil-desain-webinar-v1.jpg'),
('BRK-004', 'REQ-003', 'storyboard-reels.pdf', 'pdf', 3.0, 'Referensi', 'assets/uploads/referensi/storyboard-reels.pdf');

-- 5. Data Catatan Revisi Contoh
INSERT INTO `CATATAN_REVISI` (`idRevisi`, `idPengajuan`, `teksRevisi`, `waktuRevisi`) VALUES
('REV-001', 'REQ-002', 'Tolong perbesar ukuran font judul webinar dan naikkan kontras warna background di bagian bawah.', '2026-11-18 10:30:00');

-- 6. Data Riwayat Log Audit
INSERT INTO `RIWAYAT_LOG` (`idLog`, `idPengajuan`, `idCMI`, `aksi`, `waktu`) VALUES
('LOG-001', 'REQ-003', 'CMI-001', 'Memproses Request', '2026-11-15 09:15:00'),
('LOG-002', 'REQ-004', 'CMI-001', 'Menolak Request', '2026-11-14 14:20:00');

