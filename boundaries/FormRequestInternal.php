<?php
class FormRequestInternal
{
    public static function tampilFormRequest()
    {
        header("Location: ../views/pengaju/form-request.html");
        exit;
    }

    public static function tampilIndikatorError()
    {
        header("Location: ../views/pengaju/form-request.html?error=kolom_kosong");
        exit;
    }

    public static function tampilPeringatanBerkas($pesan = "Format atau ukuran berkas tidak valid")
    {
        header("Location: ../views/pengaju/form-request.html?error=berkas_invalid&msg=" . urlencode($pesan));
        exit;
    }
}
?>