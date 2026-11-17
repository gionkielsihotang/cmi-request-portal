-- ============================================================================
-- SUBBAB 4.1 DATA DEFINITION LANGUAGE (DDL)
-- Kode Tugas : DDL-TAB-03 (PENGAJU_INTERNAL)
-- Format     : [REV] CREATE TABLE PENGAJU_INTERNAL (Validasi ✓)
-- Penyusun   : Revalia Rahmadani
-- ============================================================================

CREATE TABLE PENGAJU_INTERNAL(
    idPengaju VARCHAR(20) NOT NULL PRIMARY KEY,
    kementerianId VARCHAR(20) NOT NULL,
    nim VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    totalPoinPenalti INT NOT NULL DEFAULT 0,
    FOREIGN KEY (kementerianId) REFERENCES KEMENTERIAN(kementerianId));