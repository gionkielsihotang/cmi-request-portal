<?php
class HalamanStatusPengajuan
{
    public static function tampilPesanBelumAda()
    {
        header("Location: ../views/pengaju/status-pengajuan.html?state=empty");
        exit;
    }

    public static function tampilDaftarPengajuan()
    {
        header("Location: ../views/pengaju/status-pengajuan.html");
        exit;
    }
}
?>