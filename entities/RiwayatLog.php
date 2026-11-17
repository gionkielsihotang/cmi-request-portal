<?php
// gion

require_once __DIR__ . '/../config/database.php';

class RiwayatLog
{
    private ?PDO $pdo;

    public string $idLog = '';
    public string $idPengajuan = '';
    public string $idCMI = '';
    public string $aksi = '';
    public string $waktu = '';

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

    public function simpanRiwayat(string $idPengajuan, string $idCMI, string $aksi, ?string $waktu = null): bool
    {
        if (!$this->pdo)
            return false;
        $sql = "INSERT INTO RIWAYAT_LOG (idLog, idPengajuan, idCMI, aksi, waktu)
                VALUES (:idLog, :idPengajuan, :idCMI, :aksi, :waktu)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idLog' => 'LOG-' . uniqid(),
            ':idPengajuan' => $idPengajuan,
            ':idCMI' => $idCMI,
            ':aksi' => $aksi,
            ':waktu' => $waktu ?? date('Y-m-d H:i:s')
        ]);
    }

    public function getByPengajuan(string $idPengajuan): array
    {
        if (!$this->pdo)
            return [];
        $stmt = $this->pdo->prepare("SELECT * FROM RIWAYAT_LOG WHERE idPengajuan = :id ORDER BY waktu DESC");
        $stmt->execute([':id' => $idPengajuan]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}