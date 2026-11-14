<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<boundary>> DialogAlasanPenolakan (Package Peninjauan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

class DialogAlasanPenolakan
{
    public function tampilFormAlasan(string $idPengajuan): array
    {
        return [
            'modalOpen' => true,
            'idPengajuan' => $idPengajuan
        ];
    }

    public function isiAlasanPenolakan(string $alasan): string
    {
        return trim($alasan);
    }

    public function klikSend(string $idPengajuan, string $alasan): array
    {
        return [
            'success' => true,
            'idPengajuan' => $idPengajuan,
            'statusPengajuan' => 'Ditolak',
            'alasanPenolakan' => $alasan,
            'message' => 'Pengajuan berhasil ditolak dan notifikasi telah dikirim ke email pengaju.'
        ];
    }

    public function klikCancel(): array
    {
        return $this->tutupPopup();
    }

    public function tutupPopup(): array
    {
        return ['modalOpen' => false];
    }

    public function tampilPeringatanAlasanKosong(): array
    {
        return [
            'success' => false,
            'message' => 'Alasan penolakan wajib diisi.'
        ];
    }
}