-- ============================================================================
-- SUBBAB 4.1 DATA DEFINITION LANGUAGE (DDL)
-- Kode Tugas : DDL-TAB-02
-- Format     : [CF] CREATE TABLE BERKAS LAMPIRAN (Validasi ✓)
-- Penyusun   : Callysta Fredelina (255150407111080)
-- ============================================================================

CREATE TABLE BERKAS_LAMPIRAN(
    idBerkas VARCHAR(20) NOT NULL PRIMARY KEY,
    idPengajuan VARCHAR(20) NOT NULL,
    namaBerkas VARCHAR(150) NOT NULL,
    formatBerkas VARCHAR(10) NOT NULL,
    ukuranBerkas DOUBLE NOT NULL,
    kategoriBerkas VARCHAR(50) NOT NULL,
    pathPenyimpanan VARCHAR(255) NOT NULL,
    FOREIGN KEY (idPengajuan) REFERENCES PENGAJUAN(idPengajuan));
