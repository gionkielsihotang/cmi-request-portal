-- ============================================================================
-- SUBBAB 4.1 DATA DEFINITION LANGUAGE (DDL)
-- Kode Tugas : DDL-TAB-01
-- Format     : [GYS] CREATE TABLE PENGAJUAN (Validasi ✓)
-- Penyusun   : Gion Yehezkiel Sihotang (255150401111041)
-- ============================================================================

CREATE TABLE PENGAJUAN(
    idPengajuan VARCHAR(20) NOT NULL PRIMARY KEY,
    idPengaju VARCHAR(20) NOT NULL,
    judulRequest VARCHAR(200) NOT NULL,
    tanggalPengajuan DATE NOT NULL,
    detailKonten TEXT NOT NULL,
    statusPengajuan VARCHAR(30) NOT NULL,
    catatanKelengkapan TEXT,
    alasanPenolakan TEXT,
    sisaWaktuVerifikasi INT,
    FOREIGN KEY (idPengaju) REFERENCES PENGAJU_INTERNAL(idPengaju));