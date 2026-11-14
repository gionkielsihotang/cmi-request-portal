<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<entity>> PengajuInternal (Package Pengajuan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

require_once __DIR__ . '/../config/database.php';

class PengajuInternal
{
    private ?PDO $pdo;

    public string $idPengaju = '';
    public string $nim = '';
    public string $nama = '';
    public string $email = '';
    public string $asalKementerian = '';
    public int $totalPoinPenalti = 0;

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

    public function getById(string $idPengaju): ?array
    {
        if (!$this->pdo)
            return null;
        $sql = "SELECT p.*, k.asalKementerian 
                FROM PENGAJU_INTERNAL p 
                LEFT JOIN KEMENTERIAN k ON p.kementerianId = k.kementerianId 
                WHERE p.idPengaju = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $idPengaju]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getEmail(string $idPengaju): ?string
    {
        $data = $this->getById($idPengaju);
        return $data ? $data['email'] : null;
    }

    public function tambahPoinPenalti(string $idPengaju, int $poin = -5): bool
    {
        if (!$this->pdo)
            return false;
        $stmt = $this->pdo->prepare("UPDATE PENGAJU_INTERNAL SET totalPoinPenalti = totalPoinPenalti + :poin WHERE idPengaju = :id");
        return $stmt->execute([':poin' => $poin, ':id' => $idPengaju]);
    }
}