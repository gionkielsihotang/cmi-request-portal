<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<entity>> Notifikasi (Package Peninjauan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

require_once __DIR__ . '/../config/database.php';

class Notifikasi
{
    private ?PDO $pdo;

    public string $idNotifikasi = '';
    public string $idPengajuan = '';
    public string $idPengaju = '';
    public string $emailTujuan = '';
    public string $isiPesan = '';

    public function __construct(?PDO $pdo = null)
    {
        if ($pdo instanceof PDO) {
            $this->pdo = $pdo;
        } elseif (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
            $this->pdo = $GLOBALS['pdo'];
        } else {
            $this->pdo = null;
        }
    }

    public function kirimNotifikasiSistem(string $idPengajuan, string $idPengaju, string $emailTujuan, string $isiPesan): bool
    {
        if (!$this->pdo)
            return false;
        $sql = "INSERT INTO NOTIFIKASI (idNotifikasi, idPengajuan, idPengaju, emailTujuan, isiPesan)
                VALUES (:idNotif, :idPengajuan, :idPengaju, :emailTujuan, :isiPesan)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idNotif' => 'NTF-' . uniqid(),
            ':idPengajuan' => $idPengajuan,
            ':idPengaju' => $idPengaju,
            ':emailTujuan' => $emailTujuan,
            ':isiPesan' => $isiPesan
        ]);
    }

    public function kirimEmailPenolakan(string $idPengajuan, string $idPengaju, string $emailTujuan, string $alasanPenolakan): bool
    {
        $pesan = "Pengajuan publikasi ({$idPengajuan}) ditolak oleh CMI. Alasan: {$alasanPenolakan}";
        return $this->kirimNotifikasiSistem($idPengajuan, $idPengaju, $emailTujuan, $pesan);
    }
}