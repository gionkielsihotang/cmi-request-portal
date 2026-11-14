<?php
/**
 * SUBBAB 4.2 PROGRAM IMPLEMENTATION CODE
 * ID Kode     : [REV - SC01]
 * Nama Kelas  : ControlPengirimanDesain
 * Acuan SDD   : Bab 3.4 Package Desain & Bab 3.6 (PC-CPD-01)
 * Penyusun    : Revalia Rahmadani
 */

require_once __DIR__ . '/../../entities/BerkasLampiran.php';
require_once __DIR__ . '/../../entities/Pengajuan.php';
require_once __DIR__ . '/../../boundaries/FormUploadDesain.php';

class ControlPengirimanDesain
{

    // PC-CPD-01
    // Mengirim hasil desain
    public function prosesUploadDesain($idPengajuan, $fileDesain)
    {

        // 1. Validasi format dan ukuran berkas ke entitas BerkasLampiran
        // (format PNG/JPG/JPEG/PDF, ukuran maks 10 MB)
        $valid = BerkasLampiran::validasiBerkas($fileDesain);

        // 2. Evaluasi hasil validasi
        if ($valid === false) {
            // Alur Alternatif: format tidak didukung atau ukuran melebihi batas
            FormUploadDesain::tampilPeringatanBerkas("Format atau ukuran berkas tidak valid");
            return false;
        }

        // 3. Alur Normal: simpan berkas desain dengan kategori 'HasilDesain'
        BerkasLampiran::simpanBerkas($idPengajuan, $fileDesain, "HasilDesain");

        // 4. Perbarui status pengajuan menjadi 'Menunggu Verifikasi'
        Pengajuan::updateStatus($idPengajuan, "Menunggu Verifikasi");

        // 5. Aktifkan timer verifikasi 1 jam (3600 detik)
        Pengajuan::mulaiTimerVerifikasi($idPengajuan, 3600);

        // 6. Tampilkan notifikasi sukses beserta countdown 1 jam
        FormUploadDesain::tampilNotifikasiUploadSukses("Menunggu Verifikasi", 3600);

        return true;
    }
}
?>
