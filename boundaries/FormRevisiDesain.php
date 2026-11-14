<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<boundary>> FormRevisiDesain (Package Desain)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

class FormRevisiDesain
{
    public function tampilFormRevisi(string $idPengajuan): array
    {
        return ['redirect' => "../views/pengaju/form-revisi.html?id={$idPengajuan}"];
    }

    public function isiCatatanRevisi(string $teksRevisi): string
    {
        return trim($teksRevisi);
    }

    public function klikKirimRevisi(string $idPengajuan, string $teksRevisi): array
    {
        return [
            'action' => 'kirim_revisi',
            'idPengajuan' => $idPengajuan,
            'teksRevisi' => $teksRevisi
        ];
    }

    public function tampilPeringatanKosong(): array
    {
        return [
            'success' => false,
            'message' => 'Mohon lengkapi catatan revisi.'
        ];
    }

    public function tampilNotifikasiRevisiSukses(string $idPengajuan): array
    {
        return [
            'success' => true,
            'idPengajuan' => $idPengajuan,
            'statusPengajuan' => 'Dalam Revisi Desain',
            'message' => 'Catatan revisi berhasil dikirim ke CMI.'
        ];
    }
}