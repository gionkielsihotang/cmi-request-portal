<?php
// gion

require_once __DIR__ . '/../config/database.php';

class Pengajuan
{
    private ?PDO $pdo;

    public string $idPengajuan = '';
    public string $idPengaju = '';
    public string $judulRequest = '';
    public string $tanggalPengajuan = '';
    public ?string $targetTanggal = null;
    public ?string $targetJam = '09:00';
    public ?string $tanggal_publikasi = null;
    public ?string $jam_publikasi = '09:00';
    public ?string $jenis_konten = 'Feeds';
    public string $detailKonten = '';
    public string $statusPengajuan = 'Dalam Antrian';
    public ?string $status_pengajuan = 'Dalam Antrian';
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
        $tglPub = $data['tanggal_publikasi'] ?? ($data['targetTanggal'] ?? ($data['tanggalPengajuan'] ?? date('Y-m-d')));
        $jamPub = $data['jam_publikasi'] ?? ($data['targetJam'] ?? '09:00');
        $jnsKon = $data['jenis_konten'] ?? ($data['jenisMedia'] ?? 'Feeds');
        $st = $data['status_pengajuan'] ?? ($data['statusPengajuan'] ?? 'Dalam Antrian');

        $sql = "INSERT INTO PENGAJUAN (
                    idPengajuan, idPengaju, judulRequest, tanggalPengajuan,
                    tanggal_publikasi, jam_publikasi, jenis_konten,
                    targetTanggal, targetJam, jenisMedia,
                    detailKonten, statusPengajuan, status_pengajuan,
                    catatanKelengkapan, alasanPenolakan, sisaWaktuVerifikasi
                ) VALUES (
                    :idPengajuan, :idPengaju, :judulRequest, :tanggalPengajuan,
                    :tanggal_publikasi, :jam_publikasi, :jenis_konten,
                    :targetTanggal, :targetJam, :jenisMedia,
                    :detailKonten, :statusPengajuan, :status_pengajuan,
                    :catatanKelengkapan, :alasanPenolakan, :sisaWaktuVerifikasi
                )";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':idPengajuan' => $data['idPengajuan'] ?? ('REQ-' . date('YmdHis')),
            ':idPengaju' => $data['idPengaju'] ?? 'PGJ-001',
            ':judulRequest' => $data['judulRequest'],
            ':tanggalPengajuan' => $data['tanggalPengajuan'] ?? date('Y-m-d'),
            ':tanggal_publikasi' => $tglPub,
            ':jam_publikasi' => $jamPub,
            ':jenis_konten' => $jnsKon,
            ':targetTanggal' => $tglPub,
            ':targetJam' => $jamPub,
            ':jenisMedia' => $jnsKon,
            ':detailKonten' => $data['detailKonten'],
            ':statusPengajuan' => $st,
            ':status_pengajuan' => $st,
            ':catatanKelengkapan' => $data['catatanKelengkapan'] ?? null,
            ':alasanPenolakan' => $data['alasanPenolakan'] ?? null,
            ':sisaWaktuVerifikasi' => $data['sisaWaktuVerifikasi'] ?? 3600
        ]);
    }

    public function cekKetersediaanSlot(string $targetTanggal, string $targetJam): bool
    {
        if (!$this->pdo)
            return true;
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM PENGAJUAN 
            WHERE (tanggal_publikasi = :tanggal OR targetTanggal = :tanggal2) 
              AND (jam_publikasi = :jam OR targetJam = :jam2) 
              AND (status_pengajuan != 'Ditolak' AND statusPengajuan != 'Ditolak')
        ");
        $stmt->execute([
            ':tanggal' => $targetTanggal,
            ':tanggal2' => $targetTanggal,
            ':jam' => $targetJam,
            ':jam2' => $targetJam
        ]);
        return ((int) $stmt->fetchColumn()) === 0;
    }

    public function validasiJadwalSOP(string $tanggalPublikasi, string $jamPublikasi, string $jenisKonten): array
    {
        $errors = [];
        $todayStr = date('Y-m-d');
        $todayTs = strtotime($todayStr);
        $targetTs = strtotime($tanggalPublikasi);

        if ($targetTs === false) {
            $errors[] = 'Format tanggal publikasi tidak valid.';
            return ['valid' => false, 'errors' => $errors];
        }

        $diffDays = (int) round(($targetTs - $todayTs) / 86400);
        $dayOfWeek = (int) date('w', $targetTs);

        // a) Tolak jika hari Minggu
        if ($dayOfWeek === 0) {
            $errors[] = 'Hari Minggu merupakan hari libur operasional CMI. Pengajuan tidak dapat dijadwalkan pada hari Minggu.';
        }

        // b) Tolak jika Feeds/Reels < 7 hari, atau Story < 3 hari
        if (in_array($jenisKonten, ['Feeds', 'Reels', 'Instagram Feeds', 'Reels / Video'], true)) {
            if ($diffDays < 7) {
                $errors[] = "Jenis konten {$jenisKonten} membutuhkan waktu pengerjaan minimal H-7 sebelum publikasi.";
            }
        } elseif ($jenisKonten === 'Story' || $jenisKonten === 'Instagram Story') {
            if ($diffDays < 3) {
                $errors[] = 'Jenis konten Story wajib diajukan minimal H-3 sebelum tanggal publikasi.';
            }
        }

        // c) Tolak jika slot terisi
        if (!$this->cekKetersediaanSlot($tanggalPublikasi, $jamPublikasi)) {
            $errors[] = "Slot jadwal publikasi pada tanggal {$tanggalPublikasi} pukul {$jamPublikasi} WIB telah dipesan oleh pengajuan lain.";
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors,
            'diffDays' => $diffDays
        ];
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