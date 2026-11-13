<?php
/**
 * ENTITY CLASS - PACKAGE PENGAJUAN
 * Nama Kelas : BerkasLampiran
 * Acuan SDD  : Bab 3.4 & 3.5 Tabel BERKAS_LAMPIRAN
 */

require_once __DIR__ . '/../config/database.php';

class BerkasLampiran
{

    // Validasi ekstensi dan ukuran berkas maks 10 MB (PC-CRI-02)
    public static function validasiBerkas($berkas)
    {
        if (empty($berkas) || ($berkas['size'] ?? 0) === 0) {
            return true; // Opsional jika tidak ada file diunggah
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
        $maxSize = 10 * 1024 * 1024; // 10 MB
        $ext = strtolower(pathinfo($berkas['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt) || $berkas['size'] > $maxSize) {
            return false;
        }
        return true;
    }

    // Simpan berkas ke folder uploads dan catat ke database (PC-CPR-02)
    public static function simpanBerkas($idPengajuan, $berkas, $kategori = 'ReferensiInternal')
    {
        global $pdo;
        if (empty($berkas) || ($berkas['size'] ?? 0) === 0)
            return false;

        $idBerkas = 'BRK-' . uniqid();
        $namaAsli = basename($berkas['name']);
        $ext = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));
        $ukuranMB = round($berkas['size'] / (1024 * 1024), 2);
        $targetPath = 'assets/uploads/referensi/' . $idBerkas . '_' . $namaAsli;

        if (!empty($berkas['tmp_name'])) {
            move_uploaded_file($berkas['tmp_name'], __DIR__ . '/../' . $targetPath);
        }

        if (isset($pdo)) {
            $sql = "INSERT INTO BERKAS_LAMPIRAN 
                    (idBerkas, idPengajuan, namaBerkas, formatBerkas, ukuranBerkas, kategoriBerkas, pathPenyimpanan) 
                    VALUES (:idBerkas, :idPengajuan, :nama, :format, :ukuran, :kategori, :path)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':idBerkas' => $idBerkas,
                ':idPengajuan' => $idPengajuan,
                ':nama' => $namaAsli,
                ':format' => $ext,
                ':ukuran' => $ukuranMB,
                ':kategori' => $kategori,
                ':path' => $targetPath
            ]);
        }

        return $idBerkas;
    }
}
?>