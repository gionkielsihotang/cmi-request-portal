<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<entity>> Pengajuan (Package Pengajuan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

require_once __DIR__ . '/../config/database.php';

class Pengajuan
{
    private ?PDO $pdo;

    public string $idPengajuan = '';
    public string $idPengaju = '';
    public string $judulRequest = '';
    public string $tanggalPengajuan = '';
    public string $detailKonten = '';
    public string $statusPengajuan = 'Dalam Antrian';
    public ?string $catatanKelengkapan = null;
    public ?string $alasanPenolakan = null;
    public int $sisaWaktuVerifikasi = 3600;

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

    public function getByPengaju(string $idPengaju): array
    {
        if (!$this->pdo)
            return [];
        $stmt = $this->pdo->prepare("SELECT * FROM PENGAJUAN WHERE idPengaju = :idPengaju ORDER BY tanggalPengajuan DESC");
        $stmt->execute([':idPengaju' => $idPengaju]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(string $idPengajuan): ?array
    {
        if (!$this->pdo)
            return null;
        $stmt = $this->pdo->prepare("SELECT * FROM PENGAJUAN WHERE idPengajuan = :idPengajuan LIMIT 1");
        $stmt->execute([':idPengajuan' => $idPengajuan]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getSemuaPengajuan(?string $statusFilter = null): array
    {
        if (!$this->pdo)
            return [];
        if ($statusFilter && $statusFilter !== 'Semua') {
            $stmt = $this->pdo->prepare("SELECT * FROM PENGAJUAN WHERE statusPengajuan = :status ORDER BY tanggalPengajuan DESC");
            $stmt->execute([':status' => $statusFilter]);
        } else {
            $stmt = $this->pdo->query("SELECT * FROM PENGAJUAN ORDER BY tanggalPengajuan DESC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cekKelengkapanForm(array $dataForm): array
    {
        $errors = [];
        $wajib = ['judulRequest', 'tanggalPengajuan', 'detailKonten'];
        foreach ($wajib as $field) {
            if (empty(trim((string) ($dataForm[$field] ?? '')))) {
                $errors[] = $field;
            }
        }
        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }

    public function simpanPengajuan(array $data): bool
    {
        if (!$this->pdo)
            return false;
        $sql = "INSERT INTO PENGAJUAN (
                    idPengajuan, idPengaju, judulRequest, tanggalPengajuan,
                    detailKonten, statusPengajuan, catatanKelengkapan, alasanPenolakan, sisaWaktuVerifikasi
                ) VALUES (
                    :idPengajuan, :idPengaju, :judulRequest, :tanggalPengajuan,
                    :detailKonten, :statusPengajuan, :catatanKelengkapan, :alasanPenolakan, :sisaWaktuVerifikasi
                )";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idPengajuan' => $data['idPengajuan'] ?? ('REQ-' . date('YmdHis')),
            ':idPengaju' => $data['idPengaju'] ?? 'PGJ-001',
            ':judulRequest' => $data['judulRequest'],
            ':tanggalPengajuan' => $data['tanggalPengajuan'] ?? date('Y-m-d'),
            ':detailKonten' => $data['detailKonten'],
            ':statusPengajuan' => $data['statusPengajuan'] ?? 'Dalam Antrian',
            ':catatanKelengkapan' => $data['catatanKelengkapan'] ?? null,
            ':alasanPenolakan' => $data['alasanPenolakan'] ?? null,
            ':sisaWaktuVerifikasi' => $data['sisaWaktuVerifikasi'] ?? 3600
        ]);
    }

    public function updateStatus(string $idPengajuan, string $statusBaru): bool
    {
        if (!$this->pdo)
            return false;
        $stmt = $this->pdo->prepare("UPDATE PENGAJUAN SET statusPengajuan = :status WHERE idPengajuan = :id");
        return $stmt->execute([':status' => $statusBaru, ':id' => $idPengajuan]);
    }

    public function ambilTimerVerifikasi(string $idPengajuan): int
    {
        $data = $this->getById($idPengajuan);
        return $data ? (int) $data['sisaWaktuVerifikasi'] : 0;
    }

    public function mulaiTimerVerifikasi(string $idPengajuan, int $durasi = 3600): bool
    {
        if (!$this->pdo)
            return false;
        $stmt = $this->pdo->prepare("UPDATE PENGAJUAN SET sisaWaktuVerifikasi = :durasi WHERE idPengajuan = :id");
        return $stmt->execute([':durasi' => $durasi, ':id' => $idPengajuan]);
    }

    public function cekBatasWaktuVerifikasi(string $idPengajuan, ?int $sisaWaktu = null): bool
    {
        $waktu = $sisaWaktu ?? $this->ambilTimerVerifikasi($idPengajuan);
        return $waktu > 0;
    }

    public function autoApproveStatus(string $idPengajuan): bool
    {
        if (!$this->pdo)
            return false;
        $stmt = $this->pdo->prepare("UPDATE PENGAJUAN SET statusPengajuan = 'Fix', sisaWaktuVerifikasi = 0 WHERE idPengajuan = :id");
        return $stmt->execute([':id' => $idPengajuan]);
    }

    public function updateCatatanKelengkapan(string $idPengajuan, string $catatan): bool
    {
        if (!$this->pdo)
            return false;
        $stmt = $this->pdo->prepare("UPDATE PENGAJUAN SET catatanKelengkapan = :catatan WHERE idPengajuan = :id");
        return $stmt->execute([':catatan' => $catatan, ':id' => $idPengajuan]);
    }

    public function simpanPenolakan(string $idPengajuan, string $alasan): bool
    {
        if (!$this->pdo)
            return false;
        $stmt = $this->pdo->prepare("UPDATE PENGAJUAN SET statusPengajuan = 'Ditolak', alasanPenolakan = :alasan WHERE idPengajuan = :id");
        return $stmt->execute([':alasan' => $alasan, ':id' => $idPengajuan]);
    }
}