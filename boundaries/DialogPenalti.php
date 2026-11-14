<?php
// gion
class DialogPenalti
{
    public function tampilDialogPenalti(string $idPengajuan, int $poinPenalti = -5): array
    {
        return [
            'modalOpen' => true,
            'requirePenalti' => true,
            'idPengajuan' => $idPengajuan,
            'poinPenalti' => $poinPenalti,
            'message' => 'Batas waktu verifikasi 1 jam telah habis. Mengirim revisi akan mengurangi -5 poin kinerja kementerian.'
        ];
    }

    public function klikSetujuiPenalti(string $idPengajuan, string $kementerianId, string $teksRevisi): array
    {
        return [
            'success' => true,
            'idPengajuan' => $idPengajuan,
            'kementerianId' => $kementerianId,
            'teksRevisi' => $teksRevisi,
            'poinPenalti' => -5,
            'statusPengajuan' => 'Dalam Revisi Desain',
            'message' => 'Revisi dikirim dengan penalti keterlambatan -5 poin.'
        ];
    }
}