-- ============================================================================
-- SUBBAB 4.1 DATA DEFINITION LANGUAGE (DDL)
-- Kode Tugas : DDL-TAB-08 (CATATAN_REVISI), DDL-TAB-09 (PENALTI_KEMENTERIAN)
-- Format     : [REV] CREATE TABLE CATATAN_REVISI & PENALTI_KEMENTERIAN (Validasi ✓)
-- Penyusun   : Revalia Rahmadani
-- ============================================================================

CREATE TABLE CATATAN_REVISI(
    idRevisi VARCHAR(20) NOT NULL PRIMARY KEY,
    idPengajuan VARCHAR(20) NOT NULL,
    teksRevisi TEXT NOT NULL,
    waktuRevisi DATETIME NOT NULL,
    FOREIGN KEY (idPengajuan) REFERENCES PENGAJUAN(idPengajuan));

CREATE TABLE PENALTI_KEMENTERIAN(
    idPenalti VARCHAR(20) NOT NULL PRIMARY KEY,
    idPengajuan VARCHAR(20) NOT NULL,
    poinPenalti INT NOT NULL,
    waktuPenalti DATETIME NOT NULL,
    idPengaju VARCHAR(20) NOT NULL,
    kementerianId VARCHAR(20) NOT NULL,
    FOREIGN KEY (idPengajuan) REFERENCES PENGAJUAN(idPengajuan),
    FOREIGN KEY (idPengaju) REFERENCES PENGAJU_INTERNAL(idPengaju),
    FOREIGN KEY (kementerianId) REFERENCES KEMENTERIAN(kementerianId));
