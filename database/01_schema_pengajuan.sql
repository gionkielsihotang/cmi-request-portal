-- ============================================================================
-- SUBBAB 4.1 DATA DEFINITION LANGUAGE (DDL)
-- Kode Tugas : DDL-TAB-01
-- Format     : [GYS] CREATE TABLE PENGAJUAN (Validasi ✓)
-- Penyusun   : Gion Yehezkiel Sihotang (255150401111041)
-- ============================================================================

CREATE TABLE PENGAJUAN (
    idPengajuan VARCHAR(20) NOT NULL,
    idPengaju VARCHAR(20) NOT NULL,
    judulRequest VARCHAR(200) NOT NULL,
    tanggalPengajuan DATE NOT NULL,
    detailKonten TEXT NOT NULL,
    statusPengajuan VARCHAR(30) NOT NULL DEFAULT 'Dalam Antrian',
    catatanKelengkapan TEXT NULL,
    alasanPenolakan TEXT NULL,
    sisaWaktuVerifikasi INT DEFAULT 3600,
    PRIMARY KEY (idPengajuan),
    CONSTRAINT fk_pengajuan_pengaju
        FOREIGN KEY (idPengaju) REFERENCES PENGAJU_INTERNAL(idPengaju)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;