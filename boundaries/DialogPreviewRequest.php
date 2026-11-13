<?php
class DialogPreviewRequest
{
    public static function tampilPreview($dataForm, $daftarBerkas = [])
    {
        header("Location: ../views/pengaju/modal-preview.html");
        exit;
    }

    public static function mulaiCountdown($detik = 180)
    {
        // Timer dieksekusi di client-side oleh assets/js/countdown.js
        return $detik;
    }

    public static function tampilNotifikasiBerhasil($status = "Dalam Antrian")
    {
        header("Location: ../views/pengaju/detail-pengajuan.html?status=created");
        exit;
    }

    public static function tutupPreview()
    {
        return true;
    }

    public static function tampilNotifWaktuHabis()
    {
        header("Location: ../views/pengaju/form-request.html?error=timeout");
        exit;
    }
}
?>