<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<boundary>> HalamanStatusPengajuan (Package Pengajuan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

class HalamanStatusPengajuan
{
    public function pilihMenuStatusPengajuan(): array
    {
        return ['redirect' => '../views/pengaju/status-pengajuan.html'];
    }

    public function tampilPesanBelumAda(): array
    {
        return [
            'empty' => true,
            'message' => 'Belum ada pengajuan',
            'data' => []
        ];
    }

    public function klikBuatPengajuan(): array
    {
        return ['redirect' => '../views/pengaju/pilihan-pengajuan.html'];
    }

    public function tampilDaftarPengajuan(array $daftarPengajuan): array
    {
        if (empty($daftarPengajuan)) {
            return $this->tampilPesanBelumAda();
        }
        return [
            'empty' => false,
            'data' => $daftarPengajuan
        ];
    }

    public function pilihPengajuan(string $idPengajuan): array
    {
        return ['redirect' => "../views/pengaju/detail-pengajuan.html?id={$idPengajuan}"];
    }
}