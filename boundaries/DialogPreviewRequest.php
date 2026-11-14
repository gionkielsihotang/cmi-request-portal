<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<boundary>> DialogPreviewRequest (Package Pengajuan)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

class DialogPreviewRequest
{
    public function tampilPreview(array $dataForm, array $daftarBerkas = [], int $durasi = 180): array
    {
        return [
            'success' => true,
            'modal' => 'preview',
            'countdown' => $durasi,
            'data' => $dataForm,
            'berkas' => $daftarBerkas
        ];
    }

    public function mulaiCountdown(int $durasi = 180): array
    {
        return ['timerSeconds' => $durasi];
    }

    public function periksaDataPreview(array $dataForm): array
    {
        return $dataForm;
    }

    public function klikSubmit(array $dataForm, array $daftarBerkas = []): array
    {
        return [
            'action' => 'submit_request',
            'data' => $dataForm,
            'berkas' => $daftarBerkas
        ];
    }

    public function klikBack(): array
    {
        return $this->tutupPreview();
    }

    public function tutupPreview(): array
    {
        return [
            'modalClosed' => true,
            'message' => 'Pratinjau ditutup, kembali ke formulir.'
        ];
    }

    public function tampilNotifWaktuHabis(): array
    {
        return [
            'success' => false,
            'timeout' => true,
            'message' => 'Waktu sesi pratinjau 3 menit telah habis. Silakan ulangi pengajuan.'
        ];
    }

    public function tampilNotifikasiBerhasil(string $idPengajuan): array
    {
        return [
            'success' => true,
            'idPengajuan' => $idPengajuan,
            'redirect' => "../views/pengaju/detail-pengajuan.html?id={$idPengajuan}&status=created",
            'message' => 'Pengajuan berhasil dikirim.'
        ];
    }
}