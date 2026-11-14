<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<entity>> BerkasLampiran (Package Pengajuan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

require_once __DIR__ . '/../config/database.php';

class BerkasLampiran
{
    private ?PDO $pdo;

    public string $idBerkas = '';
    public string $idPengajuan = '';
    public string $namaBerkas = '';
    public string $formatBerkas = '';
    public float $ukuranBerkas = 0.0;
    public string $kategoriBerkas = 'Referensi';
    public string $pathPenyimpanan = '';

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

    public function validasiBerkas(array $file, int $maxSizeMB = 10, array $allowedExt = ['jpg', 'jpeg', 'png', 'pdf']): array
    {
        if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'Berkas belum dipilih atau gagal diunggah.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            return ['valid' => false, 'message' => 'Format berkas tidak valid. Gunakan: ' . strtoupper(implode(', ', $allowedExt))];
        }

        $maxBytes = $maxSizeMB * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            return ['valid' => false, 'message' => "Ukuran berkas melebihi batas maksimal {$maxSizeMB} MB."];
        }

        return ['valid' => true, 'message' => 'Berkas valid.'];
    }

    public function simpanBerkas(array $data): bool
    {
        if (!$this->pdo)
            return false;
        $sql = "INSERT INTO BERKAS_LAMPIRAN (
                    idBerkas, idPengajuan, namaBerkas, formatBerkas, ukuranBerkas, kategoriBerkas, pathPenyimpanan
                ) VALUES (
                    :idBerkas, :idPengajuan, :namaBerkas, :formatBerkas, :ukuranBerkas, :kategoriBerkas, :pathPenyimpanan
                )";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idBerkas' => $data['idBerkas'] ?? ('BRK-' . uniqid()),
            ':idPengajuan' => $data['idPengajuan'],
            ':namaBerkas' => $data['namaBerkas'],
            ':formatBerkas' => $data['formatBerkas'],
            ':ukuranBerkas' => $data['ukuranBerkas'],
            ':kategoriBerkas' => $data['kategoriBerkas'] ?? 'Referensi',
            ':pathPenyimpanan' => $data['pathPenyimpanan']
        ]);
    }

    public function getByPengajuan(string $idPengajuan, ?string $kategori = null): array
    {
        if (!$this->pdo)
            return [];
        if ($kategori) {
            $stmt = $this->pdo->prepare("SELECT * FROM BERKAS_LAMPIRAN WHERE idPengajuan = :id AND kategoriBerkas = :kat");
            $stmt->execute([':id' => $idPengajuan, ':kat' => $kategori]);
        } else {
            $stmt = $this->pdo->prepare("SELECT * FROM BERKAS_LAMPIRAN WHERE idPengajuan = :id");
            $stmt->execute([':id' => $idPengajuan]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ambilBerkasDesain(string $idPengajuan): ?array
    {
        $list = $this->getByPengajuan($idPengajuan, 'HasilDesain');
        return count($list) > 0 ? end($list) : null;
    }
}