<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<entity>> CMI (Package Peninjauan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

require_once __DIR__ . '/../config/database.php';

class CMI
{
    private ?PDO $pdo;

    public string $idCMI = '';
    public string $namaAdmin = '';
    public string $accountAdmin = '';

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

    public function getById(string $idCMI): ?array
    {
        if (!$this->pdo)
            return null;
        $stmt = $this->pdo->prepare("SELECT * FROM CMI WHERE idCMI = :id LIMIT 1");
        $stmt->execute([':id' => $idCMI]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAll(): array
    {
        if (!$this->pdo)
            return [];
        $stmt = $this->pdo->query("SELECT * FROM CMI ORDER BY namaAdmin ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}