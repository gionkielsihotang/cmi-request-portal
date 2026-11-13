<?php
/**
 * ENTITY CLASS - PACKAGE PENGAJUAN
 * Nama Kelas : Pengajuan
 * Acuan SDD  : Bab 3.4 & 3.5 Tabel PENGAJUAN
 */

require_once __DIR__ . '/../config/database.php';

class Pengajuan
{

    // Validasi kolom wajib form request internal (PC-CRI-02)
    public static function cekKelengkapanForm($dataForm)
    {
        if (
            empty(trim($dataForm['judulRequest'] ?? '')) ||
            empty(trim($dataForm['kementerian'] ?? '')) ||
            empty(trim($dataForm['jenisPublikasi'] ?? '')) ||
            empty(trim($dataForm['tanggalPublikasi'] ?? '')) ||
            empty(trim($dataForm['detailKonten'] ?? ''))
        ) {
            return false;
        }
        return true;
    }

    // Simpan permohonan baru ke tabel PENGAJUAN (PC-CPR-02)
    public static function simpanPengajuan($dataForm, $status = 'Dalam Antrian')
    {
        global $pdo;
        $idPengajuan = 'INT-' . date('YmdHis');

        if (isset($pdo)) {
            $sql = "INSERT INTO PENGAJUAN 
                    (idPengajuan, idPengaju, judulRequest, jenisPublikasi, tanggalPublikasi, tanggalPengajuan, detailKonten, statusPengajuan) 
                    VALUES (:id, :idPengaju, :judul, :jenis, :tglPub, CURDATE(), :konten, :status)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $idPengajuan,
                ':idPengaju' => $dataForm['idPengaju'] ?? 'PGJ-001',
                ':judul' => $dataForm['judulRequest'],
                ':jenis' => $dataForm['jenisPublikasi'],
                ':tglPub' => $dataForm['tanggalPublikasi'],
                ':konten' => $dataForm['detailKonten'],
                ':status' => $status
            ]);
        }

        return $idPengajuan;
    }

    // Ambil semua pengajuan milik mahasiswa tertentu (PC-CSP-01)
    public static function getByPengaju($idPengaju)
    {
        global $pdo;
        if (!isset($pdo))
            return [];

        $stmt = $pdo->prepare("SELECT * FROM PENGAJUAN WHERE idPengaju = :id ORDER BY tanggalPengajuan DESC");
        $stmt->execute([':id' => $idPengaju]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil detail 1 pengajuan spesifik (PC-CSP-02)
    public static function getById($idPengajuan)
    {
        global $pdo;
        if (!isset($pdo))
            return null;

        $stmt = $pdo->prepare("SELECT * FROM PENGAJUAN WHERE idPengajuan = :id");
        $stmt->execute([':id' => $idPengajuan]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>