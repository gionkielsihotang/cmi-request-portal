<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<boundary>> FormUploadDesain (Package Desain)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

class FormUploadDesain
{
    public function tampilFormUpload(string $idPengajuan): array
    {
        return ['redirect' => "../views/admin/unggah-desain.html?id={$idPengajuan}"];
    }

    public function pilihBerkasDesain(array $fileDesain): array
    {
        return $fileDesain;
    }

    public function klikKirimDesain(string $idPengajuan, array $fileDesain): array
    {
        return [
            'action' => 'upload_desain',
            'idPengajuan' => $idPengajuan,
            'file' => $fileDesain
        ];
    }

    public function tampilPeringatanBerkas(string $pesanError): array
    {
        return [
            'success' => false,
            'message' => $pesanError
        ];
    }

    public function tampilNotifikasiUploadSukses(string $idPengajuan, int $sisaWaktu = 3600): array
    {
        return [
            'success' => true,
            'idPengajuan' => $idPengajuan,
            'statusPengajuan' => 'Menunggu Verifikasi',
            'sisaWaktuVerifikasi' => $sisaWaktu,
            'message' => 'Hasil desain berhasil diunggah. Timer verifikasi 1 jam dimulai.'
        ];
    }
}