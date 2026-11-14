<?php
// gion
class HalamanDetailPengajuan
{
    public function tampilDetailPengajuan(array $detailPengajuan): array
    {
        return [
            'success' => true,
            'data' => $detailPengajuan
        ];
    }

    public function bukaDetailPermohonan(string $idPengajuan): array
    {
        return ['redirect' => "../views/pengaju/detail-pengajuan.html?id={$idPengajuan}"];
    }

    public function tampilPratinjauDanTimer(array $dataDesain, int $sisaWaktu): array
    {
        return [
            'success' => true,
            'desain' => $dataDesain,
            'sisaWaktuVerifikasi' => $sisaWaktu
        ];
    }

    public function klikSetujuiDesain(string $idPengajuan): array
    {
        return [
            'action' => 'konfirmasi_setujui',
            'idPengajuan' => $idPengajuan
        ];
    }

    public function klikAjukanRevisi(string $idPengajuan): array
    {
        return ['redirect' => "../views/pengaju/form-revisi.html?id={$idPengajuan}"];
    }

    public function perbaruiTampilanVerifikasi(string $statusBaru, string $pesan): array
    {
        return [
            'success' => true,
            'statusPengajuan' => $statusBaru,
            'message' => $pesan
        ];
    }

    public function kunciAksesVerifikasi(string $alasan = 'Desain sudah berstatus Fix'): array
    {
        return [
            'locked' => true,
            'reason' => $alasan
        ];
    }
}