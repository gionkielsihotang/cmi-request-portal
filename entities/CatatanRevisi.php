<?php
// gion
require_once __DIR__ . '/../config/database.php';

class CatatanRevisi
{
    private ?PDO $pdo;

    public string $idRevisi = '';
    public string $idPengajuan = '';
    public string $teksRevisi = '';
    public string $waktuRevisi = '';

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

    public function simpanCatatanRevisi(string $idPengajuan, string $teksRevisi, ?string $waktuRevisi = null): bool
    {
        if (!$this->pdo)
            return false;
        $sql = "INSERT INTO CATATAN_REVISI (idRevisi, idPengajuan, teksRevisi, waktuRevisi)
                VALUES (:idRevisi, :idPengajuan, :teksRevisi, :waktuRevisi)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idRevisi' => 'REV-' . uniqid(),
            ':idPengajuan' => $idPengajuan,
            ':teksRevisi' => $teksRevisi,
            ':waktuRevisi' => $waktuRevisi ?? date('Y-m-d H:i:s')
        ]);
    }

    public function getByPengajuan(string $idPengajuan): array
    {
        if (!$this->pdo)
            return [];
        $stmt = $this->pdo->prepare("SELECT * FROM CATATAN_REVISI WHERE idPengajuan = :id ORDER BY waktuRevisi DESC");
        $stmt->execute([':id' => $idPengajuan]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}