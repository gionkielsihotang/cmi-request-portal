<?php
// gion
class HalamanDetailRequest
{
    public function tampilDetailRequest(array $detailRequest, array $daftarLampiran = []): array
    {
        return [
            'success' => true,
            'detail' => $detailRequest,
            'lampiran' => $daftarLampiran
        ];
    }

    public function periksaKelengkapanMateri(array $checklistSOP): bool
    {
        return !in_array(false, $checklistSOP, true);
    }

    public function klikTerima(string $idPengajuan): array
    {
        return ['action' => 'konfirmasi_proses', 'idPengajuan' => $idPengajuan];
    }

    public function klikProsesRequest(string $idPengajuan): array
    {
        return ['action' => 'proses_request', 'idPengajuan' => $idPengajuan];
    }

    public function klikTolak(string $idPengajuan): array
    {
        return ['action' => 'buka_modal_tolak', 'idPengajuan' => $idPengajuan];
    }

    public function tampilStatusDiproses(string $idPengajuan, array $logAktivitas = []): array
    {
        return [
            'success' => true,
            'idPengajuan' => $idPengajuan,
            'statusPengajuan' => 'Diproses',
            'logs' => $logAktivitas,
            'message' => 'Request berhasil diproses.'
        ];
    }

    public function isiCatatanKelengkapan(string $idPengajuan, string $catatan): array
    {
        return [
            'success' => true,
            'idPengajuan' => $idPengajuan,
            'statusPengajuan' => 'Ditinjau',
            'catatanKelengkapan' => $catatan,
            'message' => 'Catatan kelengkapan materi berhasil disimpan.'
        ];
    }
}