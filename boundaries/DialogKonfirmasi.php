<?php
// ============================================================================
// CLASS DIAGRAM MODUL 2 : <<boundary>> DialogKonfirmasi (Package Desain)
// Penyusun              : [GYS] Gion Yehezkiel Sihotang
// ============================================================================

class DialogKonfirmasi
{
    public function bukaDialogKonfirmasi(string $idPengajuan, string $pesan = 'Setujui desain ini?'): array
    {
        return [
            'modalOpen' => true,
            'idPengajuan' => $idPengajuan,
            'message' => $pesan
        ];
    }

    public function klikKonfirmasiSetuju(string $idPengajuan): array
    {
        return [
            'success' => true,
            'idPengajuan' => $idPengajuan,
            'statusPengajuan' => 'Fix',
            'message' => 'Desain berhasil disetujui dan berstatus Fix.'
        ];
    }
}