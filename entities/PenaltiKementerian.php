<?php
// gion

require_once __DIR__ . '/../config/database.php';

class PenaltiKementerian
{
    private ?PDO $pdo;

    public string $idPenalti = '';
    public string $kementerianId = '';
    public string $idPengajuan = '';
    public string $idPengaju = '';
    public int $poinPenalti = -5;
    public string $waktuPenalti = '';

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

    public function catatPenguranganPoin(string $kementerianId, string $idPengajuan, int $poin = -5, ?string $idPengaju = null): bool
    {
        if (!$this->pdo)
            return false;

        if (!$idPengaju) {
            $q = $this->pdo->prepare("SELECT idPengaju FROM PENGAJUAN WHERE idPengajuan = :id LIMIT 1");
            $q->execute([':id' => $idPengajuan]);
            $idPengaju = (string) ($q->fetchColumn() ?: 'PGJ-001');
        }

        $sql = "INSERT INTO PENALTI_KEMENTERIAN (idPenalti, idPengajuan, idPengaju, kementerianId, poinPenalti, waktuPenalti)
                VALUES (:idPenalti, :idPengajuan, :idPengaju, :kementerianId, :poinPenalti, :waktuPenalti)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idPenalti' => 'PNL-' . uniqid(),
            ':idPengajuan' => $idPengajuan,
            ':idPengaju' => $idPengaju,
            ':kementerianId' => $kementerianId,
            ':poinPenalti' => $poin,
            ':waktuPenalti' => date('Y-m-d H:i:s')
        ]);
    }
}